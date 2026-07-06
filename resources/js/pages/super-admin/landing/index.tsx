import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { PageLoader } from '@/components/ui/spinner';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/utils';
import {
    Layout,
    Save,
    Send,
    Eye,
    Clock,
    RotateCcw,
    ChevronDown,
    ChevronUp,
} from 'lucide-react';

interface Section {
    type: string;
    id: string;
    data: Record<string, unknown>;
}

export default function SuperAdminLandingPage() {
    const queryClient = useQueryClient();
    const [sections, setSections] = useState<Section[] | null>(null);
    const [expandedSection, setExpandedSection] = useState<string | null>(null);
    const [metaTitle, setMetaTitle] = useState('');
    const [metaDescription, setMetaDescription] = useState('');
    const [statusMessage, setStatusMessage] = useState('');
    const [loadingRevisions, setLoadingRevisions] = useState(false);

    const { data: landing, isLoading } = useQuery({
        queryKey: ['super-admin', 'landing'],
        queryFn: () => superAdminService.getLanding(),
        select: (res) => res.data,
    });

    useEffect(() => {
        if (landing && !sections) {
            const content = landing.content as { sections: Section[] };
            setSections(content?.sections ?? []);
            setMetaTitle((landing.meta_title as string) ?? '');
            setMetaDescription((landing.meta_description as string) ?? '');
        }
    }, [landing]);

    const statusTimeout = (msg: string) => {
        setStatusMessage(msg);
        setTimeout(() => setStatusMessage(''), 3000);
    };

    const saveMutation = useMutation({
        mutationFn: (data: { content: { sections: Section[] }; meta_title: string; meta_description: string }) =>
            superAdminService.updateLanding(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'landing'] });
            statusTimeout('Draft saved.');
        },
        onError: (err: Error) => {
            statusTimeout(err.message || 'Save failed.');
        },
    });

    const publishMutation = useMutation({
        mutationFn: () => superAdminService.publishLanding(),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'landing'] });
            statusTimeout('Published!');
        },
        onError: (err: Error) => {
            statusTimeout(err.message || 'Publish failed.');
        },
    });

    const restoreMutation = useMutation({
        mutationFn: (revisionId: number) => superAdminService.restoreLandingRevision(revisionId),
        onSuccess: (res) => {
            const page = res.data as Record<string, unknown>;
            queryClient.setQueryData(['super-admin', 'landing'], res);
            setSections((page.content as { sections?: Section[] })?.sections ?? []);
            setMetaTitle((page.meta_title as string) ?? '');
            setMetaDescription((page.meta_description as string) ?? '');
            statusTimeout('Revision restored.');
        },
        onError: (err: Error) => {
            statusTimeout(err.message || 'Restore failed.');
        },
    });

    const { data: revisionsData, refetch: fetchRevisions } = useQuery({
        queryKey: ['super-admin', 'landing', 'revisions'],
        queryFn: () => superAdminService.getLandingRevisions(),
        enabled: false,
    });

    if (isLoading) return <PageLoader />;

    const updateSection = (id: string, key: string, value: string) => {
        if (!sections) return;
        setSections(
            sections.map((s) => {
                if (s.id !== id) return s;
                return { ...s, data: { ...s.data, [key]: value } };
            })
        );
    };

    const sectionNestedKey: Record<string, string> = {
        compliance_bar: 'items',
        module_showcase: 'modules',
        how_it_works: 'steps',
        workflow_diagram: 'nodes',
        faq: 'items',
        operator_signal: 'attribution_rows',
    };

    const updateNestedItem = (sectionId: string, itemIndex: number, key: string, value: string) => {
        if (!sections) return;
        setSections(
            sections.map((s) => {
                if (s.id !== sectionId) return s;
                const sourceKey = sectionNestedKey[s.type] ?? 'items';
                const items = [...((s.data[sourceKey] ?? []) as Record<string, unknown>[])];
                if (items[itemIndex]) {
                    items[itemIndex] = { ...items[itemIndex], [key]: value };
                }
                return {
                    ...s,
                    data: {
                        ...s.data,
                        [sourceKey]: items,
                    },
                };
            })
        );
    };

    const sectionLabels: Record<string, string> = {
        nav: 'Navigation',
        hero: 'Hero',
        compliance_bar: 'Compliance Bar',
        module_showcase: 'Module Showcase',
        how_it_works: 'How It Works',
        workflow_diagram: 'Workflow Diagram',
        pricing: 'Pricing',
        operator_signal: 'Operator Signal',
        faq: 'FAQ',
        cta: 'Call To Action',
        footer: 'Footer',
    };

    const renderSectionEditor = (section: Section) => {
        const d = section.data || {};

        switch (section.type) {
            case 'nav':
                return (
                    <div className="space-y-3">
                        <Input label="Logo Text" value={(d.logo_text as string) ?? ''} onChange={(e) => updateSection(section.id, 'logo_text', e.target.value)} />
                        <Input label="CTA Text" value={(d.cta_text as string) ?? ''} onChange={(e) => updateSection(section.id, 'cta_text', e.target.value)} />
                        <Input label="CTA URL" value={(d.cta_url as string) ?? ''} onChange={(e) => updateSection(section.id, 'cta_url', e.target.value)} />
                        <Input label="Sign In Text" value={(d.signin_text as string) ?? ''} onChange={(e) => updateSection(section.id, 'signin_text', e.target.value)} />
                        <Input label="Sign In URL" value={(d.signin_url as string) ?? ''} onChange={(e) => updateSection(section.id, 'signin_url', e.target.value)} />
                    </div>
                );
            case 'hero':
                return (
                    <div className="space-y-3">
                        <Input label="Eyebrow Badge" value={(d.eyebrow as string) ?? ''} onChange={(e) => updateSection(section.id, 'eyebrow', e.target.value)} />
                        <Input label="Heading" value={(d.heading as string) ?? ''} onChange={(e) => updateSection(section.id, 'heading', e.target.value)} />
                        <div>
                            <label className="text-sm font-medium">Subheading</label>
                            <textarea
                                value={(d.subheading as string) ?? ''}
                                onChange={(e) => updateSection(section.id, 'subheading', e.target.value)}
                                className="mt-1 flex min-h-[80px] w-full rounded-md border border-border bg-surface px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                aria-label="Hero subheading"
                            />
                        </div>
                        <Input label="Primary CTA Text" value={(d.primary_cta_text as string) ?? ''} onChange={(e) => updateSection(section.id, 'primary_cta_text', e.target.value)} />
                        <Input label="Primary CTA URL" value={(d.primary_cta_url as string) ?? ''} onChange={(e) => updateSection(section.id, 'primary_cta_url', e.target.value)} />
                        <Input label="Secondary CTA Text" value={(d.secondary_cta_text as string) ?? ''} onChange={(e) => updateSection(section.id, 'secondary_cta_text', e.target.value)} />
                        <Input label="Secondary CTA URL" value={(d.secondary_cta_url as string) ?? ''} onChange={(e) => updateSection(section.id, 'secondary_cta_url', e.target.value)} />
                    </div>
                );
            case 'module_showcase':
                return (
                    <div className="space-y-3">
                        <Input label="Heading" value={(d.heading as string) ?? ''} onChange={(e) => updateSection(section.id, 'heading', e.target.value)} />
                        <Input label="Subheading" value={(d.subheading as string) ?? ''} onChange={(e) => updateSection(section.id, 'subheading', e.target.value)} />
                        {(d.modules as Record<string, string>[] ?? []).map((item, i) => (
                            <Card key={i}>
                                <CardContent className="space-y-2 p-4">
                                    <p className="text-xs font-medium uppercase text-text-muted">Module {i + 1}</p>
                                    <Input label="Number" value={item.number ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'number', e.target.value)} />
                                    <Input label="Title" value={item.title ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'title', e.target.value)} />
                                    <div>
                                        <label className="text-sm font-medium">Description</label>
                                        <textarea
                                            value={item.description ?? ''}
                                            onChange={(e) => updateNestedItem(section.id, i, 'description', e.target.value)}
                                            className="mt-1 flex min-h-[60px] w-full rounded-md border border-border bg-surface px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                            aria-label={`Module ${i + 1} description`}
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                );
            case 'compliance_bar':
                return (
                    <div className="space-y-3">
                        {(d.items as Record<string, string>[] ?? []).map((item, i) => (
                            <Card key={i}>
                                <CardContent className="space-y-2 p-4">
                                    <p className="text-xs font-medium uppercase text-text-muted">Compliance {i + 1}</p>
                                    <Input label="Label" value={item.label ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'label', e.target.value)} />
                                    <Input label="Detail" value={item.detail ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'detail', e.target.value)} />
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                );
            case 'how_it_works':
                return (
                    <div className="space-y-3">
                        <Input label="Heading" value={(d.heading as string) ?? ''} onChange={(e) => updateSection(section.id, 'heading', e.target.value)} />
                        {(d.steps as Record<string, string>[] ?? []).map((step, i) => (
                            <Card key={i}>
                                <CardContent className="space-y-2 p-4">
                                    <p className="text-xs font-medium uppercase text-text-muted">Step {i + 1}</p>
                                    <Input label="Title" value={step.title ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'title', e.target.value)} />
                                    <div>
                                        <label className="text-sm font-medium">Description</label>
                                        <textarea
                                            value={step.description ?? ''}
                                            onChange={(e) => updateNestedItem(section.id, i, 'description', e.target.value)}
                                            className="mt-1 flex min-h-[60px] w-full rounded-md border border-border bg-surface px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                            aria-label={`Step ${i + 1} description`}
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                );
            case 'stats':
            case 'workflow_diagram':
                return (
                    <div className="space-y-3">
                        <Input label="Heading" value={(d.heading as string) ?? ''} onChange={(e) => updateSection(section.id, 'heading', e.target.value)} />
                        {(d.nodes as Record<string, string>[] ?? []).map((node, i) => (
                            <Input key={i} label={`Node ${i + 1}`} value={node.label ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'label', e.target.value)} />
                        ))}
                    </div>
                );
            case 'operator_signal':
                return (
                    <div className="space-y-3">
                        <Input label="Heading" value={(d.heading as string) ?? ''} onChange={(e) => updateSection(section.id, 'heading', e.target.value)} />
                        <div>
                            <label className="text-sm font-medium">Quote</label>
                            <textarea
                                value={(d.quote as string) ?? ''}
                                onChange={(e) => updateSection(section.id, 'quote', e.target.value)}
                                className="mt-1 flex min-h-[60px] w-full rounded-md border border-border bg-surface px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                aria-label="Operator signal quote"
                            />
                        </div>
                        {(d.attribution_rows as Record<string, string>[] ?? []).map((item, i) => (
                            <Card key={i}>
                                <CardContent className="space-y-2 p-4">
                                    <p className="text-xs font-medium uppercase text-text-muted">Attribution {i + 1}</p>
                                    <Input label="Name" value={item.name ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'name', e.target.value)} />
                                    <Input label="Pharmacy" value={item.pharmacy ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'pharmacy', e.target.value)} />
                                    <Input label="Location" value={item.location ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'location', e.target.value)} />
                                    <Input label="Since" value={item.since ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'since', e.target.value)} />
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                );
            case 'faq':
                return (
                    <div className="space-y-3">
                        <Input label="Heading" value={(d.heading as string) ?? ''} onChange={(e) => updateSection(section.id, 'heading', e.target.value)} />
                        {(d.items as Record<string, string>[] ?? []).map((item, i) => (
                            <Card key={i}>
                                <CardContent className="space-y-2 p-4">
                                    <p className="text-xs font-medium uppercase text-text-muted">FAQ {i + 1}</p>
                                    <Input label="Question" value={item.question ?? ''} onChange={(e) => updateNestedItem(section.id, i, 'question', e.target.value)} />
                                    <div>
                                        <label className="text-sm font-medium">Answer</label>
                                        <textarea
                                            value={item.answer ?? ''}
                                            onChange={(e) => updateNestedItem(section.id, i, 'answer', e.target.value)}
                                            className="mt-1 flex min-h-[60px] w-full rounded-md border border-border bg-surface px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                            aria-label={`FAQ ${i + 1} answer`}
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                );
            case 'cta':
                return (
                    <div className="space-y-3">
                        <Input label="Heading" value={(d.heading as string) ?? ''} onChange={(e) => updateSection(section.id, 'heading', e.target.value)} />
                        <Input label="Subheading" value={(d.subheading as string) ?? ''} onChange={(e) => updateSection(section.id, 'subheading', e.target.value)} />
                        <Input label="Button Text" value={(d.button_text as string) ?? ''} onChange={(e) => updateSection(section.id, 'button_text', e.target.value)} />
                        <Input label="Button URL" value={(d.button_url as string) ?? ''} onChange={(e) => updateSection(section.id, 'button_url', e.target.value)} />
                    </div>
                );
            case 'footer':
                return (
                    <div className="space-y-3">
                        <Input label="Logo Text" value={(d.logo_text as string) ?? ''} onChange={(e) => updateSection(section.id, 'logo_text', e.target.value)} />
                        <div>
                            <label className="text-sm font-medium">Description</label>
                            <textarea
                                value={(d.description as string) ?? ''}
                                onChange={(e) => updateSection(section.id, 'description', e.target.value)}
                                className="mt-1 flex min-h-[60px] w-full rounded-md border border-border bg-surface px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                aria-label="Footer description"
                            />
                        </div>
                    </div>
                );
            case 'pricing':
                return (
                    <div className="space-y-3">
                        <Input label="Heading" value={String(d.heading ?? '')} onChange={(e) => updateSection(section.id, 'heading', e.target.value)} />
                        <Input label="Subheading" value={String(d.subheading ?? '')} onChange={(e) => updateSection(section.id, 'subheading', e.target.value)} />
                    </div>
                );
            default:
                return <p className="text-sm text-text-muted">No editable fields for this section.</p>;
        }
    };

    const previewToken = (landing as Record<string, unknown> | undefined)?.preview_token;
    const previewUrl = previewToken ? `/?preview_token=${previewToken}` : '/';

    const handleSave = () => {
        if (!sections) return;
        saveMutation.mutate({
            content: { sections },
            meta_title: metaTitle,
            meta_description: metaDescription,
        });
    };

    const handleToggleRevisions = () => {
        setLoadingRevisions(true);
        fetchRevisions().finally(() => setLoadingRevisions(false));
    };

    const revisions = (revisionsData as unknown as { data: Array<Record<string, unknown>> })?.data ?? [];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Landing Page Editor</h1>
                <div className="flex items-center gap-2">
                    {statusMessage && (
                        <span className="text-sm text-text">{statusMessage}</span>
                    )}
                    <Button variant="outline" onClick={() => window.open(previewUrl, '_blank')}>
                        <Eye className="mr-2 h-4 w-4" /> Preview
                    </Button>
                    <Button variant="outline" onClick={handleSave} loading={saveMutation.isPending}>
                        <Save className="mr-2 h-4 w-4" /> Save Draft
                    </Button>
                    <Button onClick={() => publishMutation.mutate()} loading={publishMutation.isPending}>
                        <Send className="mr-2 h-4 w-4" /> Publish
                    </Button>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <Input label="Meta Title" value={metaTitle} maxLength={255} onChange={(e) => setMetaTitle(e.target.value)} />
                <Input label="Meta Description" value={metaDescription} maxLength={500} onChange={(e) => setMetaDescription(e.target.value)} />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Layout className="h-5 w-5" />
                        Sections
                        {(landing as Record<string, unknown> | undefined)?.published_at ? (
                            <Badge variant="success" className="ml-2">Published</Badge>
                        ) : (
                            <Badge variant="outline" className="ml-2">Unpublished</Badge>
                        )}
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {sections?.map((section) => (
                        <Card key={section.id}>
                            <CardContent className="p-0">
                                <button
                                    onClick={() => setExpandedSection(expandedSection === section.id ? null : section.id)}
                                    className="flex w-full items-center justify-between p-4 hover:bg-surface-muted transition-colors"
                                >
                                    <div className="flex items-center gap-2">
                                        <span className="text-xs font-medium uppercase text-text-muted w-8">
                                            {sections.indexOf(section) + 1}
                                        </span>
                                        <span className="font-medium">{sectionLabels[section.type] ?? section.type}</span>
                                    </div>
                                    {expandedSection === section.id ? (
                                        <ChevronUp className="h-4 w-4 text-text-muted" />
                                    ) : (
                                        <ChevronDown className="h-4 w-4 text-text-muted" />
                                    )}
                                </button>
                                {expandedSection === section.id && (
                                    <div className="border-t border-border p-4">
                                        {renderSectionEditor(section)}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Clock className="h-5 w-5" />
                        Revision History
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <Button variant="outline" size="sm" onClick={handleToggleRevisions} loading={loadingRevisions} className="mb-4">
                        <Clock className="mr-2 h-4 w-4" /> Load Revisions
                    </Button>
                    {revisions.length > 0 ? (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>By</TableHead>
                                    <TableHead>Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {revisions.map((rev: Record<string, unknown>) => (
                                    <TableRow key={rev.id as number}>
                                        <TableCell className="text-sm text-text-muted">
                                            {formatDate(rev.created_at as string)}
                                        </TableCell>
                                        <TableCell>{(rev.created_by as Record<string, string>)?.name ?? '—'}</TableCell>
                                        <TableCell>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => restoreMutation.mutate(rev.id as number)}
                                                loading={restoreMutation.isPending}
                                            >
                                                <RotateCcw className="mr-2 h-3 w-3" /> Restore
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    ) : (
                        <p className="text-sm text-text-muted">Click load to see revision history.</p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
