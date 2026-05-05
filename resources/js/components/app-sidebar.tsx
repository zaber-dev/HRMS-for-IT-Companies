import { usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { BookOpen, CalendarDays, ClipboardCheck, ClipboardList, FolderGit2, LayoutGrid, List, Shield, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavAdmin } from '@/components/nav-admin';
import { NavFooter } from '@/components/nav-footer';
import { NavLeave } from '@/components/nav-leave';
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
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';
import { index as rolesIndex } from '@/actions/App/Http/Controllers/Admin/RoleController';
import { index as auditLogsIndex } from '@/actions/App/Http/Controllers/Admin/AuditLogController';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const role = auth.user.role;

    const adminNavItems: NavItem[] = [
        ...(role === 'super_admin' || role === 'admin' || role === 'hr'
            ? [{ title: 'Users', href: usersIndex().url, icon: Users }]
            : []),
        ...(role === 'super_admin' || role === 'admin'
            ? [{ title: 'Roles', href: rolesIndex().url, icon: Shield }]
            : []),
        ...(role === 'super_admin'
            ? [{ title: 'Audit Log', href: auditLogsIndex().url, icon: ClipboardList }]
            : []),
    ];

    const leaveNavItems: NavItem[] = [
        { title: 'My Leave Requests', href: '/leave-requests', icon: CalendarDays },
        ...(role === 'hr' || role === 'admin' || role === 'super_admin'
            ? [{ title: 'Approval Queue', href: '/leave-requests/approvals', icon: ClipboardCheck }]
            : []),
        ...(role === 'admin' || role === 'super_admin'
            ? [{ title: 'All Leave Requests', href: '/leave-requests/all', icon: List }]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavLeave items={leaveNavItems} />
                <NavAdmin items={adminNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
