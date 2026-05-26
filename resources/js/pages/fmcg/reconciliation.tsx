import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type ReconciliationRow = {
    id: number;
    orderId: number;
    orderNo: string;
    customer: string;
    provider: string;
    internalState: string;
    externalState: string;
    syncStatus: string;
    mismatch: string;
    attempts: number;
    lastError: string | null;
    externalReference: string | null;
    lastSync: string;
    lastCallbackAt: string | null;
};

type ReconciliationFilters = {
    provider?: string;
    status?: string;
    order?: string;
};

export default function ReconciliationPage({
    rows,
    filters,
}: {
    rows: ReconciliationRow[];
    filters: ReconciliationFilters;
}) {
    const [provider, setProvider] = useState(filters.provider ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [order, setOrder] = useState(filters.order ?? '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                '/fmcg/reconciliation',
                { provider, status, order },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [provider, status, order]);

    const handleRetry = (integrationId: number) => {
        router.post(`/fmcg/reconciliation/${integrationId}/retry`, {}, { preserveScroll: true });
    };

    return (
        <FmcgPageShell title="Reconciliation">
            <PageHeader
                eyebrow="External Sync"
                title="Reconciliation Center"
                description="Compare internal order state against ERP responses, resolve mismatches, and replay outbound integrations safely."
                actions={['Export Incident Report']}
            />

            <SectionCard title="Mismatch Registry" subtitle="Internal vs external state comparison">
                <div className="mb-4 grid gap-3 md:grid-cols-3">
                    <Input placeholder="Provider (e.g. erp_stub)" value={provider} onChange={(e) => setProvider(e.target.value)} />
                    <Input placeholder="Sync status (pending/sent/acknowledged/failed)" value={status} onChange={(e) => setStatus(e.target.value)} />
                    <Input placeholder="Order number" value={order} onChange={(e) => setOrder(e.target.value)} />
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[860px] text-left text-sm">
                        <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="pb-3">Order</th>
                                <th className="pb-3">Provider</th>
                                <th className="pb-3">Internal State</th>
                                <th className="pb-3">External State</th>
                                <th className="pb-3">Sync</th>
                                <th className="pb-3">Mismatch</th>
                                <th className="pb-3">Last Sync</th>
                                <th className="pb-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rows.map((row) => (
                                <tr key={row.id}>
                                    <td className="py-3 font-medium text-slate-800">{row.orderNo}</td>
                                    <td className="py-3 text-slate-700">{row.provider}</td>
                                    <td className="py-3 text-slate-700">{row.internalState}</td>
                                    <td className="py-3 text-slate-700">{row.externalState}</td>
                                    <td className="py-3"><StatusPill value={row.syncStatus} /></td>
                                    <td className="py-3"><StatusPill value={row.mismatch} /></td>
                                    <td className="py-3 text-slate-700">{row.lastSync}</td>
                                    <td className="py-3 text-right">
                                        <Button size="sm" variant="outline" onClick={() => handleRetry(row.id)}>Retry</Button>
                                    </td>
                                </tr>
                            ))}
                            {rows.length === 0 && (
                                <tr>
                                    <td className="py-8 text-center text-slate-500" colSpan={8}>
                                        No reconciliation records found for current filters.
                                    </td>
                                </tr>
                            )}
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
