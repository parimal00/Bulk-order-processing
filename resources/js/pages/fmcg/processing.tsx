import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface ProcessingJob {
    id: string;
    rawId: number;
    uploadId: string;
    step: string;
    progress: number;
    status: 'queued' | 'running' | 'retrying' | 'failed' | 'completed';
    elapsed: string;
}

interface ProcessingPageProps {
    jobs?: ProcessingJob[];
}

export default function ProcessingPage({ jobs = [] }: ProcessingPageProps) {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');

    const filteredJobs = jobs.filter((job) => {
        const matchesSearch =
            job.id.toLowerCase().includes(search.toLowerCase()) ||
            job.uploadId.toLowerCase().includes(search.toLowerCase()) ||
            job.step.toLowerCase().includes(search.toLowerCase());

        const matchesStatus = statusFilter
            ? job.status.toLowerCase() === statusFilter.toLowerCase()
            : true;

        return matchesSearch && matchesStatus;
    });

    const handleRefresh = () => {
        router.reload({ only: ['jobs'] });
    };

    const getJobHref = (job: ProcessingJob) => {
        return `/fmcg/uploads/${job.rawId}/validation`;
    };

    return (
        <FmcgPageShell title="Processing Queue">
            <PageHeader
                eyebrow="Execution"
                title="Processing Queue"
                description="Observe async job execution, monitor retries, and isolate failures before downstream systems are impacted."
                actions={[
                    <Button key="refresh" onClick={handleRefresh}>Refresh Queue</Button>
                ]}
            />

            <SectionCard title="Queue Jobs" subtitle="Real-time processing progress by pipeline step">
                <div className="mb-4 grid gap-3 md:grid-cols-3 items-center">
                    <Input
                        placeholder="Search job id, upload id, or step"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                    <Input
                        placeholder="Status (queued, running, completed, failed)"
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                    />
                    <div className="flex justify-end">
                        {(search || statusFilter) && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    setSearch('');
                                    setStatusFilter('');
                                }}
                                className="h-9 px-3 text-xs text-slate-500 hover:text-slate-700"
                            >
                                Clear Filters
                            </Button>
                        )}
                    </div>
                </div>

                <div className="space-y-3">
                    {filteredJobs.map((job) => (
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
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={getJobHref(job)}>View Details</Link>
                                </Button>
                            </div>
                        </div>
                    ))}

                    {filteredJobs.length === 0 && (
                        <p className="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500">
                            No jobs found matching the filters.
                        </p>
                    )}
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
