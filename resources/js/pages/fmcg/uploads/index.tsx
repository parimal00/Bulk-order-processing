import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { uploads } from '@/lib/fmcg-data';

export default function UploadsIndexPage() {
    return (
        <FmcgPageShell title="Uploads">
            <PageHeader
                eyebrow="Ingestion"
                title="Bulk Upload Batches"
                description="Track all incoming files and API batches before processing. Filter by status, customer, or owner and jump into failed rows quickly."
                actions={['New Upload', 'Export Upload Report']}
            />

            <SectionCard
                title="Upload Registry"
                subtitle="All sources consolidated into a single operations queue"
                right={<Button size="sm">Refresh</Button>}
            >
                <div className="mb-4 grid gap-3 md:grid-cols-4">
                    <Input placeholder="Search upload id or customer" />
                    <Input placeholder="Status (ready, failed, completed)" />
                    <Input placeholder="Owner" />
                    <Input placeholder="Date range" />
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
                            {uploads.map((upload) => (
                                <tr key={upload.id}>
                                    <td className="py-3 font-medium text-slate-800">{upload.id}</td>
                                    <td className="py-3 text-slate-700">{upload.customer}</td>
                                    <td className="py-3 text-slate-700">{upload.source}</td>
                                    <td className="py-3 text-slate-700">{upload.rows.toLocaleString()}</td>
                                    <td className="py-3 text-slate-700">{upload.validPercent}%</td>
                                    <td className="py-3 text-slate-700">{upload.owner}</td>
                                    <td className="py-3 text-slate-700">{upload.createdAt}</td>
                                    <td className="py-3"><StatusPill value={upload.status} /></td>
                                </tr>
                            ))}
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
