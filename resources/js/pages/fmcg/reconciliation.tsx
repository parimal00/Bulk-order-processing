import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { reconciliationRows } from '@/lib/fmcg-data';

export default function ReconciliationPage() {
    return (
        <FmcgPageShell title="Reconciliation">
            <PageHeader
                eyebrow="External Sync"
                title="Reconciliation Center"
                description="Compare internal order state against ERP responses, resolve mismatches, and replay outbound integrations safely."
                actions={['Retry Selected', 'Mark Resolved', 'Export Incident Report']}
            />

            <SectionCard title="Mismatch Registry" subtitle="Internal vs external state comparison">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[860px] text-left text-sm">
                        <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="pb-3">Order</th>
                                <th className="pb-3">Internal State</th>
                                <th className="pb-3">External State</th>
                                <th className="pb-3">Mismatch</th>
                                <th className="pb-3">Last Sync</th>
                                <th className="pb-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {reconciliationRows.map((row) => (
                                <tr key={row.orderNo}>
                                    <td className="py-3 font-medium text-slate-800">{row.orderNo}</td>
                                    <td className="py-3 text-slate-700">{row.internalState}</td>
                                    <td className="py-3 text-slate-700">{row.externalState}</td>
                                    <td className="py-3"><StatusPill value={row.mismatch} /></td>
                                    <td className="py-3 text-slate-700">{row.lastSync}</td>
                                    <td className="py-3 text-right">
                                        <Button size="sm" variant="outline">Retry</Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </FmcgPageShell>
    );
}

ReconciliationPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Reconciliation', href: '/fmcg/reconciliation' },
    ],
};
