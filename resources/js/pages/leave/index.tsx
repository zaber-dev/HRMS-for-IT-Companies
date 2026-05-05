import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PageProps } from '@/types';
import { type LeaveRequest } from '@/types/leave';
import { type PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Leave Requests', href: '/leave-requests' },
];

interface Props extends PageProps {
    leaveRequests: PaginatedData<LeaveRequest>;
    filters: {
        status?: string;
    };
}

export default function LeaveRequestIndex({ leaveRequests, filters }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Leave Requests" />
            <div className="p-6">
                <h1 className="text-2xl font-semibold">My Leave Requests</h1>
                {/* TODO: implement full UI in task 12.1 */}
            </div>
        </AppLayout>
    );
}
