<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamSettingsController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get()->map(function ($user) {
            // Get actual actions count today from audit logs!
            $actionsCount = AuditLog::where('user_id', $user->id)
                ->where('created_at', '>=', now()->startOfDay())
                ->count();

            // Set module labels based on roles
            $modules = 'Uploads, Validation';
            if ($user->role === 'admin') {
                $modules = 'Settings, Integrations, Users, Approvals, Orders';
            } elseif ($user->role === 'approver') {
                $modules = 'Approvals, Orders, Reconciliation';
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'modules' => $modules,
                'activity' => "{$actionsCount} actions today",
            ];
        });

        return Inertia::render('fmcg/settings/users-roles', [
            'users' => $users
        ]);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('manage-users');

        $request->validate([
            'role' => 'required|string|in:ops,approver,admin',
        ]);

        $oldRole = $user->role;
        $newRole = $request->input('role');

        if ($oldRole !== $newRole) {
            $user->update(['role' => $newRole]);

            AuditLog::log('user_role_updated', User::class, $user->id, [
                'updated_user_id' => $user->id,
                'updated_user_name' => $user->name,
                'old_role' => $oldRole,
                'new_role' => $newRole,
            ]);
        }

        return back()->with('success', "Role for {$user->name} has been updated to {$newRole}.");
    }
}
