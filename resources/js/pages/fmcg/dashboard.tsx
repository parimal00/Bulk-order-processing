import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { KpiGrid, MiniBars, PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { chartFailures, chartThroughput, dashboardKpis, processingJobs, uploads } from '@/lib/fmcg-data';

export default function FmcgDashboardPage() {
    return (
        <FmcgPageShell title="FMCG Dashboard">
            <PageHeader
                eyebrow="FMCG Control Tower"
                title="Bulk Order Operations"
                description="Monitor upload health, processing throughput, approvals backlog, and sync reliability across wholesale order flows."
                actions={['New Upload', 'Review Exceptions', 'Run Reconciliation']}
            />

            <KpiGrid items={dashboardKpis} />

            <div className="grid gap-4 xl:grid-cols-3">
                <SectionCard
                    title="Hourly Throughput"
                    subtitle="Line items processed in the current operating day"
                    right={<Button variant="outline" size="sm">Last 24h</Button>}
                >
                    <MiniBars items={chartThroughput} keyLabel="hour" keyValue="lines" />
                </SectionCard>

                <SectionCard
                    title="Weekly Failure Trend"
                    subtitle="Validation, processing, and sync exception counts"
                    right={<Button variant="outline" size="sm">Last 7d</Button>}
                >
                    <MiniBars items={chartFailures} keyLabel="day" keyValue="count" />
                </SectionCard>

                <SectionCard title="At Risk" subtitle="Items that may breach SLA or need manual action">
                    <div className="space-y-3 text-sm">
                        <div className="rounded-xl border border-red-100 bg-red-50/70 p-3">
                            <p className="font-medium text-red-700">ERP sync stalled: SO-55015</p>
                            <p className="text-red-600">3 retries exhausted. Last attempt 10:57.</p>
                        </div>
                        <div className="rounded-xl border border-amber-100 bg-amber-50/70 p-3">
                            <p className="font-medium text-amber-700">Approval queue above threshold</p>
                            <p className="text-amber-600">16 pending approvals, SLA target is 10.</p>
                        </div>
                        <div className="rounded-xl border border-cyan-100 bg-cyan-50/70 p-3">
                            <p className="font-medium text-cyan-700">Inventory split risk</p>
                            <p className="text-cyan-600">2 uploads with backorder ratio above 25%.</p>
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
                                {uploads.slice(0, 4).map((upload) => (
                                    <tr key={upload.id}>
                                        <td className="py-2 font-medium text-slate-800">{upload.id}</td>
                                        <td className="py-2 text-slate-700">{upload.customer}</td>
                                        <td className="py-2 text-slate-700">{upload.rows.toLocaleString()}</td>
                                        <td className="py-2 text-slate-700">{upload.validPercent}%</td>
                                        <td className="py-2"><StatusPill value={upload.status} /></td>
                                    </tr>
                                ))}
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
