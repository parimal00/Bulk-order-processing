import {
    Activity,
    ClipboardCheck,
    FileSpreadsheet,
    Files,
    Gauge,
    ReceiptText,
    Settings,
    Shuffle,
} from 'lucide-react';
import type { NavItem } from '@/types';

const allFmcgNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: Gauge },
    { title: 'Uploads', href: '/fmcg/uploads', icon: FileSpreadsheet },
    { title: 'Processing', href: '/fmcg/processing', icon: Shuffle },
    { title: 'Approvals', href: '/fmcg/approvals', icon: ClipboardCheck },
    { title: 'Orders', href: '/fmcg/orders', icon: ReceiptText },
    { title: 'Reconciliation', href: '/fmcg/reconciliation', icon: Files },
    { title: 'Audit Trail', href: '/fmcg/audit', icon: Activity },
    { title: 'Settings', href: '/fmcg/settings/pricing-rules', icon: Settings },
];

export function getFmcgNavItems(role?: string): NavItem[] {
    const safeRole = role ?? 'ops';

    if (safeRole === 'admin') {
        return allFmcgNavItems;
    }

    if (safeRole === 'approver') {
        return allFmcgNavItems.filter((item) =>
            ['/dashboard', '/fmcg/approvals', '/fmcg/orders', '/fmcg/reconciliation'].includes(item.href)
        );
    }

    return allFmcgNavItems.filter((item) =>
        ['/dashboard', '/fmcg/uploads', '/fmcg/processing'].includes(item.href)
    );
}
