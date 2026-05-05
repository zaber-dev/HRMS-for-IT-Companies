import { Form, Head, Link } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/Leave/ApprovalController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { type LeaveRequest, type LeaveStatus, type User } from '@/types';

function statusBadgeClass(status: LeaveStatus): string {
    const map: Record<LeaveStatus, string> = {
        pending_hr: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        pending_admin: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        pending_super_admin: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    };
    return map[status] ?? '';
}

function formatStatus(status: LeaveStatus): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

type Props = {
    leaveRequest: LeaveRequest & { user: User };
};

export default function RejectForm({ leaveRequest }: Props) {
    return (
        <>
            <Head title="Reject Leave Request" />

            <div className="space-y-6 max-w-lg">
                <Heading title="Reject Leave Request" description="Provide a reason for rejecting this leave request" />

                {/* Request Summary */}
                <div className="rounded-lg border border-border p-6 space-y-4">
                    <h2 className="text-base font-semibold">Request Details</h2>
                    <dl className="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt className="text-muted-foreground">Submitted by</dt>
                            <dd className="font-medium mt-1">{leaveRequest.user?.name ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Current Status</dt>
                            <dd className="mt-1">
                                <Badge className={statusBadgeClass(leaveRequest.status)}>
                                    {formatStatus(leaveRequest.status)}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Start Date</dt>
                            <dd className="font-medium mt-1">{leaveRequest.start_date}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">End Date</dt>
                            <dd className="font-medium mt-1">{leaveRequest.end_date}</dd>
                        </div>
                        <div className="col-span-2">
                            <dt className="text-muted-foreground">Reason</dt>
                            <dd className="mt-1">
                                {leaveRequest.reason ?? <span className="italic text-muted-foreground">No reason provided</span>}
                            </dd>
                        </div>
                    </dl>
                </div>

                {/* Reject Form */}
                <Form
                    action={`/leave-requests/approvals/${leaveRequest.id}/reject`}
                    method="post"
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="reason">
                                    Rejection Reason <span className="text-destructive">*</span>
                                </Label>
                                <textarea
                                    id="reason"
                                    name="reason"
                                    rows={3}
                                    required
                                    placeholder="Provide a reason for rejecting this request"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                />
                                <InputError message={errors.reason} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="comment">
                                    Additional Comment <span className="text-muted-foreground">(optional)</span>
                                </Label>
                                <textarea
                                    id="comment"
                                    name="comment"
                                    rows={3}
                                    placeholder="Add any additional context or notes"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button variant="destructive" type="submit" disabled={processing}>
                                    Confirm Rejection
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={index.url()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

RejectForm.layout = {
    breadcrumbs: [
        {
            title: 'Approval Queue',
            href: index.url(),
        },
        {
            title: 'Reject Request',
            href: '#',
        },
    ],
};
