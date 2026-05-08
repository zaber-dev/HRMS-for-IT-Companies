import { Link } from '@inertiajs/react';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';

type Props = {
    totalEmployees: number;
    onBench: number;
    assigned: number;
    deactivated: number;
};

export function WorkforceSummaryPanel({ totalEmployees, onBench, assigned, deactivated }: Props) {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">Workforce Summary</h2>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <MetricCard label="Total Employees" value={totalEmployees} />
                <MetricCard
                    label="On Bench"
                    value={onBench}
                    href={usersIndex.url({ query: { bench_status: 'on_bench' } })}
                    colorClass="text-blue-600 dark:text-blue-400"
                />
                <MetricCard
                    label="Assigned"
                    value={assigned}
                    href={usersIndex.url({ query: { bench_status: 'assigned' } })}
                    colorClass="text-green-600 dark:text-green-400"
                />
                <MetricCard
                    label="Deactivated"
                    value={deactivated}
                    colorClass="text-red-600 dark:text-red-400"
                />
            </div>
        </div>
    );
}

type MetricCardProps = {
    label: string;
    value: number;
    href?: string;
    colorClass?: string;
};

function MetricCard({ label, value, href, colorClass = 'text-foreground' }: MetricCardProps) {
    const content = (
        <div className="rounded-md border border-border bg-background p-4 text-center transition-colors hover:bg-muted/30">
            <div className={`text-3xl font-bold ${colorClass}`}>{value}</div>
            <div className="mt-1 text-sm text-muted-foreground">{label}</div>
        </div>
    );

    if (href) {
        return <Link href={href}>{content}</Link>;
    }

    return content;
}
