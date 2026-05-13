import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { orders } from '@/lib/fmcg-data';

export default function OrdersIndexPage() {
    return (
        <FmcgPageShell title="Orders">
            <PageHeader
                eyebrow="Orders"
                title="Processed Orders"
                description="Browse all generated sales orders, fulfillment progression, and latest operational updates."
                actions={['Export Orders', 'Assign Ops Owner']}
            />

            <SectionCard title="Order Register" subtitle="Search, filter, and track fulfillment progression" right={<Button size="sm">Open SO-55012</Button>}>
                <div className="mb-4 grid gap-3 md:grid-cols-4">
                    <Input placeholder="Order number" />
                    <Input placeholder="Customer" />
                    <Input placeholder="Status" />
                    <Input placeholder="Date range" />
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full min-w-[860px] text-left text-sm">
                        <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="pb-3">Order</th>
                                <th className="pb-3">Customer</th>
                                <th className="pb-3">Status</th>
                                <th className="pb-3">Fulfillment</th>
                                <th className="pb-3">Total</th>
                                <th className="pb-3">Updated</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {orders.map((order) => (
                                <tr key={order.orderNo}>
                                    <td className="py-3 font-medium text-slate-800">{order.orderNo}</td>
                                    <td className="py-3 text-slate-700">{order.customer}</td>
                                    <td className="py-3"><StatusPill value={order.status} /></td>
                                    <td className="py-3 text-slate-700">{order.fulfillment}%</td>
                                    <td className="py-3 text-slate-700">{order.total}</td>
                                    <td className="py-3 text-slate-700">{order.updatedAt}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </FmcgPageShell>
    );
}

OrdersIndexPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Orders', href: '/fmcg/orders' },
    ],
};
