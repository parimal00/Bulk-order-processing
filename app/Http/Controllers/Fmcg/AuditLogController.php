<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Resources\Fmcg\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest('id');

        // Apply filters if provided
        if ($request->filled('actor')) {
            $actor = $request->input('actor');
            $query->where(function ($q) use ($actor) {
                $q->whereHas('user', function ($uq) use ($actor) {
                    $uq->where('name', 'like', "%{$actor}%");
                })->orWhere(function ($sq) use ($actor) {
                    if (str_contains(strtolower('system job'), strtolower($actor))) {
                        $sq->whereNull('user_id');
                    }
                });
            });
        }

        if ($request->filled('action')) {
            $action = $request->input('action');
            $query->where('action', 'like', "%{$action}%");
        }

        if ($request->filled('entity')) {
            $entity = $request->input('entity');
            $query->where(function ($q) use ($entity) {
                $q->where('entity_type', 'like', "%{$entity}%")
                  ->orWhere('details', 'like', "%{$entity}%");
            });
        }

        // Limit/Paginate to 100 entries for high speed and responsiveness
        $logs = $query->take(100)->get();

        return Inertia::render('fmcg/audit', [
            'auditTrail' => AuditLogResource::collection($logs)->resolve(),
            'filters' => $request->only(['actor', 'action', 'entity']),
        ]);
    }
}
