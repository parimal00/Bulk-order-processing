import { Link, usePage } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { getFmcgNavItems } from '@/lib/fmcg-nav';
import type { NavItem } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'New Upload',
        href: '/fmcg/uploads/new',
        icon: ClipboardList,
    },
];

export function AppSidebar() {
    const { auth } = usePage<any>().props;
    const role = auth?.user?.role as string | undefined;
    const navItems = getFmcgNavItems(role);
    const footerItems = role === 'ops' || role === 'admin' ? footerNavItems : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
