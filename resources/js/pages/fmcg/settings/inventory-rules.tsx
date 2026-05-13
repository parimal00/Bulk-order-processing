import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { inventoryPolicies } from '@/lib/fmcg-data';

export default function InventoryRulesPage() {
    return (
        <FmcgPageShell title="Inventory Rules">
            <PageHeader
                eyebrow="Settings"
                title="Inventory Allocation Rules"
                description="Control FIFO strategy, safety stock thresholds, and backorder behavior used during line-item allocation."
                actions={['Update Policy', 'Preview Allocation Impact']}
            />

            <div className="grid gap-4 xl:grid-cols-3">
                <SectionCard className="xl:col-span-1" title="Policy Controls" subtitle="Operational allocation configuration">
                    <div className="space-y-3">
                        <Input defaultValue="FIFO by warehouse priority" />
                        <Input defaultValue="Backorder trigger if > 20%" />
                        <Input defaultValue="15 days" />
                        <Input defaultValue="Allocate premium clients first" />
                        <Button className="w-full">Save Policies</Button>
                    </div>
                </SectionCard>

                <SectionCard className="xl:col-span-2" title="Current Policies" subtitle="Latest saved rules and update timestamps">
                    <div className="space-y-3 text-sm">
                        {inventoryPolicies.map((policy) => (
                            <div key={policy.policy} className="rounded-xl border border-slate-200 bg-white p-3">
                                <p className="font-semibold text-slate-800">{policy.policy}</p>
                                <p className="mt-1 text-slate-700">{policy.value}</p>
                                <p className="mt-1 text-xs text-slate-500">Updated: {policy.updatedAt}</p>
                            </div>
                        ))}
                    </div>
                </SectionCard>
            </div>
        </FmcgPageShell>
    );
}

InventoryRulesPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Settings', href: '/fmcg/settings/inventory-rules' },
        { title: 'Inventory Rules', href: '/fmcg/settings/inventory-rules' },
    ],
};
