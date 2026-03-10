<div class="table-responsive ticket-list-scroll mb-4">
    <table class="table table-bordered mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Tracker</th>
                <th>Status</th>
                <th>Subject</th>
                <th>My Spent</th>
                <th>Date</th>
                <th>Hours</th>
                <th>Activity</th>
                <th>Comment</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($issues as $issue)
            @php $mySpent = $mySpentByIssue[$issue['id']] ?? 0; @endphp
            <tr>
                <td><a target="_blank" href="{{ config('redmine.url') }}/issues/{{ $issue['id'] }}">#{{ $issue['id'] }}</a></td>
                <td>
                    @if(!empty($issue['tracker']))
                        <span class="tracker-badge">{{ $issue['tracker'] }}</span>
                    @else
                        —
                    @endif
                </td>
                <td>{{ $issue['status'] ?? '' }}</td>
                <td class="text-dark">{{ $issue['subject'] }}</td>
                <td class="text-nowrap" data-issue-id="{{ $issue['id'] }}">{{ $mySpent > 0 ? number_format($mySpent, 2) . ' h' : '—' }}</td>
                <td style="width: 135px;">
                    <input type="date" name="issues[{{ $issue['id'] }}][date]" class="form-control"
                           value="{{ old('issues.'.$issue['id'].'.date', date('Y-m-d')) }}">
                </td>
                <td style="width: 130px;">
                    <input type="number" step="0.1" min="0"
                           name="issues[{{ $issue['id'] }}][hours]" class="form-control" placeholder="0">
                </td>
                <td style="min-width: 140px;">
                    <select name="issues[{{ $issue['id'] }}][activity]" class="form-control">
                        @foreach($activities as $activity)
                            <option value="{{ $activity['id'] }}">{{ $activity['name'] }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <textarea name="issues[{{ $issue['id'] }}][comment]"
                        class="form-control" placeholder="Note..." rows="2"></textarea>
                </td>
                <td class="text-center" style="width: 80px;">
                    <button type="submit" name="save_issue" value="{{ $issue['id'] }}"
                            class="btn btn-sm btn-outline-primary btn-save-row">Save</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($issueTotalPages > 1)
    <nav class="d-flex justify-content-end mb-2">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item {{ $ticketPage <= 1 ? ' disabled' : '' }}">
                <a class="page-link" href="{{ $ticketPage <= 1 ? '#' : route('logtime.index', ['month' => $selectedMonth, 'p' => max(1, $ticketPage - 1)]) }}">«</a>
            </li>
            <li class="page-item active">
                <span class="page-link">{{ $ticketPage }} / {{ $issueTotalPages }}</span>
            </li>
            <li class="page-item {{ $ticketPage >= $issueTotalPages ? ' disabled' : '' }}">
                <a class="page-link" href="{{ $ticketPage >= $issueTotalPages ? '#' : route('logtime.index', ['month' => $selectedMonth, 'p' => $ticketPage + 1]) }}">»</a>
            </li>
        </ul>
    </nav>
@endif
