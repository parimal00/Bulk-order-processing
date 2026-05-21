import { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type UserRecord = {
    id: number;
    name: string;
    email: string;
    role: string;
    modules: string;
    activity: string;
};

export default function UsersRolesPage({ users }: { users: UserRecord[] }) {
    const { auth } = usePage<any>().props;
    const isSystemAdmin = auth?.user?.role === 'admin';

    const [selectedUser, setSelectedUser] = useState<UserRecord | null>(users[0] ?? null);
    const [role, setRole] = useState(selectedUser?.role ?? 'ops');

    // Keep inputs in sync when clicking a team member
    useEffect(() => {
        if (selectedUser) {
            setRole(selectedUser.role);
        }
    }, [selectedUser]);

    const handleSave = () => {
        if (!selectedUser || !isSystemAdmin) return;
        
        router.put(`/fmcg/settings/users-roles/${selectedUser.id}`, 
            { role }, 
            { 
                preserveScroll: true,
                onSuccess: () => {
                    // Update local selected state with new value
                    const updated = users.find(u => u.id === selectedUser.id);
                    if (updated) {
                        setSelectedUser(updated);
                    }
                }
            }
        );
    };

    return (
        <FmcgPageShell title="Users and Roles">
            {!isSystemAdmin && (
                <div className="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-sm">
                    <span className="font-semibold">⚠️ Administrative Actions Restricted:</span> Your account role does not have <strong className="font-semibold">Admin</strong> privileges. Role modification is locked.
                </div>
            )}

            <PageHeader
                eyebrow="Settings"
                title="Users and Role Matrix"
                description="Assign responsibilities across Ops, Approver, and Admin roles with module-level access boundaries."
                actions={['Invite User', 'Export Access Matrix']}
            />

            <div className="grid gap-4 xl:grid-cols-3">
                <SectionCard 
                    className="xl:col-span-1" 
                    title="Role Assignment" 
                    subtitle={selectedUser ? `Adjust permissions for ${selectedUser.name}` : "Select a team member to edit"}
                >
                    {selectedUser ? (
                        <div className="space-y-4">
                            <div>
                                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1.5">User Email</label>
                                <Input value={selectedUser.email} disabled className="bg-slate-100 cursor-not-allowed" />
                            </div>
                            <div>
                                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1.5">Security Role</label>
                                <select 
                                    value={role} 
                                    onChange={(e) => setRole(e.target.value)}
                                    disabled={!isSystemAdmin}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="ops">Ops (Uploads, Validation)</option>
                                    <option value="approver">Approver (Orders, Approvals)</option>
                                    <option value="admin">Admin (All Modules + RBAC Settings)</option>
                                </select>
                            </div>
                            <div>
                                <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1.5">Granted Modules</label>
                                <Input 
                                    value={role === 'admin' ? 'Settings, Integrations, Users, Approvals, Orders' : (role === 'approver' ? 'Approvals, Orders, Reconciliation' : 'Uploads, Validation')} 
                                    disabled 
                                    className="bg-slate-50 cursor-not-allowed text-slate-500" 
                                />
                            </div>
                            <Button 
                                className="w-full disabled:opacity-50 disabled:cursor-not-allowed bg-cyan-600 hover:bg-cyan-700" 
                                onClick={handleSave}
                                disabled={!isSystemAdmin || selectedUser.role === role}
                            >
                                {!isSystemAdmin ? "Admin Privileges Required" : (selectedUser.role === role ? "Role matches current" : "Save Changes")}
                            </Button>
                        </div>
                    ) : (
                        <p className="text-sm text-slate-500 italic text-center py-6">Select a team member to manage access.</p>
                    )}
                </SectionCard>

                <SectionCard className="xl:col-span-2" title="Current Team" subtitle="Access and daily activity summary (Click a row to edit)">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[500px] text-left text-sm cursor-pointer">
                            <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="pb-3">Name</th>
                                    <th className="pb-3">Role</th>
                                    <th className="pb-3">Modules</th>
                                    <th className="pb-3">Activity Today</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {users.map((user) => (
                                    <tr 
                                        key={user.id} 
                                        onClick={() => setSelectedUser(user)}
                                        className={`hover:bg-slate-50/50 transition-colors ${selectedUser?.id === user.id ? 'bg-cyan-50/40 font-semibold' : ''}`}
                                    >
                                        <td className="py-3.5 font-medium text-slate-800">{user.name}</td>
                                        <td className="py-3.5 text-slate-700 uppercase font-mono text-xs">{user.role}</td>
                                        <td className="py-3.5 text-slate-700 text-xs">{user.modules}</td>
                                        <td className="py-3.5 text-slate-700 text-xs">{user.activity}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </SectionCard>
            </div>
        </FmcgPageShell>
    );
}

UsersRolesPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Settings', href: '/fmcg/settings/users-roles' },
        { title: 'Users and Roles', href: '/fmcg/settings/users-roles' },
    ],
};
