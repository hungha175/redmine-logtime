<?php

namespace App\Http\Controllers;

use App\Services\RedmineService;
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

        $mySpentByIssue = [];
        $todayHours = 0.0;
        $hoursByDay = [];

        if (!empty($issues)) {
            $spentData = $this->redmine->fetchMySpentHoursForIssues(array_column($issues, 'id'));
            $mySpentByIssue = $spentData['by_issue'];
            $todayHours = $spentData['today'];
            $hoursByDay = $spentData['by_day'];
        }

        return view('logtime.index', compact(
            'message', 'issues', 'activities', 'mySpentByIssue', 'todayHours', 'hoursByDay',
            'selectedMonth', 'ticketPage', 'perPage', 'issueTotalPages'
        ))->with('hasApiKey', $this->redmine->hasApiKey());
    }

    protected function handlePost(Request $request)
    {
        if ($request->has('api_key')) {
            $key = trim($request->attributes->get('_api_key_raw', $request->input('api_key', '')));
            Session::forget(['rm_username', 'rm_password']);
            if ($key === '') {
                Session::forget('rm_api_key');
                Session::flash('flash_message', 'Đã xóa API key.');
            } else {
                Session::put('rm_api_key', Crypt::encryptString($key));
                Session::flash('flash_message', '✅ Đã lưu API key, đang dùng để logtime.');
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
                $issueIdsStr = $request->input('issues_on_page', '');
                if ($anyLogged && $issueIdsStr !== '') {
                    $issueIds = array_filter(array_map('intval', explode(',', $issueIdsStr)));
                    $spentData = $this->redmine->fetchMySpentHoursForIssues($issueIds);
                    $payload['updated'] = true;
                    $payload['today'] = $spentData['today'];
                    $payload['by_issue'] = $spentData['by_issue'];
                    $payload['by_day'] = $spentData['by_day'];
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
