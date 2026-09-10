@extends('layouts.admin')

@section('content')
<h4 class="mb-3">Activity Logs (Audit Trail)</h4>

<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="action" class="form-control form-control-sm" placeholder="Filter by action (e.g. login)"
               value="{{ request('action') }}">
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-outline-secondary">Filter</button>
    </div>
</form>

<table class="table table-sm">
    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>IP</th><th>Details</th></tr></thead>
    <tbody>
        @foreach ($logs as $log)
            <tr>
                <td class="small">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                <td class="small">{{ $log->user->email ?? 'System / Guest' }}</td>
                <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                <td class="small">{{ $log->ip_address }}</td>
                <td class="small text-muted">
                    {{-- json_encode output here still passes through Blade's {{ }}
                         escaping, so even if a logged field somehow contained
                         HTML-like characters, it renders as inert text, not markup --}}
                    {{ json_encode($log->description) }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
{{ $logs->links() }}
@endsection
