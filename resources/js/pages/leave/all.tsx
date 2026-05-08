import { Head, Link, router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { index as allIndex } from '@/actions/App/Http/Controllers/Leave/AllLeaveRequestsController';
import { index as leaveIndex } from '@/actions/App/Http/Controllers/Leave/LeaveRequestController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    LeaveRequest,
    LeaveStatus,
    PaginatedData,
    Role,
    User,
} from '@/types';

const roleOptions = [
    { value: 'employee', label: 'Employee' },
    { value: 'hr', label: 'HR' },
    { value: 'admin', label: 'Admin' },
];

function statusBadgeClass(status: LeaveStatus): string {
    const map: Record<LeaveStatus, string> = {
        pending_hr:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        pending_admin:
            'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        pending_super_admin:
            'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        approved:
            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        rejected:
            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        cancelled:
            'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    };

    return map[status] ?? '';
}

function formatStatus(status: LeaveStatus): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

type UserWithRoles = User & { roles?: Role[] };
type LeaveRequestWithUser = LeaveRequest & { user: UserWithRoles };

type Filters = {
    status?: string;
    role?: string;
    start_date?: string;
    end_date?: string;
};

type Props = {
    leaveRequests: PaginatedData<LeaveRequestWithUser>;
    filters: Filters;
    statuses: string[];
};

export default function AllLeaveRequests({
    leaveRequests,
    filters,
    statuses,
}: Props) {
    const formRef = useRef<HTMLFormElement>(null);
    const [status, setStatus] = useState(filters.status ?? '');
    const [role, setRole] = useState(filters.role ?? '');
    const [startDate, setStartDate] = useState(filters.start_date ?? '');
    const [endDate, setEndDate] = useState(filters.end_date ?? '');

    function handleFilterChange() {
        const params: Record<string, string> = {};
        if (status) params.status = status;
        if (role) params.role = role;
        if (startDate) params.start_date = startDate;
        if (endDate) params.end_date = endDate;

        router.get(allIndex.url(), params, {
            preserveState: true,
            replace: true,
        });
    }

    function handleReset() {
        setStatus('');
        setRole('');
        setStartDate('');
        setEndDate('');
        router.get(allIndex.url(), {}, { preserveState: false, replace: true });
    }

    return (
        <>
            <Head title="All Leave Requests" />

            <div className="space-y-6">
                <Heading
                    title="All Leave Requests"
                    description="View and filter all leave requests across all users"
                />

                {/* Filters */}
                <form
                    ref={formRef}
                    onSubmit={(e) => {
                        e.preventDefault();
                        handleFilterChange();
                    }}
                    className="flex flex-wrap items-end gap-4 rounded-lg border border-border p-4"
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="filter-status">Status</Label>
                        <Select value={status || undefined} onValueChange={(value) => { setStatus(value === status ? '' : value); }}>
                            <SelectTrigger id="filter-status" className="w-48">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                {statuses.map((s) => (
                                    <SelectItem key={s} value={s}>
                                        {s.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="filter-role">Submitter Role</Label>
                        <Select value={role || undefined} onValueChange={(value) => { setRole(value === role ? '' : value); }}>
                            <SelectTrigger id="filter-role" className="w-40">
                                <SelectValue placeholder="All roles" />
                            </SelectTrigger>
                            <SelectContent>
                                {roleOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="filter-start-date">From Date</Label>
                        <Input
                            id="filter-start-date"
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                            className="h-9"
                        />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="filter-end-date">To Date</Label>
                        <Input
                            id="filter-end-date"
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                            className="h-9"
                        />
                    </div>

                    <Button type="submit">Apply Filters</Button>

                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleReset}
                    >
                        Reset
                    </Button>
                </form>

                {/* Table */}
                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Submitter
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Role
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Date Range
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Reason
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Submitted
                                </th>
                                <th className="px-4 py-3 text-right font-medium text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {leaveRequests.data.map((request) => {
                                const roleName =
                                    request.user?.roles?.[0]?.name ?? '—';

                                return (
                                    <tr
                                        key={request.id}
                                        className="bg-background transition-colors hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {request.user?.name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant="secondary">
                                                {roleName}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {request.start_date} –{' '}
                                            {request.end_date}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {request.reason ? (
                                                request.reason.length > 50 ? (
                                                    request.reason.slice(
                                                        0,
                                                        50,
                                                    ) + '…'
                                                ) : (
                                                    request.reason
                                                )
                                            ) : (
                                                <span className="italic">
                                                    —
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                className={statusBadgeClass(
                                                    request.status,
                                                )}
                                            >
                                                {formatStatus(request.status)}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {new Date(
                                                request.submitted_at,
                                            ).toLocaleDateString()}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/leave-requests/${request.id}`}
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                );
                            })}
                            {leaveRequests.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No leave requests found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {leaveRequests.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1">
                        {leaveRequests.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

AllLeaveRequests.layout = {
    breadcrumbs: [
        {
            title: 'My Leave Requests',
            href: leaveIndex.url(),
        },
        {
            title: 'All Leave Requests',
            href: allIndex.url(),
        },
    ],
};
