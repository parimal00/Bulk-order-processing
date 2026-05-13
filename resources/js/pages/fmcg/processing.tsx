import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { processingJobs } from '@/lib/fmcg-data';

export default function ProcessingPage() {
    return (
        <FmcgPageShell title="Processing Queue">
            <PageHeader
                eyebrow="Execution"
                title="Processing Queue"
                description="Observe async job execution, monitor retries, and isolate failures before downstream systems are impacted."
                actions={['Pause Queue', 'Retry Failed Jobs', 'Open Dead Letter Queue']}
            />

            <SectionCard title="Queue Jobs" subtitle="Real-time processing progress by pipeline step">
                <div className="space-y-3">
                    {processingJobs.map((job) => (
                        <div key={job.id} className="rounded-xl border border-slate-200 bg-white p-4">
                            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p className="text-sm font-semibold text-slate-800">{job.id}</p>
                                    <p className="text-xs text-slate-500">{job.uploadId} - {job.step}</p>
                                </div>
                                <StatusPill value={job.status} />
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div className="h-full rounded-full bg-[#0f718f]" style={{ width: `${job.progress}%` }} />
                            </div>
                            <div className="mt-3 flex items-center justify-between text-xs text-slate-500">
                                <span>{job.progress}% complete</span>
                                <span>{job.elapsed}</span>
                            </div>
                            <div className="mt-3 flex flex-wrap gap-2">
                                <Button size="sm" variant="outline">View Logs</Button>
                                <Button size="sm" variant="outline">Retry</Button>
                                <Button size="sm" variant="outline">Escalate</Button>
                            </div>
                        </div>
                    ))}
                </div>
            </SectionCard>
        </FmcgPageShell>
    );
}

ProcessingPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Processing', href: '/fmcg/processing' },
    ],
};
