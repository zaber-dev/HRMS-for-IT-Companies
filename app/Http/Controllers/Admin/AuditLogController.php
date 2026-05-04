<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Display a paginated listing of audit log entries, newest first.
     * Only accessible to users with the `audit-log.view` permission (super_admin only).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $auditLogs = AuditLog::with('user')->orderByDesc('created_at')->paginate(20);

        return Inertia::render('admin/audit-logs/index', [
            'auditLogs' => $auditLogs,
        ]);
    }
}
