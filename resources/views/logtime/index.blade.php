<!DOCTYPE html>
<html>
<head>
    <title>Redmine Auto Logtime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --header-bg: #2c3e50;
            --header-color: #fff;
            --border: #e9ecef;
            --row-hover: #f8f9fa;
            --row-zebra: #fafbfc;
        }
        body { font-family: 'Inter', -apple-system, sans-serif; background: #f1f3f5; }
        .logtime-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .logtime-card h3 { font-weight: 600; color: #1a1d21; margin-bottom: 1.25rem; }
        .ticket-list-scroll {
            max-height: 70vh;
            overflow-y: auto;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        .ticket-list-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--header-bg);
            color: var(--header-color);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            padding: .75rem 1rem;
            border: none;
        }
        .ticket-list-scroll thead th:first-child { border-radius: 10px 0 0 0; }
        .ticket-list-scroll thead th:last-child { border-radius: 0 10px 0 0; }
        .ticket-list-scroll tbody tr:nth-child(even) { background: var(--row-zebra); }
        .ticket-list-scroll tbody tr:hover { background: var(--row-hover); }
        .ticket-list-scroll tbody td {
            padding: .6rem 1rem;
            vertical-align: middle;
            border-color: var(--border);
            font-size: 0.9rem;
        }
        .ticket-list-scroll tbody td:first-child { font-weight: 600; color: #495057; }
        .tracker-badge {
            display: inline-block;
            padding: .25rem .5rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 6px;
            background: #e9ecef;
            color: #495057;
        }
        .ticket-list-scroll .form-control {
            font-size: 0.9rem;
            padding: .5rem .7rem;
            border-radius: 6px;
            min-height: 38px;
        }
        .ticket-list-scroll .form-control:focus {
            border-color: #5c7cfa;
            box-shadow: 0 0 0 2px rgba(92,124,250,.2);
        }
        .ticket-list-scroll textarea.form-control {
            resize: vertical;
            min-height: 70px;
        }
        .btn-save { padding: .5rem 1.5rem; font-weight: 600; border-radius: 8px; background: #2c3e50; border: none; }
        .btn-save:hover { background: #1a252f; color: #fff; }
        .daily-bar {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }
        .day-pill {
            min-width: 70px;
            padding: .3rem .4rem;
            border-radius: 8px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            text-align: center;
            font-size: 0.75rem;
        }
        .day-pill strong {
            display: block;
            font-size: 0.9rem;
        }
        .day-pill small {
            display: block;
            font-size: 0.7rem;
            color: #868e96;
        }
        .day-pill.today {
            border-color: #5c7cfa;
            background: #edf2ff;
        }
        .day-pill.low {
            border-color: #fa5252;
            background: #fff5f5;
            color: #c92a2a;
        }
        .day-pill.ok {
            border-color: #51cf66;
            background: #ebfbee;
            color: #2b8a3e;
        }
        .day-pill.weekend {
            background: #f1f3f5;
            border-style: dashed;
        }
        .daily-bar-legend {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .5rem;
        }
        .legend-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 4px;
        }
        .legend-ok { background: #51cf66; }
        .legend-low { background: #fa5252; }
        .legend-weekend { background: #adb5bd; }
        .login-fab {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(44,62,80,.35);
            font-size: 20px;
            transition: transform .2s, box-shadow .2s;
        }
        .login-fab:hover { transform: scale(1.05); box-shadow: 0 6px 16px rgba(44,62,80,.4); }
        .login-fab span { line-height: 1; }
        .login-panel {
            position: absolute;
            top: 3.75rem;
            right: 1rem;
            width: 360px;
            max-width: 90vw;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0,0,0,.12), 0 0 1px rgba(0,0,0,.08);
            padding: 0;
            display: none;
            z-index: 10;
            overflow: hidden;
        }
        .login-panel-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: #fff;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .login-panel-header span {
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: .02em;
        }
        .login-panel-header .close {
            color: rgba(255,255,255,.8);
            opacity: 1;
            font-size: 1.25rem;
            line-height: 1;
        }
        .login-panel-header .close:hover { color: #fff; }
        .login-panel-body { padding: 1.25rem; }
        .login-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .login-section:last-of-type { margin-bottom: 0; }
        .login-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748b;
            margin-bottom: .75rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .login-section-title::before {
            content: '';
            width: 4px;
            height: 14px;
            background: linear-gradient(180deg, #5c7cfa, #748ffc);
            border-radius: 2px;
        }
        .login-section.login .login-section-title::before { background: linear-gradient(180deg, #51cf66, #69db7c); }
        .login-panel .form-control {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 0.875rem;
            padding: .5rem .75rem;
        }
        .login-panel .form-control:focus {
            border-color: #5c7cfa;
            box-shadow: 0 0 0 3px rgba(92,124,250,.15);
        }
        .login-panel .form-control::placeholder { color: #94a3b8; }
        .login-panel small.text-muted {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: .35rem;
            display: block;
        }
        .login-panel .btn-save-api {
            background: linear-gradient(135deg, #5c7cfa 0%, #748ffc 100%);
            color: #fff;
            border: none;
            padding: .45rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: opacity .2s;
        }
        .login-panel .btn-save-api:hover { opacity: .9; color: #fff; }
        .login-panel .btn-save-login {
            background: linear-gradient(135deg, #51cf66 0%, #69db7c 100%);
            color: #fff;
            border: none;
            padding: .45rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: opacity .2s;
        }
        .login-panel .btn-save-login:hover { opacity: .9; color: #fff; }
        .login-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 1rem 0;
        }
        .login-status {
            margin-top: 1rem;
            padding: .6rem .85rem;
            border-radius: 8px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .login-status.using-api {
            background: #edf2ff;
            color: #364fc7;
        }
        .login-status.using-login {
            background: #ebfbee;
            color: #2b8a3e;
        }
        .login-status.none {
            background: #f1f5f9;
            color: #64748b;
        }
        .login-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .login-status.using-api .login-status-dot { background: #5c7cfa; }
        .login-status.using-login .login-status-dot { background: #51cf66; }
        .login-status.none .login-status-dot { background: #94a3b8; }

        /* Mobile responsive tweaks */
        @media (max-width: 576px) {
            body.p-4 {
                padding: 0.75rem !important;
            }

            .logtime-card {
                padding: 1rem;
                border-radius: 10px;
            }

            .container-fluid {
                padding: 0;
            }

            .daily-bar {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                flex-wrap: nowrap;
            }

            .daily-bar::-webkit-scrollbar {
                height: 3px;
            }

            .day-pill {
                min-width: 64px;
                padding: .3rem .35rem;
                font-size: 0.7rem;
            }

            .day-pill strong {
                font-size: 0.85rem;
            }

            .daily-bar-legend {
                font-size: 0.7rem;
            }

            .login-fab {
                top: 0.75rem;
                right: 0.75rem;
                width: 40px;
                height: 40px;
            }

            .login-panel {
                right: 0;
                left: 0;
                margin: 0 0.5rem;
                width: auto;
                max-width: none;
            }

            #monthForm,
            #filterForm {
                width: 100%;
            }

            #monthForm .form-control,
            #filterForm .form-control {
                width: 100% !important;
                max-width: 100%;
            }

            #monthForm label {
                margin-bottom: 0.25rem;
                display: block;
            }

            .ticket-list-scroll {
                max-height: none;
            }

            .ticket-list-scroll thead th,
            .ticket-list-scroll tbody td {
                padding: 0.45rem 0.5rem;
                font-size: 0.8rem;
            }

            .ticket-list-scroll .form-control {
                font-size: 0.9rem;
                padding: 0.45rem 0.6rem;
                min-height: 42px;
            }

            .ticket-list-scroll textarea.form-control {
                min-height: 110px;
            }

            /* Comment column: make it take most of the width on mobile */
            .ticket-list-scroll tbody td:nth-child(9) {
                min-width: 75vw;
            }

            .btn-save,
            .btn-save-row,
            .login-panel .btn-save-api,
            .login-panel .btn-save-login {
                padding: 0.45rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body class="p-4">

<div class="container-fluid" style="max-width: 1400px; position: relative;">

    <div class="logtime-card position-relative">
        <div class="login-fab" id="loginFab"><span>⚙</span></div>
        <div class="login-panel" id="loginPanel">
            <div class="login-panel-header">
                <span>Redmine đăng nhập</span>
                <button type="button" class="close" onclick="toggleLoginPanel(false)">&times;</button>
            </div>
            <form method="POST" action="{{ route('logtime.index') }}" autocomplete="off">
                @csrf
                <div class="login-panel-body">
                    <div class="login-section api">
                        <div class="login-section-title">API Key</div>
                        <div class="form-group mb-2">
                            <input type="password" name="api_key" placeholder="My account → API access key"
                                   class="form-control" autocomplete="off">
                            <small class="text-muted">Nếu nhập cùng Username/Password, API key sẽ được lưu vào DB.</small>
                        </div>
                    </div>

                    <div class="login-divider"></div>

                    <div class="login-section login">
                        <div class="login-section-title">Username / Password</div>
                        <div class="form-group mb-2">
                            <input type="text" name="login_username" placeholder="Username"
                                   value="{{ session('rm_username', '') }}" class="form-control mb-2">
                            <input type="password" name="login_password" placeholder="Password"
                                   class="form-control" autocomplete="off">
                            <small class="text-muted">Lần đầu có thể nhập kèm API key để lưu vào DB. Lần sau chỉ cần Username/Password.</small>
                        </div>
                        <button type="submit" name="save_type" value="credentials" class="btn btn-save-login">Lưu cấu hình</button>
                    </div>

                    @if($hasApiKey ?? false)
                        <div class="login-status using-api d-flex justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="login-status-dot"></span>
                                <span>Đang dùng: API key</span>
                            </div>
                            <button type="submit" name="save_type" value="logout" class="btn btn-sm btn-outline-secondary">Logout</button>
                        </div>
                    @elseif(session('rm_username'))
                        <div class="login-status using-login d-flex justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="login-status-dot"></span>
                                <span>Đang dùng: {{ session('rm_username') }}</span>
                            </div>
                            <button type="submit" name="save_type" value="logout" class="btn btn-sm btn-outline-secondary">Logout</button>
                        </div>
                    @else
                        <div class="login-status none">
                            <span class="login-status-dot"></span>
                            <span>Chưa cấu hình. Chọn API key hoặc Username/Password.</span>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <h3>My Open Tickets</h3>

        <div id="alertMessage" class="alert alert-success" style="display:{{ $message ? 'block' : 'none' }}">{{ $message }}</div>

        <form method="GET" action="{{ route('logtime.index') }}" class="form-inline mb-3 flex-wrap" id="monthForm">
            <label class="text-secondary small mr-2">Month</label>
            <input type="month" name="month" class="form-control form-control-sm mr-2"
                   value="{{ $selectedMonth }}" onchange="this.form.submit()">
            <input type="hidden" name="filter" value="{{ $filter ?? 'assignee' }}">
            {{-- <button class="btn btn-sm btn-outline-secondary" type="submit">Apply</button> --}}
        </form>

        @include('logtime.partials.daily-bar')

        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
            <form method="GET" action="{{ route('logtime.index') }}" class="form-inline mb-2 mb-sm-0" id="filterForm">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <select name="filter" class="form-control form-control-sm" style="width: auto;" onchange="this.form.submit()">
                    <option value="assignee" {{ ($filter ?? 'assignee') === 'assignee' ? 'selected' : '' }}>Assignee</option>
                    <option value="author" {{ ($filter ?? '') === 'author' ? 'selected' : '' }}>Author</option>
                    <option value="qc_name" {{ ($filter ?? '') === 'qc_name' ? 'selected' : '' }}>QC Name</option>
                    <option value="co_assignee" {{ ($filter ?? '') === 'co_assignee' ? 'selected' : '' }}>Co-assignee</option>
                </select>
            </form>
            <div class="text-right mt-2 mt-sm-0">
                <span class="text-secondary small d-block">Today</span>
                <strong id="todayHours" class="text-dark" style="font-size: 1.25rem;">{{ number_format($todayHours, 2) }}</strong> h
            </div>
        </div>

        <form method="POST" action="{{ route('logtime.index') }}" id="logtimeForm">
            @csrf
            <input type="hidden" name="issues_on_page" value="{{ implode(',', array_column($issues, 'id')) }}">
            @include('logtime.partials.ticket-table')
        </form>
    </div>

</div>

<script>
function toggleLoginPanel(forceState) {
    var panel = document.getElementById('loginPanel');
    if (!panel) return;
    if (typeof forceState === 'boolean') {
        panel.style.display = forceState ? 'block' : 'none';
        return;
    }
    panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
}
document.addEventListener('DOMContentLoaded', function() {
    var firstHours = document.querySelector('input[name^="issues"][name$="[hours]"]');
    if (firstHours) firstHours.focus();
    var fab = document.getElementById('loginFab');
    if (fab) fab.addEventListener('click', function() { toggleLoginPanel(); });
    var form = document.getElementById('logtimeForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            var btn = e.submitter;
            if (btn && btn.name === 'save_issue' && btn.value && btn.classList.contains('btn-save-row')) {
                e.preventDefault();
                var fd = new FormData(form);
                fd.append('save_issue', btn.value);
                btn.disabled = true;
                btn.textContent = '...';
                var token = form.querySelector('input[name="_token"]');
                var headers = {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                };
                if (token) headers['X-CSRF-TOKEN'] = token.value;
                fetch(form.action, {
                    method: 'POST',
                    body: fd,
                    headers: headers,
                    credentials: 'same-origin'
                }).then(function(r) {
                    return r.text().then(function(t) {
                        try {
                            var data = JSON.parse(t);
                            if (!r.ok) data._httpError = r.status;
                            return data;
                        } catch (_) {
                            if (r.status === 419) throw new Error('Phiên làm việc hết hạn. Vui lòng tải lại trang.');
                            if (r.status >= 500) throw new Error('Lỗi máy chủ. Vui lòng thử lại.');
                            throw new Error('Lỗi ' + r.status + '. Vui lòng tải lại trang.');
                        }
                    });
                })
                .then(function(data) {
                    if (data._httpError) throw new Error(data.message || 'Lỗi ' + data._httpError);
                    document.getElementById('alertMessage').textContent = data.message;
                    document.getElementById('alertMessage').style.display = 'block';
                    document.getElementById('alertMessage').className = 'alert alert-' + (data.success ? 'success' : 'warning');
                    if (data.updated && data.by_issue) {
                        document.getElementById('todayHours').textContent = (data.today || 0).toFixed(2);
                        for (var id in data.by_issue) {
                            var cell = form.querySelector('td[data-issue-id="' + id + '"]');
                            if (cell) cell.textContent = data.by_issue[id] > 0 ? data.by_issue[id].toFixed(2) + ' h' : '—';
                        }
                        var pills = document.querySelectorAll('.day-pill-el');
                        pills.forEach(function(p) {
                            var d = p.getAttribute('data-date');
                            var hrs = (data.by_day && data.by_day[d]) || 0;
                            var strong = p.querySelector('strong');
                            if (strong) strong.textContent = (hrs > 0 ? hrs.toFixed(1) : '0.0') + 'h';
                        });
                        var row = btn.closest('tr');
                        if (row) {
                            var h = row.querySelector('input[name*="[hours]"]');
                            var c = row.querySelector('textarea[name*="[comment]"]');
                            if (h) h.value = '';
                            if (c) c.value = '';
                        }
                    }
                }).catch(function(err) {
                    var msg = (err && err.message) ? err.message : '⚠️ Có lỗi xảy ra. Vui lòng tải lại trang và thử lại.';
                    document.getElementById('alertMessage').innerHTML = msg + ' <a href="#" onclick="location.reload();return false;">Tải lại</a>';
                    document.getElementById('alertMessage').style.display = 'block';
                    document.getElementById('alertMessage').className = 'alert alert-danger';
                }).finally(function() {
                    btn.disabled = false;
                    btn.textContent = 'Save';
                });
            }
        });
    }
});
</script>

</body>
</html>
