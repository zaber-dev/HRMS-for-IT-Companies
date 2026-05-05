import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PageProps } from '@/types';
import { type LeaveRequest } from '@/types/leave';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Leave Requests', href: '/leave-requests' },
    { title: 'Leave Request Details', href: '#' },
];

interface Props extends PageProps {
    leaveRequest: LeaveRequest;
    canApprove: boolean;
    canReject: boolean;
}

export default function LeaveRequestShow({ leaveRequest, canApprove, canReject }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Leave Request Details" />
            <div className="p-6">
                <h1 className="text-2xl font-semibold">Leave Request Details</h1>
                {/* TODO: implement full UI in task 12.3 */}
            </div>
        </AppLayout>
    );
}
