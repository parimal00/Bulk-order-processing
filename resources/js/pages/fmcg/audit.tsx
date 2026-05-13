import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard } from '@/components/fmcg/ui';
import { Input } from '@/components/ui/input';
import { auditTrail } from '@/lib/fmcg-data';

export default function AuditPage() {
    return (
        <FmcgPageShell title="Audit Trail">
            <PageHeader
                eyebrow="Traceability"
                title="Audit Trail Explorer"
                description="Track every high-value action across uploads, approvals, allocations, and sync events with actor-level accountability."
                actions={['Export CSV', 'Export JSON']}
            />

            <SectionCard title="Event Stream" subtitle="System and user actions ordered by timestamp">
                <div className="mb-4 grid gap-3 md:grid-cols-4">
                    <Input placeholder="Actor" />
                    <Input placeholder="Action" />
                    <Input placeholder="Entity" />
                    <Input placeholder="Date range" />
                </div>

                <div className="space-y-3">
                    {auditTrail.map((event) => (
                        <div key={`${event.timestamp}-${event.entity}`} className="rounded-xl border border-slate-200 bg-white p-3">
                            <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                                <span>{event.timestamp}</span>
                                <span className="rounded-full bg-slate-100 px-2 py-1 font-mono">{event.entity}</span>
                            </div>
                            <p className="mt-1 text-sm font-semibold text-slate-800">{event.action}</p>
                            <p className="text-sm text-slate-700">{event.actor}</p>
                            <p className="text-sm text-slate-600">{event.details}</p>
                        </div>
                    ))}
                </div>
            </SectionCard>
        </FmcgPageShell>
    );
}

AuditPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Audit Trail', href: '/fmcg/audit' },
    ],
};
