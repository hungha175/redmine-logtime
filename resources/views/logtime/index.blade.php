<!DOCTYPE html>
<html>
<head>
    <title>Redmine Auto Logtime</title>
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
            font-size: 0.875rem;
            padding: .4rem .6rem;
            border-radius: 6px;
        }
        .ticket-list-scroll .form-control:focus {
            border-color: #5c7cfa;
            box-shadow: 0 0 0 2px rgba(92,124,250,.2);
        }
        .ticket-list-scroll textarea.form-control {
            resize: vertical;
            min-height: 52px;
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
        .login-fab {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2c3e50;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,.2);
            font-size: 18px;
        }
        .login-fab span { line-height: 1; }
        .login-panel {
            position: absolute;
            top: 3.5rem;
            right: 1rem;
            width: 320px;
            max-width: 90vw;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,.15);
            padding: 0.75rem 1rem;
            display: none;
            z-index: 10;
        }
        .login-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: .5rem;
        }
        .login-panel-header span {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #868e96;
            letter-spacing: .05em;
        }
    </style>
</head>
<body class="p-4">

<div class="container-fluid" style="max-width: 1400px; position: relative;">

    <div class="logtime-card position-relative">
        <div class="login-fab" id="loginFab"><span>⚙</span></div>
        <div class="login-panel" id="loginPanel">
            <div class="login-panel-header">
                <span>Redmine API Key</span>
                <button type="button" class="close" style="font-size:16px;line-height:1;" onclick="toggleLoginPanel(false)">&times;</button>
            </div>
            <form method="POST" action="{{ route('logtime.index') }}" autocomplete="off">
                @csrf
                <div class="form-group mb-2">
                    <label for="api_key" class="text-secondary small">API Key</label>
                    <input type="password" name="api_key" id="api_key" placeholder="Nhập API key từ Redmine"
                           class="form-control form-control-sm" autocomplete="off">
                    <small class="text-muted d-block">Lấy từ: My account → API access key. Để trống + Lưu = xóa.</small>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Lưu API key</button>
                @if($hasApiKey ?? false)
                    <div class="text-muted small mt-2">Đã cấu hình API key.</div>
                @else
                    <div class="text-muted small mt-2">Chưa cấu hình API key.</div>
                @endif
            </form>
        </div>

        <h3>My Open Tickets</h3>

        <div id="alertMessage" class="alert alert-success" style="display:{{ $message ? 'block' : 'none' }}">{{ $message }}</div>

        <form method="GET" action="{{ route('logtime.index') }}" class="form-inline mb-3">
            <label class="text-secondary small mr-2">Month</label>
            <input type="month" name="month" class="form-control form-control-sm mr-2"
                   value="{{ $selectedMonth }}">
            <button class="btn btn-sm btn-outline-secondary" type="submit">Apply</button>
        </form>

        @include('logtime.partials.daily-bar')

        <form method="POST" action="{{ route('logtime.index') }}" id="logtimeForm">
            @csrf
            <input type="hidden" name="issues_on_page" value="{{ implode(',', array_column($issues, 'id')) }}">
            <div class="d-flex flex-wrap align-items-end justify-content-end mb-4">
                <div class="text-right">
                    <span class="text-secondary small d-block">Today</span>
                    <strong id="todayHours" class="text-dark" style="font-size: 1.25rem;">{{ number_format($todayHours, 2) }}</strong> h
                </div>
            </div>

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
