import { Head, Link } from '@inertiajs/react';
import {
    index,
    create,
    edit,
} from '@/actions/App/Http/Controllers/Skills/SkillCategoryController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import type { SkillCategory } from '@/types';

type Props = {
    categories: SkillCategory[];
    canManageSkills: boolean;
};

export default function SkillCategoriesIndex({
    categories,
    canManageSkills,
}: Props) {
    return (
        <>
            <Head title="Skill Categories" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Skill Categories"
                        description="Manage skill categories"
                    />
                    {canManageSkills && (
                        <Button asChild>
                            <Link href={create.url()}>New Category</Link>
                        </Button>
                    )}
                </div>

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Category Name
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Skills Count
                                </th>
                                {canManageSkills && (
                                    <th className="px-4 py-3 text-right font-medium text-muted-foreground">
                                        Actions
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {categories.map((category) => (
                                <tr
                                    key={category.id}
                                    className="bg-background transition-colors hover:bg-muted/30"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {category.name}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {category.skills_count ?? 0}
                                    </td>
                                    {canManageSkills && (
                                        <td className="px-4 py-3 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={edit.url(category)}>
                                                    Edit
                                                </Link>
                                            </Button>
                                        </td>
                                    )}
                                </tr>
                            ))}
                            {categories.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canManageSkills ? 3 : 2}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No categories found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

SkillCategoriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Skill Categories',
            href: index.url(),
        },
    ],
};
