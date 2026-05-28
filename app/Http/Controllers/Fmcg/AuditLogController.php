<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Http\Resources\Fmcg\AuditLogResource;
use App\Models\AuditLog;
use App\Models\BulkUpload;
use App\Models\Order;
use App\Models\OrderIntegration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest('id');

        // Generic text filters
        if ($request->filled('actor')) {
            $actor = trim((string) $request->input('actor'));
            $query->where(function ($q) use ($actor) {
                $q->whereHas('user', function ($uq) use ($actor) {
                    $uq->where('name', 'like', "%{$actor}%")
                        ->orWhere('email', 'like', "%{$actor}%");
                })->orWhere(function ($sq) use ($actor) {
                    if (str_contains(strtolower('system job'), strtolower($actor))) {
                        $sq->whereNull('user_id');
                    }
                });
            });
        }

        if ($request->filled('action')) {
            $action = trim((string) $request->input('action'));
            $query->where('action', 'like', "%{$action}%");
        }

        if ($request->filled('entity')) {
            $entity = trim((string) $request->input('entity'));
            $query->where(function ($q) use ($entity) {
                $q->where('entity_type', 'like', "%{$entity}%")
                    ->orWhere('details', 'like', "%{$entity}%");
            });
        }

        // Structured Week 3 filters
        if ($request->filled('order')) {
            $order = trim((string) $request->input('order'));
            $query->where(function ($q) use ($order) {
                $q->where(function ($orderQuery) use ($order) {
                    $orderQuery->where('entity_type', Order::class)
                        ->where(function ($inner) use ($order) {
                            if (is_numeric($order)) {
                                $inner->where('entity_id', (int) $order);
                            }
                            $inner->orWhere('details', 'like', "%{$order}%");
                        });
                })->orWhere(function ($integrationQuery) use ($order) {
                    $integrationQuery->where('entity_type', OrderIntegration::class)
                        ->where('details', 'like', "%{$order}%");
                })->orWhere('details', 'like', "%{$order}%");
            });
        }

        if ($request->filled('upload')) {
            $upload = trim((string) $request->input('upload'));
            $uploadId = null;
            if (preg_match('/(\d+)$/', $upload, $matches)) {
                $uploadId = (int) $matches[1];
            }

            $query->where(function ($q) use ($upload, $uploadId) {
                $q->where('entity_type', BulkUpload::class)
                    ->where(function ($inner) use ($upload, $uploadId) {
                        if ($uploadId !== null) {
                            $inner->where('entity_id', $uploadId)
                                ->orWhere('details', 'like', "%\"upload_id\":{$uploadId}%");
                        }
                        $inner->orWhere('details', 'like', "%{$upload}%");
                    })
                    ->orWhere('details', 'like', "%{$upload}%");
            });
        }

        if ($request->filled('user')) {
            $user = trim((string) $request->input('user'));
            $matchedUserIds = User::query()
                ->where('name', 'like', "%{$user}%")
                ->orWhere('email', 'like', "%{$user}%")
                ->pluck('id')
                ->all();

            $query->where(function ($q) use ($user, $matchedUserIds) {
                $q->whereHas('user', function ($uq) use ($user) {
                    $uq->where('name', 'like', "%{$user}%")
                        ->orWhere('email', 'like', "%{$user}%");
                })
                    ->orWhere(function ($entityUserQuery) use ($user) {
                        $entityUserQuery->where('entity_type', User::class)
                            ->where('details', 'like', "%{$user}%");
                    })
                    ->orWhere('details', 'like', "%{$user}%")
                    ->orWhere(function ($entityUserQuery) use ($matchedUserIds) {
                        if (count($matchedUserIds) === 0) {
                            $entityUserQuery->whereRaw('1 = 0');

                            return;
                        }
                        $entityUserQuery->where('entity_type', User::class)
                            ->whereIn('entity_id', $matchedUserIds);
                    });
            });
        }

        if ($request->filled('from')) {
            try {
                $from = Carbon::parse((string) $request->input('from'))->startOfDay();
                $query->where('created_at', '>=', $from);
            } catch (\Throwable) {
                // Ignore invalid date input and keep query resilient.
            }
        }

        if ($request->filled('to')) {
            try {
                $to = Carbon::parse((string) $request->input('to'))->endOfDay();
                $query->where('created_at', '<=', $to);
            } catch (\Throwable) {
                // Ignore invalid date input and keep query resilient.
            }
        }

        $logs = $query->take(100)->get();

        return Inertia::render('fmcg/audit', [
            'auditTrail' => AuditLogResource::collection($logs)->resolve(),
            'filters' => $request->only(['actor', 'action', 'entity', 'order', 'upload', 'user', 'from', 'to']),
        ]);
    }
}
