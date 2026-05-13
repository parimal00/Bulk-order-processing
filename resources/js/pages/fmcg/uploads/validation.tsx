import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { validationErrors } from '@/lib/fmcg-data';

export default function UploadValidationPage() {
    return (
        <FmcgPageShell title="Validation Results">
            <PageHeader
                eyebrow="Validation"
                title="Upload Validation Results"
                description="Review row-level errors, fix issues, and decide whether to process valid rows immediately or hold the entire batch."
                actions={['Export Errors', 'Re-Validate', 'Process Valid Rows']}
            />

            <div className="grid gap-4 md:grid-cols-3">
                <SectionCard title="Total Rows" subtitle="Rows detected in upload batch">
                    <p className="text-3xl font-semibold text-slate-800">834</p>
                </SectionCard>
                <SectionCard title="Valid Rows" subtitle="Rows ready for processing">
                    <p className="text-3xl font-semibold text-emerald-700">734</p>
                </SectionCard>
                <SectionCard title="Invalid Rows" subtitle="Rows needing correction">
                    <p className="text-3xl font-semibold text-rose-700">100</p>
                </SectionCard>
            </div>

            <SectionCard title="Error Queue" subtitle="Grouped by row with suggested actions">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="pb-3">Row</th>
                                <th className="pb-3">SKU</th>
                                <th className="pb-3">Issue</th>
                                <th className="pb-3">Suggested Fix</th>
                                <th className="pb-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {validationErrors.map((error) => (
                                <tr key={`${error.row}-${error.sku}`}>
                                    <td className="py-3 font-medium text-slate-800">{error.row}</td>
                                    <td className="py-3 font-mono text-xs text-slate-600">{error.sku}</td>
                                    <td className="py-3 text-rose-700">{error.issue}</td>
                                    <td className="py-3 text-slate-700">{error.suggestion}</td>
                                    <td className="py-3 text-right">
                                        <Button variant="outline" size="sm">Resolve</Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </FmcgPageShell>
    );
}

UploadValidationPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Uploads', href: '/fmcg/uploads' },
        { title: 'Validation', href: '/fmcg/uploads/validation' },
    ],
};
