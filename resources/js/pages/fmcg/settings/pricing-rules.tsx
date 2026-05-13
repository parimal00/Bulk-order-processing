import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { pricingRules } from '@/lib/fmcg-data';

export default function PricingRulesPage() {
    return (
        <FmcgPageShell title="Pricing Rules">
            <PageHeader
                eyebrow="Settings"
                title="Catalog and Pricing Rules"
                description="Define customer-tier price breaks, MOQ thresholds, and category-level discount rules used by the processing engine."
                actions={['Create Rule', 'Run Rule Simulation']}
            />

            <div className="grid gap-4 xl:grid-cols-3">
                <SectionCard className="xl:col-span-1" title="Rule Editor" subtitle="Create or update pricing logic">
                    <div className="space-y-3">
                        <Input placeholder="Customer tier" defaultValue="Tier A" />
                        <Input placeholder="Category" defaultValue="Beverages" />
                        <Input placeholder="Minimum quantity" defaultValue="120" />
                        <Input placeholder="Discount percent" defaultValue="8" />
                        <Input placeholder="MOQ" defaultValue="60" />
                        <Button className="w-full">Save Rule</Button>
                    </div>
                </SectionCard>

                <SectionCard className="xl:col-span-2" title="Active Rules" subtitle="Rules currently applied in pricing engine">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[560px] text-left text-sm">
                            <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="pb-3">Tier</th>
                                    <th className="pb-3">Category</th>
                                    <th className="pb-3">Min Qty</th>
                                    <th className="pb-3">Discount</th>
                                    <th className="pb-3">MOQ</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {pricingRules.map((rule) => (
                                    <tr key={`${rule.customerTier}-${rule.category}`}>
                                        <td className="py-3 font-medium text-slate-800">{rule.customerTier}</td>
                                        <td className="py-3 text-slate-700">{rule.category}</td>
                                        <td className="py-3 text-slate-700">{rule.minQty}</td>
                                        <td className="py-3 text-slate-700">{rule.discount}</td>
                                        <td className="py-3 text-slate-700">{rule.moq}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </SectionCard>
            </div>
        </FmcgPageShell>
    );
}

PricingRulesPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Settings', href: '/fmcg/settings/pricing-rules' },
        { title: 'Pricing Rules', href: '/fmcg/settings/pricing-rules' },
    ],
};
