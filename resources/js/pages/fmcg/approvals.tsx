import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';

type ApprovalOrder = {
    id: number;
    orderNo: string;
    customer: string;
    amount: string;
    submittedAt: string;
    risk: string;
    margin: string;
    reasons: string[];
};

export default function ApprovalsPage({ orders }: { orders: ApprovalOrder[] }) {
    const { auth } = usePage<any>().props;
    const isOps = auth?.user?.role === 'ops';
    const [selectedId, setSelectedId] = useState<number | null>(orders[0]?.id ?? null);

    const selected = orders.find(o => o.id === selectedId) ?? orders[0];

    const handleApprove = () => {
        if (!selected || isOps) return;
        router.post(`/fmcg/approvals/${selected.id}/approve`, {}, { preserveScroll: true });
    };

    const handleReject = () => {
        if (!selected || isOps) return;
        router.post(`/fmcg/approvals/${selected.id}/reject`, {}, { preserveScroll: true });
    };

    return (
        <FmcgPageShell title="Approvals">
            {isOps && (
                <div className="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-sm">
                    <span className="font-semibold">⚠️ Read-Only Operations Access:</span> Your account is assigned the <strong className="font-semibold">Ops</strong> role. Only <strong className="font-semibold">Approvers</strong> and <strong className="font-semibold">Admins</strong> can approve or reject policy-flagged orders.
                </div>
            )}

            <PageHeader
                eyebrow="Risk Controls"
                title="Approval Inbox"
                description="Review policy-triggered orders before release. Prioritize by risk score, margin impact, and inventory shortfall."
                actions={['Approve Selected', 'Reject Selected', 'Request Revision']}
            />

            <div className="grid gap-4 xl:grid-cols-5">
                <SectionCard
                    className="xl:col-span-2"
                    title="Pending Queue"
                    subtitle="Orders requiring manual decision"
                    right={<StatusPill value="high" />}
                >
                    <div className="space-y-3">
                        {orders.length === 0 && (
                            <p className="text-sm text-slate-500">No orders pending approval.</p>
                        )}
                        {orders.map((approval) => (
                            <div
                                key={approval.id}
                                onClick={() => setSelectedId(approval.id)}
                                className={`cursor-pointer rounded-xl border p-3 transition-colors ${selected?.id === approval.id ? 'border-cyan-500 bg-cyan-50' : 'border-slate-200 bg-white hover:border-slate-300'}`}
                            >
                                <div className="flex items-center justify-between">
                                    <p className="font-semibold text-slate-800">{approval.orderNo}</p>
                                    <StatusPill value={approval.risk} />
                                </div>
                                <p className="mt-1 text-sm text-slate-700">{approval.customer}</p>
                                <p className="text-xs text-slate-500">{approval.amount} • {approval.submittedAt}</p>
                            </div>
                        ))}
                    </div>
                </SectionCard>

                <SectionCard
                    className="xl:col-span-3"
                    title="Order Review"
                    subtitle="Decision context and policy flags"
                    right={selected ? <Button size="sm" onClick={() => router.visit(`/fmcg/orders/${selected.id}`)}>Open Full Order</Button> : null}
                >
                    <div className="space-y-4 text-sm">
                        {selected ? (
                            <>
                                <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <p className="text-xs uppercase tracking-wide text-slate-500">Order</p>
                                    <p className="font-semibold text-slate-800">{selected.orderNo}</p>
                                    <p className="text-slate-700">{selected.customer}</p>
                                </div>

                                <div className="grid gap-3 md:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 p-3">
                                        <p className="text-xs uppercase tracking-wide text-slate-500">Order Total</p>
                                        <p className="text-xl font-semibold text-slate-800">{selected.amount}</p>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-3">
                                        <p className="text-xs uppercase tracking-wide text-slate-500">Projected Margin</p>
                                        <p className="text-xl font-semibold text-slate-800">{selected.margin}</p>
                                    </div>
                                </div>

                                <div>
                                    <p className="mb-2 text-xs uppercase tracking-wide text-slate-500">Reason Flags</p>
                                    <div className="flex flex-wrap gap-2">
                                        {selected.reasons.map((reason) => (
                                            <span key={reason} className="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs text-amber-700">
                                                {reason}
                                            </span>
                                        ))}
                                    </div>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <Button onClick={handleApprove} disabled={isOps} className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">Approve</Button>
                                    <Button onClick={handleReject} disabled={isOps} variant="destructive" className="disabled:opacity-50 disabled:cursor-not-allowed">Reject</Button>
                                    <Button variant="outline" disabled={isOps} className="disabled:opacity-50 disabled:cursor-not-allowed">Request Changes</Button>
                                </div>
                            </>
                        ) : (
                            <div className="flex h-40 items-center justify-center rounded-xl border border-dashed border-slate-300">
                                <p className="text-sm text-slate-500">Select an order from the queue to review.</p>
                            </div>
                        )}
                    </div>
                </SectionCard>
            </div>
        </FmcgPageShell>
    );
}

ApprovalsPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Approvals', href: '/fmcg/approvals' },
    ],
};
