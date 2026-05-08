import { Link } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/Leave/ApprovalController';

type Props = {
    count: number;
};

export function LeaveQueuePanel({ count }: Props) {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">Leave Queue</h2>
            <Link
                href={index.url()}
                className="block rounded-md border border-border bg-background p-4 text-center transition-colors hover:bg-muted/30"
            >
                <div className="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{count}</div>
                <div className="mt-1 text-sm text-muted-foreground">Pending Approvals</div>
            </Link>
        </div>
    );
}
