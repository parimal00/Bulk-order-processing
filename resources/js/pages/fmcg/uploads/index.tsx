import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface UploadRecord {
    id: string;
    rawId: number;
    customer: string;
    source: string;
    rows: number;
    validPercent: number;
    status: string;
    createdAt: string;
    owner: string;
}

interface UploadsIndexPageProps {
    uploads?: UploadRecord[];
}

export default function UploadsIndexPage({ uploads = [] }: UploadsIndexPageProps) {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [ownerFilter, setOwnerFilter] = useState('');

    const filteredUploads = uploads.filter((upload) => {
        const matchesSearch =
            upload.id.toLowerCase().includes(search.toLowerCase()) ||
            upload.customer.toLowerCase().includes(search.toLowerCase());

        const matchesStatus = statusFilter
            ? upload.status.toLowerCase().includes(statusFilter.toLowerCase())
            : true;

        const matchesOwner = ownerFilter
            ? upload.owner.toLowerCase().includes(ownerFilter.toLowerCase())
            : true;

        return matchesSearch && matchesStatus && matchesOwner;
    });

    const getUploadHref = (upload: UploadRecord) => {
        if (upload.status === 'uploaded') {
            return `/fmcg/uploads/new/${upload.rawId}`;
        }
        return `/fmcg/uploads/${upload.rawId}/validation`;
    };

    const handleRefresh = () => {
        router.reload({ only: ['uploads'] });
    };

    return (
        <FmcgPageShell title="Uploads">
            <PageHeader
                eyebrow="Ingestion"
                title="Bulk Upload Batches"
                description="Track all incoming files and API batches before processing. Filter by status, customer, or owner and jump into failed rows quickly."
                actions={[{ label: 'New Upload', href: '/fmcg/uploads/new' }]}
            />

            <SectionCard
                title="Upload Registry"
                subtitle="All sources consolidated into a single operations queue"
                right={<Button size="sm" onClick={handleRefresh}>Refresh</Button>}
            >
                <div className="mb-4 grid gap-3 md:grid-cols-4 items-center">
                    <Input
                        placeholder="Search upload id or customer"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                    <Input
                        placeholder="Status (ready, failed, completed)"
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                    />
                    <Input
                        placeholder="Owner"
                        value={ownerFilter}
                        onChange={(e) => setOwnerFilter(e.target.value)}
                    />
                    <div className="flex justify-end">
                        {(search || statusFilter || ownerFilter) && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    setSearch('');
                                    setStatusFilter('');
                                    setOwnerFilter('');
                                }}
                                className="h-9 px-3 text-xs text-slate-500 hover:text-slate-700"
                            >
                                Clear Filters
                            </Button>
                        )}
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[880px] text-left text-sm">
                        <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="pb-3">Upload ID</th>
                                <th className="pb-3">Customer</th>
                                <th className="pb-3">Source</th>
                                <th className="pb-3">Rows</th>
                                <th className="pb-3">Valid %</th>
                                <th className="pb-3">Owner</th>
                                <th className="pb-3">Created</th>
                                <th className="pb-3">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {filteredUploads.map((upload) => (
                                <tr key={upload.id}>
                                    <td className="py-3 font-medium text-slate-800">
                                        <Link
                                            href={getUploadHref(upload)}
                                            className="text-cyan-600 hover:text-cyan-800 hover:underline transition-colors"
                                        >
                                            {upload.id}
                                        </Link>
                                    </td>
                                    <td className="py-3 text-slate-700">{upload.customer}</td>
                                    <td className="py-3 text-slate-700">{upload.source}</td>
                                    <td className="py-3 text-slate-700">{upload.rows.toLocaleString()}</td>
                                    <td className="py-3 text-slate-700">{upload.validPercent}%</td>
                                    <td className="py-3 text-slate-700">{upload.owner}</td>
                                    <td className="py-3 text-slate-700">{upload.createdAt}</td>
                                    <td className="py-3"><StatusPill value={upload.status} /></td>
                                </tr>
                            ))}
                            {filteredUploads.length === 0 && (
                                <tr>
                                    <td colSpan={8} className="py-8 text-center text-slate-500">
                                        No uploads found.
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

UploadsIndexPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Uploads', href: '/fmcg/uploads' },
    ],
};
