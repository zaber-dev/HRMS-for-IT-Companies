import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PageProps } from '@/types';
import { type LeaveRequest } from '@/types/leave';
import { type PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Leave Requests', href: '/leave-requests' },
    { title: 'All Leave Requests', href: '/leave-requests/all' },
];

interface Props extends PageProps {
    leaveRequests: PaginatedData<LeaveRequest>;
    filters: {
        status?: string;
        role?: string;
        start_date?: string;
        end_date?: string;
    };
    statuses: string[];
}

export default function AllLeaveRequests({ leaveRequests, filters, statuses }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="All Leave Requests" />
            <div className="p-6">
                <h1 className="text-2xl font-semibold">All Leave Requests</h1>
                {/* TODO: implement full UI in task 12.7 */}
            </div>
        </AppLayout>
    );
}
