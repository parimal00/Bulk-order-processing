import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Link } from '@inertiajs/react';
type PaginatedOrders = {
    data: Array<{
        id: number;
        order_number: string;
        customer: { name: string } | null;
        status: string;
        total: string;
        placed_at: string;
    }>;
};

export default function OrdersIndexPage({ orders }: { orders: PaginatedOrders }) {
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
                                <th className="pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {orders.data.map((order) => (
                                <tr key={order.id} className="hover:bg-slate-50/50 transition-colors">
                                    <td className="py-3 font-medium text-slate-800">
                                        <Link href={`/fmcg/orders/${order.id}`} className="text-cyan-600 hover:text-cyan-700 hover:underline">
                                            {order.order_number}
                                        </Link>
                                    </td>
                                    <td className="py-3 text-slate-700">{order.customer?.name ?? 'Unknown'}</td>
                                    <td className="py-3"><StatusPill value={order.status} /></td>
                                    <td className="py-3 text-slate-700">0%</td>
                                    <td className="py-3 text-slate-700">${order.total}</td>
                                    <td className="py-3 text-slate-700">{new Date(order.placed_at).toLocaleDateString()}</td>
                                    <td className="py-3 text-right">
                                        <Link
                                            href={`/fmcg/orders/${order.id}`}
                                            className="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition-colors"
                                        >
                                            View Order
                                        </Link>
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

OrdersIndexPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Orders', href: '/fmcg/orders' },
    ],
};
