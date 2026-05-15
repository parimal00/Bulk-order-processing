import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';



import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';


export default function NewUploadPage({ flash, upload, headers, sampleData }: { flash: any, upload?: any, headers?: string[], sampleData?: any[] }) {

    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const [file, setFile] = useState<File | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [columnMapping, setColumnMapping] = useState<Record<string, string>>({});

    const systemFields = [
        { value: 'sku', label: 'SKU' },
        { value: 'quantity', label: 'Quantity' },
        { value: 'requested_date', label: 'Requested Delivery Date' },
        { value: 'note', label: 'Notes' },
    ];

    // Initialize mappings: For each system field, find the best matching CSV header
    useEffect(() => {
        if (headers) {
            const initial: Record<string, string> = {};
            systemFields.forEach(field => {
                const match = headers.find(h => h.toLowerCase().includes(field.value.toLowerCase()));
                if (match) initial[field.value] = match;
            });
            setColumnMapping(initial);
        }
    }, [headers]);

    const onPickFile = () => fileInputRef.current?.click();

    const onFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFile(e.target.files?.[0] ?? null);
    };

    const onUpload = () => {
        if (!file) return;

        const form = new FormData();
        form.append('file', file);

        setSubmitting(true);
        router.post('/fmcg/bulk-uploads', form, {
            forceFormData: true,
            onFinish: () => setSubmitting(false),
            onSuccess: () => {
                // Stay on page to allow mapping
            }
        });
    };



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
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".csv,text/csv"
                            className="hidden"
                            onChange={onFileChange}
                        />
                        <Button onClick={onPickFile}>Choose File</Button>
                        <Button variant="outline">Download Template</Button>

                        {file ? <p className="mt-3 text-sm text-slate-700">{file.name}</p> : null}

                        <Button onClick={onUpload} disabled={!file || submitting}>
                            {submitting ? 'Uploading...' : 'Upload CSV'}
                        </Button>
                    </div>
                    {upload && (
                        <p className="mt-4 text-sm font-semibold text-emerald-600">
                            ✓ File "{upload.original_filename}" uploaded successfully. Please map columns below.
                        </p>
                    )}
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

                <SectionCard title="Step 3 - Column Mapping" subtitle="Assign CSV columns to required fields">
                    <div className="space-y-4 text-sm">
                        {headers ? (
                            systemFields.map((field) => (
                                <div key={field.value} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="flex items-center justify-between gap-2 mb-3">
                                        <p className="font-semibold text-slate-900">{field.label}</p>
                                        <span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-tight ${columnMapping[field.value] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'}`}>
                                            {columnMapping[field.value] ? 'Linked' : 'Pending'}
                                        </span>
                                    </div>
                                    <div className="mt-2">
                                        <select
                                            value={columnMapping[field.value] || ''}
                                            onChange={(e) => setColumnMapping(prev => ({ ...prev, [field.value]: e.target.value }))}
                                            className="w-full rounded-lg border-slate-200 bg-slate-50 text-sm focus:border-cyan-500 focus:ring-cyan-500 py-2"
                                        >
                                            <option value="">-- Choose CSV Column --</option>
                                            {headers.map(h => (
                                                <option key={h} value={h}>
                                                    {h}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="flex flex-col items-center justify-center py-8 text-center text-slate-500">
                                <p className="italic">Upload a file first to see column mapping</p>
                            </div>
                        )}
                    </div>
                </SectionCard>
            </div>

            {sampleData && sampleData.length > 0 && (
                <SectionCard title="Step 4 - Sample Preview" subtitle="First 3 rows as they will be imported">
                    <div className="overflow-x-auto rounded-xl border border-slate-200">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 text-slate-600">
                                <tr>
                                    {/* Mapped system fields */}
                                    {systemFields.map((field) => (
                                        <th key={field.value} className="border-r border-slate-200 px-4 py-3 font-medium bg-cyan-50/50">
                                            <div className="flex flex-col">
                                                <span className="text-cyan-800 text-xs uppercase tracking-wider">{field.label}</span>
                                            </div>
                                        </th>
                                    ))}
                                    {/* Unmapped CSV columns */}
                                    {headers.filter(h => !Object.values(columnMapping).includes(h)).map((h: string) => (
                                        <th key={h} className="px-4 py-3 font-medium text-slate-400">
                                            <div className="flex flex-col">
                                                <span className="text-[10px] uppercase tracking-wider">Unmapped</span>
                                                <span className="text-xs truncate max-w-[120px]">{h}</span>
                                            </div>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {sampleData.map((row: any, i: number) => (
                                    <tr key={i} className="hover:bg-slate-50/50 transition-colors">
                                        {/* Mapped values */}
                                        {systemFields.map((field) => (
                                            <td key={field.value} className="border-r border-slate-100 px-4 py-2 font-medium text-slate-900 bg-cyan-50/20">
                                                {columnMapping[field.value] ? row[columnMapping[field.value]] : <span className="text-slate-300">--</span>}
                                            </td>
                                        ))}
                                        {/* Unmapped values */}
                                        {headers.filter(h => !Object.values(columnMapping).includes(h)).map((h: string) => (
                                            <td key={h} className="px-4 py-2 text-slate-400 italic">
                                                {row[h]}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="mt-6 flex items-center justify-between">
                        <p className="text-sm text-slate-500">
                            Showing first 3 rows. All mapped fields will be validated in the next step.
                        </p>
                        <Button size="lg" className="bg-cyan-600 hover:bg-cyan-700 shadow-lg shadow-cyan-100">
                            Confirm Mapping & Start Validation
                        </Button>
                    </div>
                </SectionCard>
            )}
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
