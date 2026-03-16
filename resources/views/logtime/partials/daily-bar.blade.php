@php
    $startOfMonth = $selectedMonth . '-01';
    $tsStart = strtotime($startOfMonth);
    $daysInMonth = (int)date('t', $tsStart);
    $todayStr = date('Y-m-d');
@endphp
<div class="daily-bar mb-2">
    @for($d = 1; $d <= $daysInMonth; $d++)
        @php
            $date = sprintf('%s-%02d', $selectedMonth, $d);
            $ts = strtotime($date);
            $dow = date('D', $ts);
            $dowNum = (int)date('N', $ts);
            $hrs = $hoursByDay[$date] ?? 0.0;
            $isToday = ($date === $todayStr);
            $isWeekend = ($dowNum >= 6);
            $isOk = (!$isWeekend && $hrs >= 7);
            $isLow = (!$isWeekend && !$isOk && $hrs < 7);
            $classes = 'day-pill';
            if ($isWeekend) $classes .= ' weekend';
            if ($isOk) $classes .= ' ok';
            if ($isLow) $classes .= ' low';
            if ($isToday) $classes .= ' today';
        @endphp
        <div class="{{ $classes }} day-pill-el" data-date="{{ $date }}">
            <small>{{ $dow }} {{ sprintf('%02d', $d) }}</small>
            <strong>{{ $hrs > 0 ? number_format($hrs, 1) : '0.0' }}h</strong>
        </div>
    @endfor
</div>
<div class="daily-bar-legend mb-3 text-muted small">
    <span class="mr-2"><span class="legend-dot legend-ok"></span> ≥ 7h</span>
    <span class="mr-2"><span class="legend-dot legend-low"></span> &lt; 7h</span>
    <span><span class="legend-dot legend-weekend"></span> Weekend</span>
</div>
