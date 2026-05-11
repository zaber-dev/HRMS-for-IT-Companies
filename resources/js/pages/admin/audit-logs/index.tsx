import { Head, Link } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/Admin/AuditLogController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { formatDateTime, formatRelative } from '@/lib/utils';
import type { AuditLog } from '@/types/auth';

type PaginatedLogs = {
    data: AuditLog[];
    links: { url: string | null; label: string; active: boolean }[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
};

type Props = {
    logs: PaginatedLogs;
};

function formatAuditableType(type: string): string {
    return type.split('\\').pop() ?? type;
}

export default function AuditLogsIndex({ logs }: Props) {
    return (
        <>
            <Head title="Audit Log" />

            <div className="space-y-6">
                <Heading
                    title="Audit Log"
                    description="Immutable record of all administrative actions"
                />

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Timestamp
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Actor
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Action
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Target
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {logs.data.map((log) => (
                                <tr
                                    key={log.id}
                                    className="bg-background transition-colors hover:bg-muted/30"
                                >
                                    <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                        <span title={formatDateTime(log.created_at)}>
                                            {formatRelative(log.created_at)}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        {log.actor ? (
                                            <div>
                                                <div className="font-medium">
                                                    {log.actor.name}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {log.actor.email}
                                                </div>
                                            </div>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                System
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">
                                            {log.action}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {formatAuditableType(
                                            log.auditable_type,
                                        )}{' '}
                                        #{log.auditable_id}
                                    </td>
                                </tr>
                            ))}
                            {logs.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No audit log entries found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {logs.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1">
                        {logs.links.map((link, i) => (
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

AuditLogsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Audit Log',
            href: index.url(),
        },
    ],
};
