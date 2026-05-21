import { FmcgPageShell } from '@/components/fmcg/page-shell';
import { PageHeader, SectionCard, StatusPill } from '@/components/fmcg/ui';
import { Button } from '@/components/ui/button';
import { router, usePage } from '@inertiajs/react';

type OrderDetailLine = {
    sku: string;
    product_name: string;
    requested_qty: number;
    allocated_qty: number;
    backorder_qty: number;
    unit_price: string;
    line_total: string;
    allocation_status: string;
};

type OrderDetail = {
    id: number;
    order_number: string;
    status: string;
    currency: string;
    subtotal: number;
    total: number;
    placed_at: string;
    policy_flags: string[];
    projected_margin: string;
    customer_name: string;
    fulfillment: number;
    lines: OrderDetailLine[];
};

type ActivityLog = {
    id: number;
    timestamp: string;
    entity: string;
    action: string;
    actor: string;
    details: string;
};

export default function OrderDetailPage({ order, activities }: { order: OrderDetail; activities: ActivityLog[] }) {
    const { auth } = usePage<any>().props;
    const isOps = auth?.user?.role === 'ops';

    const handleApprove = () => {
        if (isOps) return;
        router.post(`/fmcg/approvals/${order.id}/approve`, {}, { preserveScroll: true });
    };

    const handleReject = () => {
        if (isOps) return;
        router.post(`/fmcg/approvals/${order.id}/reject`, {}, { preserveScroll: true });
    };

    const getStatusExplanation = () => {
        if (order.status === 'allocated') {
            return '100% allocated. Order is fully reserved in the warehouse and ready for release.';
        }
        if (order.status === 'partially_fulfilled') {
            return `Fulfillment ratio is ${order.fulfillment}%. ${order.lines.filter(l => l.backorder_qty > 0).length} line items have split backorders.`;
        }
        if (order.status === 'backordered') {
            return '0% allocated. All items have been placed on backorder due to safety stock shortfalls.';
        }
        if (order.status === 'pending_review') {
            return `Review required: Triggered risk rules. (Fulfillment: ${order.fulfillment}%).`;
        }
        return `Order is currently in ${order.status} state.`;
    };

    return (
        <FmcgPageShell title={`Order - ${order.order_number}`}>
            <PageHeader
                eyebrow="Order Detail"
                title={`${order.order_number} - ${order.customer_name}`}
                description="Detailed line-level view including pricing, warehouse allocation, backorders, and approval history."
                actions={
                    order.status === 'pending_review'
                        ? [
                              <Button key="app" onClick={handleApprove} disabled={isOps} className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">Approve Release</Button>,
                              <Button key="rej" onClick={handleReject} disabled={isOps} variant="destructive" className="disabled:opacity-50 disabled:cursor-not-allowed">Reject Order</Button>
                          ]
                        : ['Notify Warehouse', 'Export Manifest']
                }
            />

            <div className="grid gap-4 lg:grid-cols-3">
                <SectionCard title="Warehouse Allocation" subtitle="Current inventory state">
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-medium text-slate-500">Fulfillment Status</span>
                            <StatusPill value={order.status} />
                        </div>
                        <p className="text-sm text-slate-600">{getStatusExplanation()}</p>
                        
                        {/* Progress Bar */}
                        <div className="mt-3">
                            <div className="flex justify-between text-xs font-semibold text-slate-500 mb-1">
                                <span>Fulfillment Progress</span>
                                <span>{order.fulfillment}%</span>
                            </div>
                            <div className="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                <div 
                                    className={`h-full rounded-full transition-all duration-500 ${
                                        order.fulfillment === 100 
                                            ? 'bg-emerald-500' 
                                            : order.fulfillment > 0 
                                            ? 'bg-amber-500' 
                                            : 'bg-rose-500'
                                    }`}
                                    style={{ width: `${order.fulfillment}%` }}
                                ></div>
                            </div>
                        </div>
                    </div>
                </SectionCard>

                <SectionCard title="Commercials" subtitle="Financial breakdown">
                    <div className="space-y-1.5 text-sm text-slate-700">
                        <div className="flex justify-between">
                            <span className="text-slate-500">Subtotal:</span>
                            <span className="font-semibold text-slate-800">
                                ${order.subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-slate-500">Projected Margin:</span>
                            <span className="font-semibold text-cyan-600">{order.projected_margin}</span>
                        </div>
                        <div className="border-t border-slate-100 pt-2 flex justify-between text-base">
                            <span className="font-medium text-slate-600">Grand Total:</span>
                            <span className="font-bold text-slate-900">
                                ${order.total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </span>
                        </div>
                    </div>
                </SectionCard>

                <SectionCard title="Control & Policy Flags" subtitle="Risk audit findings">
                    <div className="space-y-3">
                        {order.policy_flags && order.policy_flags.length > 0 ? (
                            <div className="flex flex-wrap gap-1.5">
                                {order.policy_flags.map((flag) => (
                                    <span key={flag} className="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 shadow-sm">
                                        {flag}
                                    </span>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-slate-500 italic">No policy flags or warnings triggered. Standard B2B clearance.</p>
                        )}
                        <p className="text-xs text-slate-400">Placed on {new Date(order.placed_at).toLocaleString()}</p>
                    </div>
                </SectionCard>
            </div>

            <div className="grid gap-4 lg:grid-cols-3">
                <div className="lg:col-span-2">
                    <SectionCard title="Line Items" subtitle="Requested quantities vs allocated warehouse stock">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[620px] text-left text-sm">
                                <thead className="border-b text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="pb-3">SKU</th>
                                        <th className="pb-3">Product Description</th>
                                        <th className="pb-3 text-right">Requested</th>
                                        <th className="pb-3 text-right">Allocated</th>
                                        <th className="pb-3 text-right">Backordered</th>
                                        <th className="pb-3 text-right">Unit Price</th>
                                        <th className="pb-3 text-right">Total</th>
                                        <th className="pb-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {order.lines.map((line) => (
                                        <tr key={line.sku} className="hover:bg-slate-50/50 transition-colors">
                                            <td className="py-3.5 font-mono text-xs font-semibold text-slate-600">{line.sku}</td>
                                            <td className="py-3.5 font-medium text-slate-800">{line.product_name}</td>
                                            <td className="py-3.5 text-right font-medium text-slate-700">{line.requested_qty}</td>
                                            <td className={`py-3.5 text-right font-medium ${line.allocated_qty === line.requested_qty ? 'text-emerald-600' : 'text-amber-600'}`}>{line.allocated_qty}</td>
                                            <td className={`py-3.5 text-right font-medium ${line.backorder_qty > 0 ? 'text-rose-500 font-semibold' : 'text-slate-400'}`}>{line.backorder_qty}</td>
                                            <td className="py-3.5 text-right text-slate-600">{line.unit_price}</td>
                                            <td className="py-3.5 text-right font-semibold text-slate-800">{line.line_total}</td>
                                            <td className="py-3.5 text-center">
                                                <StatusPill value={line.allocation_status} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </SectionCard>
                </div>

                <div className="lg:col-span-1">
                    <SectionCard title="Activity Timeline" subtitle="Actor-level status transitions">
                        <div className="space-y-4">
                            {activities.length === 0 ? (
                                <p className="text-sm text-slate-500 italic">No activity logs recorded yet.</p>
                            ) : (
                                <div className="relative border-l border-slate-200 pl-4 ml-2 space-y-5">
                                    {activities.map((activity) => (
                                        <div key={activity.id} className="relative">
                                            <span className={`absolute -left-[21px] top-1 flex h-2.5 w-2.5 items-center justify-center rounded-full ring-4 ring-white ${
                                                activity.action.toLowerCase().includes('approve') 
                                                    ? 'bg-emerald-500' 
                                                    : activity.action.toLowerCase().includes('reject') 
                                                    ? 'bg-rose-500' 
                                                    : 'bg-cyan-500'
                                            }`}></span>
                                            <div className="flex items-center justify-between text-xs text-slate-400">
                                                <span className="font-semibold text-slate-700">{activity.actor}</span>
                                                <span>{activity.timestamp}</span>
                                            </div>
                                            <p className="mt-1 text-sm font-semibold text-slate-800">{activity.action}</p>
                                            <p className="text-xs text-slate-500 mt-0.5 leading-relaxed">{activity.details}</p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </SectionCard>
                </div>
            </div>
        </FmcgPageShell>
    );
}

OrderDetailPage.layout = (page: any) => {
    const order = page?.props?.order;
    return {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Orders', href: '/fmcg/orders' },
            { title: order?.order_number ?? 'Order', href: `/fmcg/orders/${order?.id}` },
        ],
    };
};
