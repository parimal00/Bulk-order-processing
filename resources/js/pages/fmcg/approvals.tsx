import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { approvals } from '@/lib/fmcg-data';

export default function ApprovalsPage() {
    const selected = approvals[0];

    return (
        <FmcgPageShell title="Approvals">
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
                        {approvals.map((approval) => (
                            <div key={approval.orderNo} className="rounded-xl border border-slate-200 bg-white p-3">
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
                    right={<Button size="sm">Open Full Order</Button>}
                >
                    <div className="space-y-4 text-sm">
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
                            <Button className="bg-emerald-600 hover:bg-emerald-700">Approve</Button>
                            <Button variant="destructive">Reject</Button>
                            <Button variant="outline">Request Changes</Button>
                        </div>
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
