import { Form, Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    index,
    create,
    show,
    edit,
    destroy,
    toggle,
} from '@/actions/App/Http/Controllers/Skills/SkillController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { PaginatedData, Skill, SkillCategory } from '@/types';

type Props = {
    skills: PaginatedData<Skill & { skill_category: SkillCategory }>;
    categories: SkillCategory[];
    filters: { category?: number; is_active?: string };
    canManageSkills: boolean;
};

const activeOptions = [
    { value: 'all', label: 'All' },
    { value: '1', label: 'Active' },
    { value: '0', label: 'Inactive' },
];

export default function SkillsIndex({
    skills,
    categories,
    filters,
    canManageSkills,
}: Props) {
    const [category, setCategory] = useState<string>(filters.category ? String(filters.category) : '');
    const [isActive, setIsActive] = useState<string>(filters.is_active ?? '');

    function handleCategoryChange(value: string) {
        setCategory(value);
        router.get(
            index.url(),
            {
                category: value || undefined,
                is_active: isActive || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    function handleActiveChange(value: string) {
        // Treat 'all' as clearing the filter
        const newValue = value === 'all' ? '' : value;
        setIsActive(newValue);
        router.get(
            index.url(),
            {
                category: category || undefined,
                is_active: newValue || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Skills" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Skills"
                        description="Browse and manage the skill catalogue"
                    />
                    {canManageSkills && (
                        <Button asChild>
                            <Link href={create.url()}>New Skill</Link>
                        </Button>
                    )}
                </div>

                <div className="flex items-center gap-4">
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium text-muted-foreground">
                            Category:
                        </span>
                        <Select value={category} onValueChange={handleCategoryChange}>
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="All categories" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((cat) => (
                                    <SelectItem key={cat.id} value={String(cat.id)}>
                                        {cat.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium text-muted-foreground">
                            Status:
                        </span>
                        <Select value={isActive || undefined} onValueChange={handleActiveChange}>
                            <SelectTrigger className="w-32">
                                <SelectValue placeholder="All" />
                            </SelectTrigger>
                            <SelectContent>
                                {activeOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Name
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Category
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Description
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Status
                                </th>
                                {canManageSkills && (
                                    <th className="px-4 py-3 text-right font-medium text-muted-foreground">
                                        Actions
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {skills.data.map((skill) => (
                                <tr
                                    key={skill.id}
                                    className="bg-background transition-colors hover:bg-muted/30"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        <Link
                                            href={show.url(skill)}
                                            className="hover:underline"
                                        >
                                            {skill.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge variant="secondary">
                                            {skill.skill_category.name}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {skill.description ? (
                                            skill.description.length > 60 ? (
                                                skill.description.slice(0, 60) +
                                                '…'
                                            ) : (
                                                skill.description
                                            )
                                        ) : (
                                            <span className="italic">
                                                No description
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        {skill.is_active ? (
                                            <Badge className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                Active
                                            </Badge>
                                        ) : (
                                            <Badge className="bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                                                Inactive
                                            </Badge>
                                        )}
                                    </td>
                                    {canManageSkills && (
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit.url(skill)}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={toggle.url(skill)}
                                                    method="patch"
                                                    className="inline"
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            type="submit"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            {skill.is_active
                                                                ? 'Deactivate'
                                                                : 'Activate'}
                                                        </Button>
                                                    )}
                                                </Form>
                                                <Form
                                                    action={destroy.url(skill)}
                                                    method="delete"
                                                    className="inline"
                                                    onBefore={() =>
                                                        window.confirm(
                                                            'Are you sure you want to delete this skill?',
                                                        )
                                                    }
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            type="submit"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Delete
                                                        </Button>
                                                    )}
                                                </Form>
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))}
                            {skills.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canManageSkills ? 5 : 4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No skills found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {skills.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1">
                        {skills.links.map((link, i) => (
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

SkillsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Skills',
            href: index.url(),
        },
    ],
};
