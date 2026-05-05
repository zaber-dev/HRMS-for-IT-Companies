import { Form, Head, Link } from '@inertiajs/react';
import { index, create, store } from '@/actions/App/Http/Controllers/Leave/LeaveRequestController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function LeaveRequestCreate() {
    return (
        <>
            <Head title="New Leave Request" />

            <div className="space-y-6">
                <Heading title="New Leave Request" description="Submit a PTO leave request for approval" />

                <Form {...store.form()} className="space-y-6 max-w-lg">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    name="start_date"
                                    type="date"
                                    required
                                />
                                <InputError message={errors.start_date} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    name="end_date"
                                    type="date"
                                    required
                                />
                                <InputError message={errors.end_date} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reason">Reason <span className="text-muted-foreground">(optional)</span></Label>
                                <textarea
                                    id="reason"
                                    name="reason"
                                    rows={4}
                                    placeholder="Briefly describe the reason for your leave request"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                />
                                <InputError message={errors.reason} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Submit Request
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

LeaveRequestCreate.layout = {
    breadcrumbs: [
        {
            title: 'My Leave Requests',
            href: index.url(),
        },
        {
            title: 'New Leave Request',
            href: create.url(),
        },
    ],
};
