import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard } from '@/components/fmcg/ui';
import { Input } from '@/components/ui/input';

type AuditRecord = {
    id: number;
    timestamp: string;
    actor: string;
    action: string;
    entity: string;
    details: string;
};

export default function AuditPage({ auditTrail, filters }: { auditTrail: AuditRecord[]; filters: any }) {
    const [actor, setActor] = useState(filters.actor ?? '');
    const [action, setAction] = useState(filters.action ?? '');
    const [entity, setEntity] = useState(filters.entity ?? '');

    // Reactive server-side dynamic query with a 400ms input debounce
    useEffect(() => {
        const delayDebounceFn = setTimeout(() => {
            router.get('/fmcg/audit', 
                { actor, action, entity }, 
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true
                }
            );
        }, 400);

        return () => clearTimeout(delayDebounceFn);
    }, [actor, action, entity]);

    return (
        <FmcgPageShell title="Audit Trail">
            <PageHeader
                eyebrow="Traceability"
                title="Audit Trail Explorer"
                description="Track every high-value action across uploads, approvals, allocations, and sync events with actor-level accountability."
                actions={['Export CSV', 'Export JSON']}
            />

            <SectionCard title="Event Stream" subtitle="System and user actions ordered by timestamp">
                <div className="mb-6 grid gap-3 md:grid-cols-3">
                    <div>
                        <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1.5">Search Actor</label>
                        <Input 
                            placeholder="e.g. System Job, Manual Approver" 
                            value={actor}
                            onChange={(e) => setActor(e.target.value)}
                            className="bg-slate-50/50 hover:bg-slate-50 focus:bg-white transition-colors"
                        />
                    </div>
                    <div>
                        <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1.5">Search Action</label>
                        <Input 
                            placeholder="e.g. Order Approved, Role Updated" 
                            value={action}
                            onChange={(e) => setAction(e.target.value)}
                            className="bg-slate-50/50 hover:bg-slate-50 focus:bg-white transition-colors"
                        />
                    </div>
                    <div>
                        <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-1.5">Search Entity / ID</label>
                        <Input 
                            placeholder="e.g. ORD-X, UPL-Y" 
                            value={entity}
                            onChange={(e) => setEntity(e.target.value)}
                            className="bg-slate-50/50 hover:bg-slate-50 focus:bg-white transition-colors"
                        />
                    </div>
                </div>

                <div className="space-y-3">
                    {auditTrail.length === 0 ? (
                        <div className="text-center py-12 border border-dashed rounded-xl bg-slate-50/30">
                            <p className="text-sm text-slate-500 font-medium">No matching audit events found.</p>
                            <p className="text-xs text-slate-400 mt-1">Try widening your search terms or filters.</p>
                        </div>
                    ) : (
                        auditTrail.map((event) => (
                            <div key={event.id ?? `${event.timestamp}-${event.entity}`} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow">
                                <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400">
                                    <span>{event.timestamp}</span>
                                    <span className="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-xs font-semibold text-slate-600">
                                        {event.entity}
                                    </span>
                                </div>
                                <h4 className="mt-2 text-base font-bold text-slate-800">{event.action}</h4>
                                <div className="mt-1 flex items-center gap-2 text-sm text-slate-600">
                                    <span className="font-semibold text-slate-700">{event.actor}</span>
                                </div>
                                <p className="mt-2 text-sm text-slate-600 bg-slate-50 border border-slate-100 p-2.5 rounded-lg leading-relaxed">
                                    {event.details}
                                </p>
                            </div>
                        ))
                    )}
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
