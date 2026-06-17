import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/react';
import { Activity, AlertTriangle, CheckCircle2, Clock, Pause, Play, RefreshCw } from 'lucide-react';
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

type HealthStatus = {
    provider: string;
    status: 'Active' | 'Paused' | 'Tripped';
    failures: number;
    cooldownRemaining: number;
};

export default function ReconciliationPage({
    rows,
    filters,
    healthStatus,
}: {
    rows: ReconciliationRow[];
    filters: ReconciliationFilters;
    healthStatus: HealthStatus;
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

    const handleCircuitAction = (action: 'pause' | 'resume' | 'reset') => {
        router.post(
            `/fmcg/reconciliation/circuit-breaker/${healthStatus.provider}/update`,
            { action },
            { preserveScroll: true },
        );
    };

    return (
        <FmcgPageShell title="Reconciliation">
            <PageHeader
                eyebrow="External Sync"
                title="Reconciliation Center"
                description="Compare internal order state against ERP responses, resolve mismatches, and replay outbound integrations safely."
                actions={['Export Incident Report']}
            />

            {/* Circuit Breaker Status Panel */}
            <div className="mb-6">
                <SectionCard
                    title="Integration Health Dashboard"
                    subtitle="Monitor outbound sync connection stability and manage circuit breaker thresholds."
                >
                    <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div className="flex flex-wrap items-center gap-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-slate-100 p-3 text-slate-600">
                                    <Activity className="h-6 w-6" />
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Provider</div>
                                    <div className="text-sm font-bold text-slate-800">{healthStatus.provider}</div>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <div className={`rounded-lg p-3 ${
                                    healthStatus.status === 'Active' ? 'bg-emerald-50 text-emerald-600' :
                                    healthStatus.status === 'Paused' ? 'bg-amber-50 text-amber-600' :
                                    'bg-rose-50 text-rose-600'
                                }`}>
                                    {healthStatus.status === 'Active' && <CheckCircle2 className="h-6 w-6" />}
                                    {healthStatus.status === 'Paused' && <Pause className="h-6 w-6" />}
                                    {healthStatus.status === 'Tripped' && <AlertTriangle className="h-6 w-6" />}
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Circuit State</div>
                                    <div>
                                        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                            healthStatus.status === 'Active' ? 'bg-emerald-100 text-emerald-800' :
                                            healthStatus.status === 'Paused' ? 'bg-amber-100 text-amber-800' :
                                            'bg-rose-100 text-rose-800'
                                        }`}>
                                            {healthStatus.status}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-slate-100 p-3 text-slate-600">
                                    <AlertTriangle className="h-6 w-6" />
                                </div>
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Consecutive Failures</div>
                                    <div className="text-sm font-bold text-slate-800">{healthStatus.failures} / 5</div>
                                </div>
                            </div>

                            {healthStatus.status === 'Tripped' && (
                                <div className="flex items-center gap-3 animate-pulse">
                                    <div className="rounded-lg bg-rose-50 p-3 text-rose-600">
                                        <Clock className="h-6 w-6" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Cooldown Timer</div>
                                        <div className="text-sm font-bold text-rose-700">
                                            {healthStatus.cooldownRemaining}s remaining
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="flex items-center gap-3 border-t pt-4 md:border-t-0 md:pt-0">
                            {healthStatus.status === 'Active' && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleCircuitAction('pause')}
                                    className="border-amber-200 text-amber-700 hover:bg-amber-50 hover:text-amber-800"
                                >
                                    <Pause className="mr-1.5 h-4 w-4" /> Pause Outbound
                                </Button>
                            )}

                            {healthStatus.status === 'Paused' && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleCircuitAction('resume')}
                                    className="border-emerald-200 text-emerald-700 hover:bg-emerald-50 hover:text-emerald-800"
                                >
                                    <Play className="mr-1.5 h-4 w-4" /> Resume Outbound
                                </Button>
                            )}

                            {healthStatus.status === 'Tripped' && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handleCircuitAction('reset')}
                                    className="border-rose-200 text-rose-700 hover:bg-rose-50 hover:text-rose-800"
                                >
                                    <RefreshCw className="mr-1.5 h-4 w-4" /> Reset Circuit
                                </Button>
                            )}
                        </div>
                    </div>
                </SectionCard>
            </div>

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
