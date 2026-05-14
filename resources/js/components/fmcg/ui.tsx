import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { Kpi } from '@/lib/fmcg-data';
import { cn } from '@/lib/utils';

type Trend = 'up' | 'down' | 'neutral';

type PageHeaderAction = string | { label: string; href: string };

type PageHeaderProps = {
    eyebrow: string;
    title: string;
    description: string;
    actions?: PageHeaderAction[];
};

export function PageHeader({ eyebrow, title, description, actions = [] }: PageHeaderProps) {
    return (
        <div className="relative overflow-hidden rounded-2xl border border-[#d2dbe3] bg-gradient-to-r from-[#07313f] via-[#0e5f74] to-[#13808d] p-6 text-white">
            <div className="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/15" />
            <p className="text-xs uppercase tracking-[0.25em] text-cyan-100/90">{eyebrow}</p>
            <h1
                className="mt-2 text-3xl font-semibold tracking-tight"
                style={{ fontFamily: 'var(--font-heading)' }}
            >
                {title}
            </h1>
            <p className="mt-2 max-w-3xl text-sm text-cyan-50/90">{description}</p>
            {actions.length > 0 && (
                <div className="mt-5 flex flex-wrap gap-2">
                    {actions.map((action) => (
                        typeof action === 'string' ? (
                            <Button key={action} size="sm" className="bg-white text-[#0d5f74] hover:bg-cyan-50">
                                {action}
                            </Button>
                        ) : (
                            <Button key={`${action.label}-${action.href}`} size="sm" className="bg-white text-[#0d5f74] hover:bg-cyan-50" asChild>
                                <Link href={action.href}>{action.label}</Link>
                            </Button>
                        )
                    ))}
                </div>
            )}
        </div>
    );
}

export function KpiGrid({ items }: { items: Kpi[] }) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            {items.map((item) => (
                <Card key={item.label} className="gap-2 border-[#dce4ea] py-4">
                    <CardHeader className="px-4">
                        <CardDescription className="text-xs uppercase tracking-wide">{item.label}</CardDescription>
                        <CardTitle className="text-2xl font-semibold text-[#102434]">{item.value}</CardTitle>
                    </CardHeader>
                    <CardContent className="px-4 pt-0 text-sm">
                        <TrendPill trend={item.trend}>{item.delta}</TrendPill>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

export function SectionCard({
    title,
    subtitle,
    right,
    className,
    children,
}: {
    title: string;
    subtitle?: string;
    right?: React.ReactNode;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <Card className={cn('gap-4 border-[#dce4ea] py-4', className)}>
            <CardHeader className="flex flex-row items-start justify-between gap-3 px-4">
                <div>
                    <CardTitle className="text-base font-semibold text-[#102434]">{title}</CardTitle>
                    {subtitle ? <CardDescription className="mt-1">{subtitle}</CardDescription> : null}
                </div>
                {right}
            </CardHeader>
            <CardContent className="px-4">{children}</CardContent>
        </Card>
    );
}

export function StatusPill({ value }: { value: string }) {
    const styleMap: Record<string, string> = {
        uploaded: 'bg-slate-100 text-slate-700 border-slate-200',
        validating: 'bg-blue-50 text-blue-700 border-blue-200',
        ready: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        processing: 'bg-cyan-50 text-cyan-700 border-cyan-200',
        failed: 'bg-red-50 text-red-700 border-red-200',
        completed: 'bg-green-50 text-green-700 border-green-200',
        queued: 'bg-slate-100 text-slate-700 border-slate-200',
        running: 'bg-cyan-50 text-cyan-700 border-cyan-200',
        retrying: 'bg-orange-50 text-orange-700 border-orange-200',
        low: 'bg-green-50 text-green-700 border-green-200',
        medium: 'bg-amber-50 text-amber-700 border-amber-200',
        high: 'bg-rose-50 text-rose-700 border-rose-200',
        new: 'bg-slate-100 text-slate-700 border-slate-200',
        allocated: 'bg-cyan-50 text-cyan-700 border-cyan-200',
        partially_fulfilled: 'bg-amber-50 text-amber-700 border-amber-200',
        backordered: 'bg-orange-50 text-orange-700 border-orange-200',
        none: 'bg-green-50 text-green-700 border-green-200',
        qty_mismatch: 'bg-amber-50 text-amber-700 border-amber-200',
        price_mismatch: 'bg-amber-50 text-amber-700 border-amber-200',
        missing_external: 'bg-red-50 text-red-700 border-red-200',
        sync_timeout: 'bg-rose-50 text-rose-700 border-rose-200',
    };

    return (
        <Badge
            variant="outline"
            className={cn(
                'rounded-full border px-2.5 py-1 font-medium capitalize',
                styleMap[value] ?? 'bg-slate-100 text-slate-700 border-slate-200',
            )}
        >
            {value.replaceAll('_', ' ')}
        </Badge>
    );
}

export function MiniBars({
    items,
    keyLabel,
    keyValue,
}: {
    items: Array<Record<string, number | string>>;
    keyLabel: string;
    keyValue: string;
}) {
    const max = Math.max(...items.map((item) => Number(item[keyValue])));

    return (
        <div className="space-y-2">
            {items.map((item) => {
                const label = String(item[keyLabel]);
                const value = Number(item[keyValue]);
                const width = max > 0 ? (value / max) * 100 : 0;

                return (
                    <div key={label} className="space-y-1">
                        <div className="flex items-center justify-between text-xs text-slate-600">
                            <span>{label}</span>
                            <span className="font-medium text-slate-700">{value}</span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div className="h-full rounded-full bg-[#0f718f]" style={{ width: `${width}%` }} />
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

function TrendPill({ trend, children }: { trend: Trend; children: string }) {
    const classes = {
        up: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        down: 'bg-cyan-50 text-cyan-700 border-cyan-200',
        neutral: 'bg-slate-100 text-slate-700 border-slate-200',
    };

    return (
        <span className={cn('inline-flex rounded-full border px-2.5 py-1 text-xs font-medium', classes[trend])}>
            {children}
        </span>
    );
}
