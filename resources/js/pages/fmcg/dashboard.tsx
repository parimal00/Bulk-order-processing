import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { KpiGrid, MiniBars, PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import type { Kpi } from '@/lib/fmcg-data';

type DashboardRisk = {
    title: string;
    detail: string;
    tone: 'critical' | 'warning' | 'notice' | 'ok';
};

type DashboardPageProps = {
    kpis: Kpi[];
    throughput: Array<{ hour: string; lines: number }>;
    failures: Array<{ day: string; count: number }>;
    atRisk: {
        sync: DashboardRisk;
        approvals: DashboardRisk;
        inventory: DashboardRisk;
    };
    recentUploads: Array<{
        id: string;
        customer: string;
        rows: number;
        validPercent: number;
        status: string;
    }>;
    processingJobs: Array<{
        id: string;
        uploadId: string;
        step: string;
        progress: number;
        status: string;
        elapsed: string;
    }>;
    headerActions: Array<{ label: string; href: string }>;
};

export default function FmcgDashboardPage({
    kpis,
    throughput,
    failures,
    atRisk,
    recentUploads,
    processingJobs,
    headerActions,
}: DashboardPageProps) {
    const riskToneClassMap: Record<DashboardRisk['tone'], string> = {
        critical: 'border-red-100 bg-red-50/70 text-red-700',
        warning: 'border-amber-100 bg-amber-50/70 text-amber-700',
        notice: 'border-cyan-100 bg-cyan-50/70 text-cyan-700',
        ok: 'border-emerald-100 bg-emerald-50/70 text-emerald-700',
    };

    return (
        <FmcgPageShell title="FMCG Dashboard">
            <PageHeader
                eyebrow="FMCG Control Tower"
                title="Bulk Order Operations"
                description="Monitor upload health, processing throughput, approvals backlog, and sync reliability across wholesale order flows."
                actions={headerActions}
            />

            <KpiGrid items={kpis} />

            <div className="grid gap-4 xl:grid-cols-3">
                <SectionCard
                    title="Hourly Throughput"
                    subtitle="Line items processed in the current operating day"
                    right={<Button variant="outline" size="sm">Last 24h</Button>}
                >
                    <MiniBars items={throughput} keyLabel="hour" keyValue="lines" />
                </SectionCard>

                <SectionCard
                    title="Weekly Failure Trend"
                    subtitle="Validation, processing, and sync exception counts"
                    right={<Button variant="outline" size="sm">Last 7d</Button>}
                >
                    <MiniBars items={failures} keyLabel="day" keyValue="count" />
                </SectionCard>

                <SectionCard title="At Risk" subtitle="Items that may breach SLA or need manual action">
                    <div className="space-y-3 text-sm">
                        <div className={`rounded-xl border p-3 ${riskToneClassMap[atRisk.sync.tone]}`}>
                            <p className="font-medium">{atRisk.sync.title}</p>
                            <p className="text-current/90">{atRisk.sync.detail}</p>
                        </div>
                        <div className={`rounded-xl border p-3 ${riskToneClassMap[atRisk.approvals.tone]}`}>
                            <p className="font-medium">{atRisk.approvals.title}</p>
                            <p className="text-current/90">{atRisk.approvals.detail}</p>
                        </div>
                        <div className={`rounded-xl border p-3 ${riskToneClassMap[atRisk.inventory.tone]}`}>
                            <p className="font-medium">{atRisk.inventory.title}</p>
                            <p className="text-current/90">{atRisk.inventory.detail}</p>
                        </div>
                    </div>
                </SectionCard>
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
                <SectionCard title="Recent Uploads" subtitle="Latest ingestion batches">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[620px] text-left text-sm">
                            <thead className="text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="pb-2">Upload</th>
                                    <th className="pb-2">Customer</th>
                                    <th className="pb-2">Rows</th>
                                    <th className="pb-2">Valid</th>
                                    <th className="pb-2">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {recentUploads.slice(0, 4).map((upload) => (
                                    <tr key={upload.id}>
                                        <td className="py-2 font-medium text-slate-800">{upload.id}</td>
                                        <td className="py-2 text-slate-700">{upload.customer}</td>
                                        <td className="py-2 text-slate-700">{upload.rows.toLocaleString()}</td>
                                        <td className="py-2 text-slate-700">{upload.validPercent}%</td>
                                        <td className="py-2"><StatusPill value={upload.status} /></td>
                                    </tr>
                                ))}
                                {recentUploads.length === 0 && (
                                    <tr>
                                        <td className="py-6 text-center text-slate-500" colSpan={5}>
                                            No uploads yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </SectionCard>

                <SectionCard title="Live Processing Jobs" subtitle="Queue execution status">
                    <div className="space-y-3">
                        {processingJobs.map((job) => (
                            <div key={job.id} className="rounded-xl border border-slate-200 bg-white p-3">
                                <div className="mb-2 flex items-center justify-between text-sm">
                                    <p className="font-medium text-slate-800">{job.id} - {job.step}</p>
                                    <StatusPill value={job.status} />
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div className="h-full rounded-full bg-[#0f718f]" style={{ width: `${job.progress}%` }} />
                                </div>
                                <div className="mt-2 flex justify-between text-xs text-slate-500">
                                    <span>{job.uploadId}</span>
                                    <span>{job.elapsed}</span>
                                </div>
                            </div>
                        ))}
                        {processingJobs.length === 0 && (
                            <p className="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                                No active processing jobs right now.
                            </p>
                        )}
                    </div>
                </SectionCard>
            </div>
        </FmcgPageShell>
    );
}

FmcgDashboardPage.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
    ],
};
