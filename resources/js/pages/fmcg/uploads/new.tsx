import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const mappingRows = [
    { source: 'sku_code', target: 'SKU', confidence: '98%' },
    { source: 'qty_ordered', target: 'Requested Quantity', confidence: '96%' },
    { source: 'need_by', target: 'Requested Delivery Date', confidence: '93%' },
    { source: 'unit_price', target: 'Proposed Unit Price', confidence: '88%' },
];

export default function NewUploadPage() {
    return (
        <FmcgPageShell title="New Upload">
            <PageHeader
                eyebrow="Upload Wizard"
                title="Create New Bulk Upload"
                description="Upload CSV/XLSX purchase orders, map incoming columns to system fields, and run pre-processing validation before entering the queue."
                actions={['Validate Sample', 'Proceed to Validation Results']}
            />

            <SectionCard title="Step 1 - File Intake" subtitle="Drop your customer purchase file">
                <div className="rounded-2xl border border-dashed border-cyan-300 bg-cyan-50/70 p-10 text-center">
                    <p className="text-base font-medium text-cyan-900">Drag and drop FMCG order file here</p>
                    <p className="mt-2 text-sm text-cyan-700">Supports CSV and XLSX up to 10 MB</p>
                    <div className="mt-4 flex items-center justify-center gap-2">
                        <Button>Choose File</Button>
                        <Button variant="outline">Download Template</Button>
                    </div>
                </div>
            </SectionCard>

            <div className="grid gap-4 xl:grid-cols-2">
                <SectionCard title="Step 2 - Source Metadata" subtitle="Tag incoming file for routing">
                    <div className="grid gap-3">
                        <Input defaultValue="Metro Retail Group" />
                        <Input defaultValue="Weekly Replenishment" />
                        <Input defaultValue="ops@metroretail.example" />
                        <Input defaultValue="May 14, 2026" />
                    </div>
                </SectionCard>

                <SectionCard title="Step 3 - Column Mapping" subtitle="Map source columns to required fields">
                    <div className="space-y-3 text-sm">
                        {mappingRows.map((row) => (
                            <div key={row.source} className="rounded-xl border border-slate-200 bg-white p-3">
                                <div className="flex items-center justify-between gap-2">
                                    <p className="font-mono text-xs text-slate-600">{row.source}</p>
                                    <span className="rounded-full bg-emerald-50 px-2 py-1 text-xs text-emerald-700">{row.confidence}</span>
                                </div>
                                <p className="mt-1 font-medium text-slate-800">{row.target}</p>
                            </div>
                        ))}
                    </div>
                </SectionCard>
            </div>
        </FmcgPageShell>
    );
}

NewUploadPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Uploads', href: '/fmcg/uploads' },
        { title: 'New Upload', href: '/fmcg/uploads/new' },
    ],
};
