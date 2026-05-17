import { useEffect } from 'react';
import { router } from '@inertiajs/react';
import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';

interface ValidationError {
    id: number;
    row_number: number;
    column_name: string;
    error_code: string;
    error_message: string;
    raw_value: string;
}

interface ValidationProps {
    upload: {
        id: number;
        status: string;
        total_rows: number;
        valid_rows: number;
        invalid_rows: number;
    };
    errors: {
        data: ValidationError[];
        current_page: number;
        last_page: number;
    };
}

export default function UploadValidationPage({ upload, errors }: ValidationProps) {
    useEffect(() => {
        let interval: NodeJS.Timeout;
        if (upload.status === 'validating') {
            interval = setInterval(() => {
                router.reload({ only: ['upload', 'errors'] });
            }, 2000);
        }
        return () => {
            if (interval) clearInterval(interval);
        };
    }, [upload.status]);

    return (
        <FmcgPageShell title="Validation Results">
            <PageHeader
                eyebrow="Validation"
                title={`Upload Validation Results #${upload.id}`}
                description="Review row-level errors, fix issues, and decide whether to process valid rows immediately or hold the entire batch."
                actions={[
                    'Export Errors',
                    'Re-Validate',
                    <Button key="process" disabled={upload.valid_rows === 0 || upload.status === 'validating'}>
                        Process Valid Rows
                    </Button>
                ]}
            />

            <div className="grid gap-4 md:grid-cols-3">
                <SectionCard title="Total Rows" subtitle="Rows detected in upload batch">
                    <div className="flex items-center gap-2">
                        <p className="text-3xl font-semibold text-slate-800">{upload.total_rows}</p>
                        {upload.status === 'validating' && <Loader2 className="h-5 w-5 animate-spin text-slate-400" />}
                    </div>
                </SectionCard>
                <SectionCard title="Valid Rows" subtitle="Rows ready for processing">
                    <p className="text-3xl font-semibold text-emerald-700">{upload.valid_rows}</p>
                </SectionCard>
                <SectionCard title="Invalid Rows" subtitle="Rows needing correction">
                    <p className="text-3xl font-semibold text-rose-700">{upload.invalid_rows}</p>
                </SectionCard>
            </div>

            {upload.status === 'validating' && (
                <div className="mt-8 flex flex-col items-center justify-center rounded-lg border border-slate-200 bg-slate-50 p-12">
                    <Loader2 className="h-10 w-10 animate-spin text-indigo-600 mb-4" />
                    <h3 className="text-lg font-medium text-slate-900">Validation in Progress</h3>
                    <p className="text-slate-500 mt-1">Please wait while we validate your uploaded rows...</p>
                </div>
            )}

            {upload.status !== 'validating' && upload.invalid_rows > 0 && (
                <SectionCard title="Error Queue" subtitle={`Showing page ${errors.current_page} of ${errors.last_page}`}>
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[760px] text-left text-sm">
                            <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="pb-3">Row</th>
                                    <th className="pb-3">Column</th>
                                    <th className="pb-3">Value</th>
                                    <th className="pb-3">Issue</th>
                                    <th className="pb-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {errors.data.map((error) => (
                                    <tr key={error.id}>
                                        <td className="py-3 font-medium text-slate-800">{error.row_number + 1}</td>
                                        <td className="py-3 font-mono text-xs text-slate-600">{error.column_name}</td>
                                        <td className="py-3 font-mono text-xs text-slate-600 truncate max-w-[150px]" title={error.raw_value}>{error.raw_value || '-'}</td>
                                        <td className="py-3 text-rose-700">{error.error_message}</td>
                                        <td className="py-3 text-right">
                                            <Button variant="outline" size="sm">Resolve</Button>
                                        </td>
                                    </tr>
                                ))}
                                {errors.data.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="py-8 text-center text-slate-500">
                                            No errors to display.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </SectionCard>
            )}

            {upload.status !== 'validating' && upload.invalid_rows === 0 && upload.total_rows > 0 && (
                <div className="mt-8 flex flex-col items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 p-12">
                    <div className="h-12 w-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 className="text-lg font-medium text-emerald-900">All Rows Valid!</h3>
                    <p className="text-emerald-700 mt-1">Your upload has been successfully validated with no errors.</p>
                </div>
            )}
        </FmcgPageShell>
    );
}

UploadValidationPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Uploads', href: '/fmcg/uploads' },
        { title: 'Validation', href: '#' },
    ],
};
