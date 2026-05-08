import { Head, Link, useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/Projects/ProjectAssignmentController';
import {
    index,
    show,
} from '@/actions/App/Http/Controllers/Projects/ProjectController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BenchStatus, Project } from '@/types/projects';
import type { Skill } from '@/types/skills';

type EmployeeWithSkills = {
    id: number;
    name: string;
    email: string;
    bench_status: BenchStatus;
    skills: Skill[];
};

type Props = {
    project: Project & { skills: Skill[] };
    suggestions: EmployeeWithSkills[];
    employees: EmployeeWithSkills[];
};

function benchStatusBadgeClass(status: BenchStatus): string {
    return status === 'on_bench'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
        : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';
}

function formatBenchStatus(status: BenchStatus): string {
    return status === 'on_bench' ? 'On Bench' : 'Assigned';
}

function matchingSkills(
    employee: EmployeeWithSkills,
    projectSkillIds: number[],
): Skill[] {
    return employee.skills.filter((skill) =>
        projectSkillIds.includes(skill.id),
    );
}

export default function AssignmentCreate({
    project,
    suggestions,
    employees,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        user_id: '',
        task_description: '',
        task_deadline: '',
    });

    const projectSkillIds = (project.skills ?? []).map((s) => s.id);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(store.url(project));
    }

    return (
        <>
            <Head title={`Assign Employee — ${project.name}`} />

            <div className="space-y-8">
                <Heading
                    title="Assign Employee"
                    description={`Assign an employee to ${project.name}`}
                />

                {/* Suggestions section */}
                <div className="space-y-4">
                    <h2 className="text-base font-semibold">
                        Suggested Employees
                    </h2>

                    {suggestions.length > 0 ? (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/30">
                            <p className="mb-3 text-sm text-blue-700 dark:text-blue-400">
                                These employees have matching skills and are
                                currently on bench.
                            </p>
                            <div className="overflow-hidden rounded-md border border-blue-200 dark:border-blue-800">
                                <table className="w-full text-sm">
                                    <thead className="bg-blue-100/60 dark:bg-blue-900/30">
                                        <tr>
                                            <th className="px-4 py-2 text-left font-medium text-muted-foreground">
                                                Name
                                            </th>
                                            <th className="px-4 py-2 text-left font-medium text-muted-foreground">
                                                Status
                                            </th>
                                            <th className="px-4 py-2 text-left font-medium text-muted-foreground">
                                                Matching Skills
                                            </th>
                                            <th className="px-4 py-2 text-right font-medium text-muted-foreground">
                                                Select
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-blue-200 dark:divide-blue-800">
                                        {suggestions.map((employee) => (
                                            <tr
                                                key={employee.id}
                                                className="bg-white/60 transition-colors hover:bg-blue-50/80 dark:bg-blue-950/20 dark:hover:bg-blue-900/30"
                                            >
                                                <td className="px-4 py-3 font-medium">
                                                    {employee.name}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        className={benchStatusBadgeClass(
                                                            employee.bench_status,
                                                        )}
                                                    >
                                                        {formatBenchStatus(
                                                            employee.bench_status,
                                                        )}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex flex-wrap gap-1">
                                                        {matchingSkills(
                                                            employee,
                                                            projectSkillIds,
                                                        ).map((skill) => (
                                                            <Badge
                                                                key={skill.id}
                                                                variant="secondary"
                                                                className="text-xs"
                                                            >
                                                                {skill.name}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setData(
                                                                'user_id',
                                                                String(
                                                                    employee.id,
                                                                ),
                                                            )
                                                        }
                                                        className={`rounded-md px-3 py-1 text-xs font-medium transition-colors ${
                                                            data.user_id ===
                                                            String(employee.id)
                                                                ? 'bg-primary text-primary-foreground'
                                                                : 'border border-input bg-background hover:bg-muted'
                                                        }`}
                                                    >
                                                        {data.user_id ===
                                                        String(employee.id)
                                                            ? 'Selected'
                                                            : 'Select'}
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ) : (
                        <div className="rounded-lg border border-border bg-muted/30 p-4">
                            <p className="text-sm text-muted-foreground">
                                No employees with matching skills are currently
                                on bench. You can still assign any employee from
                                the list below.
                            </p>
                        </div>
                    )}
                </div>

                {/* Full employee list + assignment form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-4">
                        <h2 className="text-base font-semibold">
                            All Employees
                        </h2>

                        <div className="overflow-hidden rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                            Name
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                            Email
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                            Bench Status
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium text-muted-foreground">
                                            Select
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {employees.map((employee) => (
                                        <tr
                                            key={employee.id}
                                            className={`transition-colors hover:bg-muted/30 ${
                                                data.user_id ===
                                                String(employee.id)
                                                    ? 'bg-primary/5'
                                                    : 'bg-background'
                                            }`}
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {employee.name}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {employee.email}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    className={benchStatusBadgeClass(
                                                        employee.bench_status,
                                                    )}
                                                >
                                                    {formatBenchStatus(
                                                        employee.bench_status,
                                                    )}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setData(
                                                            'user_id',
                                                            String(employee.id),
                                                        )
                                                    }
                                                    className={`rounded-md px-3 py-1 text-xs font-medium transition-colors ${
                                                        data.user_id ===
                                                        String(employee.id)
                                                            ? 'bg-primary text-primary-foreground'
                                                            : 'border border-input bg-background hover:bg-muted'
                                                    }`}
                                                >
                                                    {data.user_id ===
                                                    String(employee.id)
                                                        ? 'Selected'
                                                        : 'Select'}
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                    {employees.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-4 py-8 text-center text-muted-foreground"
                                            >
                                                No active employees found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <InputError message={errors.user_id} />
                    </div>

                    {/* Task details */}
                    <div className="max-w-lg space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="task_description">
                                Task Description
                            </Label>
                            <textarea
                                id="task_description"
                                value={data.task_description}
                                onChange={(e) =>
                                    setData('task_description', e.target.value)
                                }
                                rows={4}
                                placeholder="Describe the employee's task on this project"
                                className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                            />
                            <InputError message={errors.task_description} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="task_deadline">
                                Task Deadline{' '}
                                <span className="text-xs text-muted-foreground">
                                    (must be on or before{' '}
                                    {new Date(
                                        project.deadline,
                                    ).toLocaleDateString()}
                                    )
                                </span>
                            </Label>
                            <Input
                                id="task_deadline"
                                type="date"
                                value={data.task_deadline}
                                max={project.deadline}
                                onChange={(e) =>
                                    setData('task_deadline', e.target.value)
                                }
                            />
                            <InputError message={errors.task_deadline} />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button type="submit" disabled={processing}>
                                Assign Employee
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={show.url(project)}>Cancel</Link>
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </>
    );
}

AssignmentCreate.layout = ({ project }: Props) => ({
    breadcrumbs: [
        {
            title: 'Projects',
            href: index.url(),
        },
        {
            title: project.name,
            href: show.url(project),
        },
        {
            title: 'Assign Employee',
        },
    ],
});
