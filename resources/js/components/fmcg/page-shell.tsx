import { Head } from '@inertiajs/react';
import { Bell, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export function FmcgPageShell({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <>
            <Head title={title} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-y-auto bg-[#f4f6f8] p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#dce4ea] bg-white p-3">
                    <div className="relative min-w-[220px] flex-1 max-w-xl">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                        <Input className="pl-9" placeholder="Search SKU, order, customer, upload..." />
                    </div>
                    <div className="flex items-center gap-2">
                        <Input className="w-[170px]" defaultValue="May 12, 2026" />
                        <Button size="icon" variant="outline">
                            <Bell className="size-4" />
                        </Button>
                    </div>
                </div>
                {children}
            </div>
        </>
    );
}
