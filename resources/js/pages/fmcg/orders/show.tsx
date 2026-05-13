import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { orderLines } from '@/lib/fmcg-data';

export default function OrderDetailPage() {
    return (
        <FmcgPageShell title="Order Detail">
            <PageHeader
                eyebrow="Order Detail"
                title="SO-55012 - EverFresh Supermart"
                description="Detailed line-level view including pricing, allocation, backorders, and approval history for this FMCG wholesale order."
                actions={['Approve Release', 'Notify Warehouse', 'Retry ERP Sync']}
            />

            <div className="grid gap-4 lg:grid-cols-3">
                <SectionCard title="Status" subtitle="Current execution state">
                    <div className="space-y-3">
                        <StatusPill value="backordered" />
                        <p className="text-sm text-slate-600">Backorder ratio is 28%. 2 line items will ship in next wave.</p>
                    </div>
                </SectionCard>
                <SectionCard title="Commercial" subtitle="Financial snapshot">
                    <div className="space-y-1 text-sm text-slate-700">
                        <p>Subtotal: <span className="font-semibold">$23,400</span></p>
                        <p>Discount: <span className="font-semibold">-$1,280</span></p>
                        <p>Tax: <span className="font-semibold">$2,740</span></p>
                        <p className="pt-2 text-base">Grand Total: <span className="font-semibold text-slate-800">$24,860</span></p>
                    </div>
                </SectionCard>
                <SectionCard title="Actions" subtitle="Operational controls">
                    <div className="flex flex-wrap gap-2">
                        <Button size="sm">Allocate from Backup WH</Button>
                        <Button size="sm" variant="outline">Split Shipment</Button>
                        <Button size="sm" variant="outline">Escalate</Button>
                    </div>
                </SectionCard>
            </div>

            <SectionCard title="Line Items" subtitle="Requested vs allocated quantities">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[920px] text-left text-sm">
                        <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="pb-3">SKU</th>
                                <th className="pb-3">Product</th>
                                <th className="pb-3">Requested</th>
                                <th className="pb-3">Allocated</th>
                                <th className="pb-3">Backorder</th>
                                <th className="pb-3">Unit Price</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {orderLines.map((line) => (
                                <tr key={line.sku}>
                                    <td className="py-3 font-mono text-xs text-slate-600">{line.sku}</td>
                                    <td className="py-3 font-medium text-slate-800">{line.product}</td>
                                    <td className="py-3 text-slate-700">{line.requestedQty}</td>
                                    <td className="py-3 text-slate-700">{line.allocatedQty}</td>
                                    <td className="py-3 text-slate-700">{line.backorderQty}</td>
                                    <td className="py-3 text-slate-700">{line.unitPrice}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </FmcgPageShell>
    );
}

OrderDetailPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Orders', href: '/fmcg/orders' },
        { title: 'SO-55012', href: '/fmcg/orders/so-55012' },
    ],
};
