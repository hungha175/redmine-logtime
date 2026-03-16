<?php

namespace App\Http\Controllers;

use App\Services\RedmineService;
use App\Models\RedmineCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

class LogtimeController extends Controller
{
    public function __construct(
        protected RedmineService $redmine
    ) {}

    public function index(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->handlePost($request);
        }

        $message = Session::get('flash_message');
        Session::forget('flash_message');

        $data = $this->redmine->getIssuesAndActivities();
        $issues = $data['issues'];
        $activities = $data['activities'];
        $ticketPage = $data['ticket_page'] ?? 1;
        $perPage = $data['per_page'] ?? 25;
        $issueTotalPages = $data['total_pages'] ?? 1;

        $selectedMonth = $request->get('month', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = date('Y-m');
        }

        $filter = $data['filter'] ?? 'assignee';

        $userSpent = $this->redmine->fetchMySpentHoursForUser();
        $todayHours = $userSpent['today'];
        $hoursByDay = $userSpent['by_day'];

        $mySpentByIssue = [];
        if (!empty($issues)) {
            $spentData = $this->redmine->fetchMySpentHoursForIssues(array_column($issues, 'id'));
            $mySpentByIssue = $spentData['by_issue'];
        }

        return view('logtime.index', compact(
            'message', 'issues', 'activities', 'mySpentByIssue', 'todayHours', 'hoursByDay',
            'selectedMonth', 'ticketPage', 'perPage', 'issueTotalPages', 'filter'
        ))->with('hasApiKey', $this->redmine->hasApiKey());
    }

    protected function handlePost(Request $request)
    {
        if ($request->input('save_type') === 'api_key') {
            $key = trim($request->attributes->get('_api_key_raw', $request->input('api_key', '')));
            $username = trim($request->input('login_username', ''));
            $passwordRaw = $request->attributes->get('_login_password_raw', $request->input('login_password', ''));

            Session::forget(['rm_username', 'rm_password']);
            if ($key === '') {
                Session::forget('rm_api_key');
                // Clear API key in DB but keep other fields
                $cred = RedmineCredential::query()->first();
                if ($cred) {
                    $cred->api_key_encrypted = null;
                    $cred->use_api = false;
                    $cred->save();
                }
                Session::flash('flash_message', 'Đã xóa API key.');
            } else {
                Session::put('rm_api_key', Crypt::encryptString($key));
                 // Upsert DB record with API key (+ optional username/password)
                $cred = RedmineCredential::query()->first() ?? new RedmineCredential();
                $cred->api_key_encrypted = Crypt::encryptString($key);
                if ($username !== '') {
                    $cred->username = $username;
                }
                if ($passwordRaw !== '') {
                    $cred->password_encrypted = Crypt::encryptString($passwordRaw);
                }
                $cred->use_api = true;
                $cred->save();
                Session::flash('flash_message', '✅ Đã lưu API key, đang dùng để logtime.');
            }
            Cache::flush();
            return redirect()->route('logtime.index');
        }

        if ($request->input('save_type') === 'login') {
            $u = trim($request->input('login_username', ''));
            $p = $request->attributes->get('_login_password_raw', $request->input('login_password', ''));
            $cred = RedmineCredential::query()->first();

            if ($u === '' || $p === '') {
                Session::forget(['rm_username', 'rm_password']);
                // Clear username/password in DB, keep API key if any
                if ($cred) {
                    $cred->username = null;
                    $cred->password_encrypted = null;
                    $cred->save();
                }
                Session::flash('flash_message', '⚠️ Vui lòng nhập đầy đủ Username và Password.');
            } else {
                // Update session creds
                Session::put([
                    'rm_username' => $u,
                    'rm_password' => Crypt::encryptString($p),
                ]);
                // Upsert DB creds, preserve API key if exists
                if (!$cred) {
                    $cred = new RedmineCredential();
                }
                $cred->username = $u;
                $cred->password_encrypted = Crypt::encryptString($p);
                // Nếu DB đã có api_key_encrypted thì tiếp tục ưu tiên dùng API
                if ($cred->api_key_encrypted) {
                    $cred->use_api = true;
                    // đảm bảo session có rm_api_key nếu chưa có
                    if (!Session::has('rm_api_key')) {
                        Session::put('rm_api_key', $cred->api_key_encrypted);
                    }
                }
                $cred->save();
                Session::flash('flash_message', '✅ Đã lưu tài khoản Redmine.');
            }
            Cache::flush();
            return redirect()->route('logtime.index');
        }

        $onlyIssueId = (int) $request->input('save_issue', 0);
        $issuesInput = $request->input('issues', []);

        if (!$onlyIssueId || !is_array($issuesInput)) {
            Session::flash('flash_message', '⚠️ Hãy dùng nút Save ở từng dòng ticket để logtime.');
            return redirect()->route('logtime.index');
        }

        $anyLogged = false;
        foreach ($issuesInput as $issueId => $row) {
            if (empty($row['hours']) || (float) $row['hours'] <= 0) {
                continue;
            }
            $activityId = (int) ($row['activity'] ?? 0);
            if ($activityId <= 0) {
                continue;
            }
            $issueIdInt = (int) $issueId;
            if ($issueIdInt !== $onlyIssueId) {
                continue;
            }
            $spentOn = !empty($row['date']) ? $row['date'] : date('Y-m-d');
            $ok = $this->redmine->logTime(
                $issueIdInt,
                (float) $row['hours'],
                $activityId,
                $row['comment'] ?? '',
                $spentOn
            );
            if ($ok) {
                $anyLogged = true;
                $this->redmine->invalidateSpentCache();
            }
        }

        $message = $anyLogged
            ? '✅ Log time thành công!'
            : '⚠️ Không có dòng nào được log (có thể bạn chưa nhập Hours hoặc session Redmine hết hạn).';

        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->wantsJson()) {
            try {
                $payload = ['success' => $anyLogged, 'message' => $message];
                if ($anyLogged) {
                    $userSpent = $this->redmine->fetchMySpentHoursForUser();
                    $issueIdsStr = $request->input('issues_on_page', '');
                    $pageIds = $issueIdsStr !== '' ? array_filter(array_map('intval', explode(',', $issueIdsStr))) : [];
                    $byIssue = !empty($pageIds) ? $this->redmine->fetchMySpentHoursForIssues($pageIds)['by_issue'] : [];
                    $payload['updated'] = true;
                    $payload['today'] = $userSpent['today'];
                    $payload['by_day'] = $userSpent['by_day'];
                    $payload['by_issue'] = $byIssue;
                }
                return response()->json($payload);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi: ' . $e->getMessage(),
                ], 500);
            }
        }

        Session::flash('flash_message', $message);
        return redirect()->route('logtime.index');
    }
}
