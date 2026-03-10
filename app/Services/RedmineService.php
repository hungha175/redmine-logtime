<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class RedmineService
{
    protected string $redmineUrl;
    protected string $cookieFile;
    protected bool $sslVerify;
    protected ?int $issueTotalPages = null;

    public function __construct()
    {
        $this->redmineUrl = rtrim(config('redmine.url'), '/');
        $this->cookieFile = config('redmine.cookie_file');
        $this->sslVerify = config('redmine.ssl_verify', false);
    }

    protected function getApiKey(): string
    {
        $encrypted = Session::get('rm_api_key', '');
        if ($encrypted !== '') {
            try {
                $decrypted = Crypt::decryptString($encrypted);
                if ($decrypted !== '') {
                    return trim($decrypted);
                }
            } catch (\Throwable) {
                //
            }
        }
        return trim(config('redmine.api_key', ''));
    }

    public function hasApiKey(): bool
    {
        return $this->getApiKey() !== '';
    }

    public function requestApi(string $method, string $endpoint, ?array $data = null): ?array
    {
        $timeout = config('redmine.timeout', 15);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->redmineUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Redmine-API-Key: ' . $this->getApiKey(),
            ],
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function login(): bool
    {
        $username = trim(session('rm_username', ''));
        $password = $this->getDecryptedPassword();
        if ($username === '' || $password === '') {
            return false;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->redmineUrl . '/login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $html = curl_exec($ch);
        if ($html === false) {
            curl_close($ch);
            return false;
        }

        $token = null;
        if (preg_match('/name="authenticity_token"[^>]*value="([^"]+)"/', $html, $m)) {
            $token = $m[1];
        }

        $fields = [
            'username' => $username,
            'password' => $password,
            'login' => 'Login',
        ];
        if ($token !== null) {
            $fields['authenticity_token'] = $token;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->redmineUrl . '/login',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
        ]);

        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return in_array($code, [200, 302], true);
    }

    public function logTime(int $issueId, float $hours, int $activityId, string $comment, string $spentOn): bool
    {
        return $this->getApiKey() !== ''
            ? $this->logTimeByApi($issueId, $hours, $activityId, $comment, $spentOn)
            : $this->logTimeHtmlForm($issueId, $hours, $activityId, $comment, $spentOn);
    }

    protected function logTimeByApi(int $issueId, float $hours, int $activityId, string $comment, string $spentOn): bool
    {
        $timeout = config('redmine.timeout', 15);
        $data = [
            'time_entry' => [
                'issue_id' => $issueId,
                'spent_on' => $spentOn,
                'hours' => $hours,
                'activity_id' => $activityId,
                'comments' => $comment,
            ],
        ];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->redmineUrl . '/time_entries.json',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Redmine-API-Key: ' . $this->getApiKey(),
            ],
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 201;
    }

    protected function logTimeHtmlForm(int $issueId, float $hours, int $activityId, string $comment, string $spentOn): bool
    {
        $timeout = config('redmine.timeout', 15);
        $html = $this->ensureLoggedAndGet('/time_entries/new?issue_id=' . $issueId);
        if ($html === false || trim($html) === '') {
            return false;
        }

        if (!preg_match('/name="authenticity_token".*?value="([^"]+)"/', $html, $m)) {
            return false;
        }

        $postFields = http_build_query([
            'authenticity_token' => $m[1],
            'time_entry[issue_id]' => $issueId,
            'time_entry[spent_on]' => $spentOn,
            'time_entry[hours]' => $hours,
            'time_entry[activity_id]' => $activityId,
            'time_entry[comments]' => $comment,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->redmineUrl . '/time_entries',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
        ]);

        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return in_array($code, [200, 302], true);
    }

    protected function curlGetWithCookie(string $url): string|false
    {
        $timeout = config('redmine.timeout', 15);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $html = curl_exec($ch);
        curl_close($ch);

        return $html;
    }

    protected function isLoginPageHtml(string|false $html): bool
    {
        if ($html === false || trim($html) === '') {
            return false;
        }
        return str_contains($html, 'name="username"')
            && str_contains($html, 'name="password"')
            && str_contains($html, '/login');
    }

    protected function ensureLoggedAndGet(string $pathOrUrl): string|false
    {
        $url = str_starts_with($pathOrUrl, 'http') ? $pathOrUrl : $this->redmineUrl . $pathOrUrl;

        $html = $this->curlGetWithCookie($url);
        if (!$this->isLoginPageHtml($html)) {
            return $html;
        }

        if (!$this->login()) {
            return $html;
        }

        return $this->curlGetWithCookie($url);
    }

    protected function parseTrackerNamesFromIssuesPage(string $html): array
    {
        $map = [];
        if (preg_match('/"tracker_id":\s*\{[^}]*"values":\s*\[[\s\S]*?\]\s*\]\s*\}\s*,\s*"priority_id"/', $html, $block)) {
            if (preg_match_all('/\["([^"]+)","(\d+)"\]/', $block[0], $opts, PREG_SET_ORDER)) {
                foreach ($opts as $o) {
                    $map[(int) $o[2]] = $o[1];
                }
            }
        }
        if (empty($map) && preg_match('/<select[^>]*name="[^"]*tracker_id[^"]*"[^>]*>([\s\S]*?)<\/select>/i', $html, $sel)) {
            if (preg_match_all('/<option[^>]*value="(\d+)"[^>]*>([^<]*)<\/option>/i', $sel[1], $opts, PREG_SET_ORDER)) {
                foreach ($opts as $o) {
                    $map[(int) $o[1]] = trim(html_entity_decode($o[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                }
            }
        }
        return $map;
    }

    protected function parseTotalIssuesCount(string $html): ?int
    {
        if ($html === false || trim($html) === '') {
            return null;
        }
        if (preg_match('/class="items"\>\\((\\d+)-(\\d+)\\/(\\d+)\\)\\</', $html, $m)) {
            return (int) $m[3];
        }
        return null;
    }

    public function fetchIssuesByScraping(int $page = 1, int $perPage = 25): array
    {
        $issues = [];
        $url = '/issues?assigned_to_id=me&set_filter=1&status_id=o&per_page=' . max(1, $perPage) . '&page=' . max(1, $page);

        $html = $this->ensureLoggedAndGet($url);
        if ($html === false || trim($html) === '') {
            return $issues;
        }

        if ($this->issueTotalPages === null) {
            $totalIssues = $this->parseTotalIssuesCount($html);
            $this->issueTotalPages = $totalIssues !== null && $perPage > 0
                ? max(1, (int) ceil($totalIssues / $perPage))
                : 1;
        }

        $trackerNames = $this->parseTrackerNamesFromIssuesPage($html);
        $pattern = '/<tr\s+id="issue-(\d+)"[^>]*class="[^"]*tracker-(\d+)[^"]*"[^>]*>([\s\S]*?)<\/tr>/i';
        $rowMatches = [];
        if (!preg_match_all($pattern, $html, $rowMatches, PREG_SET_ORDER)) {
            $pattern = '/<tr\s+id="issue-(\d+)"([\s\S]*?)<\/tr>/i';
            preg_match_all($pattern, $html, $rowMatches, PREG_SET_ORDER);
        }

        foreach ($rowMatches as $rowM) {
            $id = (int) $rowM[1];
            $trackerId = (isset($rowM[2]) && is_numeric($rowM[2])) ? (int) $rowM[2] : 0;
            $row = $rowM[3] ?? $rowM[2];

            $subject = '';
            if (preg_match('/<td[^>]*class="[^"]*subject[^"]*"[^>]*>[\s\S]*?<a[^>]*>(.*?)<\/a>/i', $row, $sm)) {
                $subject = html_entity_decode(strip_tags($sm[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            $status = '';
            if (preg_match('/<td[^>]*class="[^"]*status[^"]*"[^>]*>([\s\S]*?)<\/td>/i', $row, $st)) {
                $status = trim(html_entity_decode(strip_tags($st[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            $tracker = ($trackerId && isset($trackerNames[$trackerId])) ? $trackerNames[$trackerId] : '';
            if ($tracker === '' && preg_match('/<td[^>]*class="[^"]*tracker[^"]*"[^>]*>([\s\S]*?)<\/td>/i', $row, $tm)) {
                $tracker = trim(html_entity_decode(strip_tags($tm[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            $issues[] = ['id' => $id, 'subject' => $subject, 'status' => $status, 'tracker' => $tracker];
        }

        return $issues;
    }

    public function fetchActivitiesByScraping(): array
    {
        $html = $this->ensureLoggedAndGet('/time_entries/new');
        if ($html === false || trim($html) === '') {
            return [];
        }

        $activities = [];
        if (preg_match('/<select[^>]*name="time_entry\[activity_id\]"[^>]*>([\s\S]*?)<\/select>/i', $html, $selectMatch)) {
            if (preg_match_all('/<option[^>]*value="(\d+)"[^>]*>(.*?)<\/option>/i', $selectMatch[1], $optMatches, PREG_SET_ORDER)) {
                foreach ($optMatches as $opt) {
                    $activities[] = [
                        'id' => (int) $opt[1],
                        'name' => html_entity_decode(strip_tags($opt[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    ];
                }
            }
        }
        return $activities;
    }

    public function fetchMySpentHoursForIssues(array $issueIds): array
    {
        if (empty($issueIds)) {
            return ['by_issue' => [], 'total' => 0.0, 'today' => 0.0, 'by_day' => []];
        }
        $version = session('spent_cache_version', 0);
        $cacheKey = 'redmine_spent_' . md5(implode(',', array_map('intval', $issueIds))) . '_v' . $version;
        $ttl = config('redmine.cache_ttl_spent', 60);
        return Cache::remember($cacheKey, $ttl, fn () => $this->getApiKey() !== ''
            ? $this->fetchMySpentHoursByApi($issueIds)
            : $this->fetchMySpentHoursByScraping($issueIds));
    }

    public function invalidateSpentCache(): void
    {
        session(['spent_cache_version' => (session('spent_cache_version', 0) + 1)]);
    }

    protected function fetchMySpentHoursByApi(array $issueIds): array
    {
        $issueIdSet = array_fill_keys(array_map('intval', $issueIds), true);
        $map = [];
        $total = 0.0;
        $today = 0.0;
        $byDay = [];
        $todayStr = date('Y-m-d');
        $limit = 250;
        $offset = 0;

        while (true) {
            $data = $this->requestApi('GET', "/time_entries.json?user_id=me&limit={$limit}&offset={$offset}");
            $entries = ($data ?? [])['time_entries'] ?? [];
            if (empty($entries)) {
                break;
            }

            foreach ($entries as $e) {
                $val = (float) ($e['hours'] ?? 0);
                $spentOn = $e['spent_on'] ?? null;
                $issueId = isset($e['issue']['id']) ? (int) $e['issue']['id'] : null;

                $total += $val;
                if ($spentOn) {
                    if ($spentOn === $todayStr) {
                        $today += $val;
                    }
                    $byDay[$spentOn] = ($byDay[$spentOn] ?? 0) + $val;
                }
                if ($issueId && isset($issueIdSet[$issueId])) {
                    $map[$issueId] = ($map[$issueId] ?? 0) + $val;
                }
            }

            if (count($entries) < $limit || $offset >= 5000) {
                break;
            }
            $offset += $limit;
        }

        return ['by_issue' => $map, 'total' => $total, 'today' => $today, 'by_day' => $byDay];
    }

    protected function fetchMySpentHoursByScraping(array $issueIds): array
    {
        $issueIdSet = array_fill_keys(array_map('intval', $issueIds), true);
        $map = [];
        $total = 0.0;
        $today = 0.0;
        $byDay = [];
        $todayStr = date('Y-m-d');
        $page = 1;
        $perPage = 100;

        while (true) {
            $html = $this->ensureLoggedAndGet('/time_entries?user_id=me&set_filter=1&per_page=' . $perPage . '&page=' . $page);
            if ($html === false || trim($html) === '') {
                break;
            }

            $rowCount = 0;
            if (preg_match_all('/<tr[^>]*>([\s\S]*?)<\/tr>/i', $html, $rows, PREG_SET_ORDER)) {
                foreach ($rows as $row) {
                    $rowHtml = $row[1];
                    if (!preg_match('/<td[^>]*class="hours"[^>]*>\s*([\d\.,]+)\s*<\/td>/i', $rowHtml, $mHours)) {
                        continue;
                    }
                    $rowCount++;
                    $val = floatval(str_replace(',', '.', preg_replace('/[^\d\.,]/', '', $mHours[1])));
                    $total += $val;

                    $spentOn = null;
                    if (preg_match('/<td[^>]*class="spent_on"[^>]*>[\s\S]*?(\d{4}-\d{2}-\d{2})[\s\S]*?<\/td>/i', $rowHtml, $mDate)) {
                        $spentOn = $mDate[1];
                    } elseif (preg_match('/<td[^>]*class="spent_on"[^>]*>[\s\S]*?(\d{1,2})\/(\d{1,2})\/(\d{4})[\s\S]*?<\/td>/i', $rowHtml, $mDate)) {
                        $spentOn = sprintf('%04d-%02d-%02d', (int) $mDate[3], (int) $mDate[2], (int) $mDate[1]);
                    }
                    if ($spentOn) {
                        if ($spentOn === $todayStr) {
                            $today += $val;
                        }
                        $byDay[$spentOn] = ($byDay[$spentOn] ?? 0) + $val;
                    }

                    if (preg_match('/href="[^"]*\/issues\/(\d+)"/i', $rowHtml, $mIssue)) {
                        $issueId = (int) $mIssue[1];
                        if (isset($issueIdSet[$issueId])) {
                            $map[$issueId] = ($map[$issueId] ?? 0) + $val;
                        }
                    }
                }
            }

            if ($rowCount < $perPage || $page >= 200) {
                break;
            }
            $page++;
        }

        return ['by_issue' => $map, 'total' => $total, 'today' => $today, 'by_day' => $byDay];
    }

    public function getIssuesAndActivities(): array
    {
        if ($this->getApiKey() !== '') {
            return Cache::remember('redmine_issues_activities_api', config('redmine.cache_ttl_issues', 120), fn () => $this->fetchIssuesAndActivitiesByApi());
        }

        $hasCreds = !empty(session('rm_username')) && !empty(session('rm_password'));
        if (!$hasCreds) {
            return ['issues' => [], 'activities' => [], 'total_pages' => 1];
        }

        $ticketPage = max(1, (int) request()->get('p', 1));
        $perPage = 25;
        $cacheKey = 'redmine_scrape_' . session()->getId() . '_p' . $ticketPage;
        $result = Cache::remember($cacheKey, config('redmine.cache_ttl_issues', 120), function () use ($ticketPage, $perPage) {
            $issues = $this->fetchIssuesByScraping($ticketPage, $perPage);
            $activities = $this->fetchActivitiesByScraping();
            return [
                'issues' => $issues,
                'activities' => $activities,
                'total_pages' => $this->issueTotalPages ?? 1,
            ];
        });
        return array_merge($result, ['ticket_page' => $ticketPage, 'per_page' => $perPage]);
    }

    protected function fetchIssuesAndActivitiesByApi(): array
    {
        $timeout = config('redmine.timeout', 15);
        $headers = ['X-Redmine-API-Key' => $this->getApiKey()];
        $responses = Http::pool(fn ($pool) => [
            $pool->as('issues')->timeout($timeout)->withHeaders($headers)->when(!$this->sslVerify, fn ($r) => $r->withoutVerifying())->get($this->redmineUrl . '/issues.json?assigned_to_id=me&status_id=open'),
            $pool->as('activities')->timeout($timeout)->withHeaders($headers)->when(!$this->sslVerify, fn ($r) => $r->withoutVerifying())->get($this->redmineUrl . '/enumerations/time_entry_activities.json'),
        ]);

        $issuesData = $responses['issues']->json();
        $activitiesData = $responses['activities']->json();
        $rawIssues = ($issuesData ?? [])['issues'] ?? [];
        $issues = [];
        foreach ($rawIssues as $i) {
            $issues[] = [
                'id' => $i['id'],
                'subject' => $i['subject'] ?? '',
                'status' => $i['status']['name'] ?? '',
                'tracker' => $i['tracker']['name'] ?? '',
            ];
        }
        $activities = ($activitiesData ?? [])['time_entry_activities'] ?? [];
        return ['issues' => $issues, 'activities' => $activities, 'total_pages' => 1];
    }

    public function getIssueTotalPages(): int
    {
        return $this->issueTotalPages ?? 1;
    }

    protected function getDecryptedPassword(): string
    {
        $encrypted = session('rm_password', '');
        if ($encrypted === '') {
            return '';
        }
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return $encrypted;
        }
    }
}
