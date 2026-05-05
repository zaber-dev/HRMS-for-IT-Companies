import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PageProps } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Leave Requests', href: '/leave-requests' },
    { title: 'New Leave Request', href: '/leave-requests/create' },
];

export default function LeaveRequestCreate({}: PageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Leave Request" />
            <div className="p-6">
                <h1 className="text-2xl font-semibold">New Leave Request</h1>
                {/* TODO: implement full UI in task 12.2 */}
            </div>
        </AppLayout>
    );
}
