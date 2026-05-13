import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { userRoles } from '@/lib/fmcg-data';

export default function UsersRolesPage() {
    return (
        <FmcgPageShell title="Users and Roles">
            <PageHeader
                eyebrow="Settings"
                title="Users and Role Matrix"
                description="Assign responsibilities across Ops, Approver, and Admin roles with module-level access boundaries."
                actions={['Invite User', 'Export Access Matrix']}
            />

            <div className="grid gap-4 xl:grid-cols-3">
                <SectionCard className="xl:col-span-1" title="Role Assignment" subtitle="Add or update user permissions">
                    <div className="space-y-3">
                        <Input defaultValue="ops.user@example.com" />
                        <Input defaultValue="Ops" />
                        <Input defaultValue="Uploads, Processing, Reconciliation" />
                        <Button className="w-full">Save Access</Button>
                    </div>
                </SectionCard>

                <SectionCard className="xl:col-span-2" title="Current Team" subtitle="Access and daily activity summary">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[620px] text-left text-sm">
                            <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="pb-3">Name</th>
                                    <th className="pb-3">Role</th>
                                    <th className="pb-3">Modules</th>
                                    <th className="pb-3">Activity</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {userRoles.map((user) => (
                                    <tr key={user.name}>
                                        <td className="py-3 font-medium text-slate-800">{user.name}</td>
                                        <td className="py-3 text-slate-700">{user.role}</td>
                                        <td className="py-3 text-slate-700">{user.modules}</td>
                                        <td className="py-3 text-slate-700">{user.activity}</td>
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
