@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Activity Logs & Security Audit Trail</h4>
        <p class="text-muted small mb-0">Immutable record of authentications, administrative actions, and security-relevant events.</p>
    </div>
</div>

<div class="admin-card mb-4">
    <div class="admin-card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="action" class="form-control border-start-0" placeholder="Filter by action keyword (e.g. login, product, order)..."
                           value="{{ request('action') }}">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-admin-primary flex-grow-1">Filter</button>
                @if(request('action'))
                    <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-admin-outline">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User Identity</th>
                    <th>Action Event</th>
                    <th>Source IP Address</th>
                    <th>Metadata Payload</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="small text-muted text-nowrap">
                            <i class="bi bi-clock me-1"></i>{{ $log->created_at->format('d M Y, H:i:s') }}
                        </td>
                        <td>
                            <div class="fw-semibold small">{{ $log->user->email ?? 'System / Anonymous' }}</div>
                        </td>
                        <td>
                            <span class="admin-badge {{ str_contains($log->action, 'failed') || str_contains($log->action, 'delete') ? 'danger' : (str_contains($log->action, 'login') ? 'success' : 'primary') }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            <code class="text-muted small">{{ $log->ip_address }}</code>
                        </td>
                        <td class="small text-muted" style="max-width: 320px;">
                            <div class="text-truncate" title="{{ json_encode($log->description) }}">
                                {{ json_encode($log->description) }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-shield-check fs-2 d-block mb-2 text-muted"></i>
                            No activity audit logs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="p-3 border-top">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
