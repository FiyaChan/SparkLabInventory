<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('activity-log.view');

        $logs = ActivityLog::with('user:id,name,email')
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->input('action').'%'))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity-logs.index', compact('logs'));
    }
}
