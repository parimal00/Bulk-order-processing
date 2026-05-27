<?php

namespace App\Http\Controllers\Fmcg;

use App\Http\Controllers\Controller;
use App\Models\BulkUpload;
use App\Models\FailedBulkRow;
use App\Models\Order;
use App\Models\OrderIntegration;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();

        return Inertia::render('fmcg/dashboard', [
            'kpis' => $this->buildKpis($today, $yesterday),
            'throughput' => $this->buildThroughput($now),
            'failures' => $this->buildFailureTrend($now),
            'atRisk' => $this->buildAtRisk($now),
            'recentUploads' => $this->buildRecentUploads(),
            'processingJobs' => $this->buildProcessingJobs($now),
            'headerActions' => $this->buildHeaderActions($request),
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string, delta: string, trend: string}>
     */
    private function buildKpis(CarbonInterface $today, CarbonInterface $yesterday): array
    {
        $todayEnd = $today->copy()->endOfDay();
        $yesterdayEnd = $yesterday->copy()->endOfDay();

        $uploadsToday = BulkUpload::query()
            ->whereBetween('created_at', [$today, $todayEnd])
            ->count();
        $uploadsYesterday = BulkUpload::query()
            ->whereBetween('created_at', [$yesterday, $yesterdayEnd])
            ->count();

        $linesToday = (int) BulkUpload::query()
            ->whereBetween('finished_at', [$today, $todayEnd])
            ->sum('valid_rows');
        $linesYesterday = (int) BulkUpload::query()
            ->whereBetween('finished_at', [$yesterday, $yesterdayEnd])
            ->sum('valid_rows');

        $failureRateCurrent = $this->failureRateForRange($today->copy()->subDays(6), $todayEnd);
        $failureRatePrevious = $this->failureRateForRange($today->copy()->subDays(13), $today->copy()->subDays(7)->endOfDay());

        $avgProcessingSecondsToday = $this->averageProcessingSecondsForRange($today, $todayEnd);
        $avgProcessingSecondsYesterday = $this->averageProcessingSecondsForRange($yesterday, $yesterdayEnd);

        $pendingApprovals = Order::query()
            ->where('status', Order::STATUS_PENDING_REVIEW)
            ->count();
        $pendingYesterday = Order::query()
            ->where('status', Order::STATUS_PENDING_REVIEW)
            ->whereBetween('created_at', [$yesterday, $yesterdayEnd])
            ->count();

        return [
            [
                'label' => 'Today Uploads',
                'value' => number_format($uploadsToday),
                'delta' => $this->formatPercentDelta($uploadsToday, $uploadsYesterday),
                'trend' => $this->trendFromDiff($uploadsToday - $uploadsYesterday),
            ],
            [
                'label' => 'Lines Processed',
                'value' => number_format($linesToday),
                'delta' => $this->formatPercentDelta($linesToday, $linesYesterday),
                'trend' => $this->trendFromDiff($linesToday - $linesYesterday),
            ],
            [
                'label' => 'Failure Rate',
                'value' => number_format($failureRateCurrent, 1).'%',
                'delta' => $this->formatPointsDelta($failureRateCurrent, $failureRatePrevious),
                'trend' => $this->trendFromDiff($failureRatePrevious - $failureRateCurrent),
            ],
            [
                'label' => 'Avg Process Time',
                'value' => $this->formatDuration($avgProcessingSecondsToday),
                'delta' => $this->formatDurationDelta($avgProcessingSecondsToday, $avgProcessingSecondsYesterday),
                'trend' => $this->trendFromDiff(($avgProcessingSecondsYesterday ?? 0) - ($avgProcessingSecondsToday ?? 0)),
            ],
            [
                'label' => 'Pending Approvals',
                'value' => number_format($pendingApprovals),
                'delta' => $this->formatPercentDelta($pendingApprovals, $pendingYesterday),
                'trend' => $this->trendFromDiff($pendingYesterday - $pendingApprovals),
            ],
        ];
    }

    /**
     * @return array<int, array{hour: string, lines: int}>
     */
    private function buildThroughput(CarbonInterface $now): array
    {
        $startHour = $now->copy()->subHours(7)->startOfHour();

        $buckets = [];
        for ($i = 0; $i < 8; $i++) {
            $label = $startHour->copy()->addHours($i)->format('H:00');
            $buckets[$label] = 0;
        }

        $uploads = BulkUpload::query()
            ->whereBetween('finished_at', [$startHour, $now])
            ->get(['finished_at', 'valid_rows']);

        foreach ($uploads as $upload) {
            if (! $upload->finished_at) {
                continue;
            }

            $label = $upload->finished_at->copy()->startOfHour()->format('H:00');
            if (! array_key_exists($label, $buckets)) {
                continue;
            }

            $buckets[$label] += (int) $upload->valid_rows;
        }

        $result = [];
        foreach ($buckets as $hour => $lines) {
            $result[] = ['hour' => $hour, 'lines' => $lines];
        }

        return $result;
    }

    /**
     * @return array<int, array{day: string, count: int}>
     */
    private function buildFailureTrend(CarbonInterface $now): array
    {
        $startDay = $now->copy()->subDays(6)->startOfDay();
        $result = [];

        for ($i = 0; $i < 7; $i++) {
            $dayStart = $startDay->copy()->addDays($i);
            $dayEnd = $dayStart->copy()->endOfDay();

            $validationFailures = FailedBulkRow::query()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

            $processingFailures = BulkUpload::query()
                ->where('status', BulkUpload::STATUS_INVALID)
                ->whereBetween('updated_at', [$dayStart, $dayEnd])
                ->count();

            $syncFailures = OrderIntegration::query()
                ->where('status', OrderIntegration::STATUS_FAILED)
                ->whereBetween('updated_at', [$dayStart, $dayEnd])
                ->count();

            $result[] = [
                'day' => $dayStart->format('D'),
                'count' => $validationFailures + $processingFailures + $syncFailures,
            ];
        }

        return $result;
    }

    /**
     * @return array{
     *   sync: array{title: string, detail: string, tone: string},
     *   approvals: array{title: string, detail: string, tone: string},
     *   inventory: array{title: string, detail: string, tone: string}
     * }
     */
    private function buildAtRisk(CarbonInterface $now): array
    {
        $stalledSync = OrderIntegration::query()
            ->with('order:id,order_number')
            ->where('status', OrderIntegration::STATUS_FAILED)
            ->latest('updated_at')
            ->first();

        $pendingApprovals = Order::query()
            ->where('status', Order::STATUS_PENDING_REVIEW)
            ->count();
        $approvalThreshold = 10;

        $orders = Order::query()
            ->with('lines:id,order_id,quantity,backorder_quantity')
            ->whereNotNull('bulk_upload_id')
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->get(['id', 'bulk_upload_id']);

        $riskUploadIds = [];
        foreach ($orders as $order) {
            $requested = (int) $order->lines->sum('quantity');
            $backordered = (int) $order->lines->sum('backorder_quantity');

            if ($requested <= 0) {
                continue;
            }

            if (($backordered / $requested) > 0.25) {
                $riskUploadIds[] = (int) $order->bulk_upload_id;
            }
        }

        $inventoryRiskCount = count(array_unique($riskUploadIds));

        return [
            'sync' => $stalledSync
                ? [
                    'title' => 'ERP sync stalled: '.($stalledSync->order?->order_number ?? 'Unknown order'),
                    'detail' => "{$stalledSync->attempt_count} attempts. Last update {$stalledSync->updated_at?->format('H:i')}.",
                    'tone' => 'critical',
                ]
                : [
                    'title' => 'ERP sync healthy',
                    'detail' => 'No failed sync records currently require manual action.',
                    'tone' => 'ok',
                ],
            'approvals' => [
                'title' => $pendingApprovals > $approvalThreshold
                    ? 'Approval queue above threshold'
                    : 'Approval queue within threshold',
                'detail' => "{$pendingApprovals} pending approvals, SLA target is {$approvalThreshold}.",
                'tone' => $pendingApprovals > $approvalThreshold ? 'warning' : 'ok',
            ],
            'inventory' => [
                'title' => $inventoryRiskCount > 0 ? 'Inventory split risk' : 'Inventory split ratio healthy',
                'detail' => "{$inventoryRiskCount} uploads with backorder ratio above 25% in the last 7 days.",
                'tone' => $inventoryRiskCount > 0 ? 'notice' : 'ok',
            ],
        ];
    }

    /**
     * @return array<int, array{id: string, customer: string, rows: int, validPercent: int, status: string}>
     */
    private function buildRecentUploads(): array
    {
        return BulkUpload::query()
            ->with('customer:id,name')
            ->latest('id')
            ->take(6)
            ->get()
            ->map(function (BulkUpload $upload): array {
                $totalRows = max(0, (int) $upload->total_rows);
                $validRows = max(0, (int) $upload->valid_rows);

                return [
                    'id' => "UPL-{$upload->id}",
                    'customer' => $upload->customer?->name ?? 'Unknown',
                    'rows' => $totalRows,
                    'validPercent' => $totalRows > 0 ? (int) round(($validRows / $totalRows) * 100) : 0,
                    'status' => $this->mapUploadStatus($upload->status),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, uploadId: string, step: string, progress: int, status: string, elapsed: string}>
     */
    private function buildProcessingJobs(CarbonInterface $now): array
    {
        return BulkUpload::query()
            ->whereIn('status', [
                BulkUpload::STATUS_UPLOADED,
                BulkUpload::STATUS_VALIDATING,
                BulkUpload::STATUS_PROCESSING,
            ])
            ->latest('updated_at')
            ->take(5)
            ->get(['id', 'status', 'total_rows', 'valid_rows', 'invalid_rows', 'created_at'])
            ->map(function (BulkUpload $upload) use ($now): array {
                $rowsProcessed = (int) $upload->valid_rows + (int) $upload->invalid_rows;
                $totalRows = max(0, (int) $upload->total_rows);

                $progress = match ($upload->status) {
                    BulkUpload::STATUS_UPLOADED => 0,
                    BulkUpload::STATUS_VALIDATING => $totalRows > 0 ? min(95, (int) round(($rowsProcessed / $totalRows) * 100)) : 20,
                    BulkUpload::STATUS_PROCESSING => 70,
                    default => 0,
                };

                $step = match ($upload->status) {
                    BulkUpload::STATUS_UPLOADED => 'Awaiting Mapping',
                    BulkUpload::STATUS_VALIDATING => 'Validation Pipeline',
                    BulkUpload::STATUS_PROCESSING => 'Pricing and Allocation',
                    default => 'Queued',
                };

                $status = match ($upload->status) {
                    BulkUpload::STATUS_UPLOADED => 'queued',
                    BulkUpload::STATUS_VALIDATING, BulkUpload::STATUS_PROCESSING => 'running',
                    default => 'queued',
                };

                return [
                    'id' => "JOB-UPL-{$upload->id}",
                    'uploadId' => "UPL-{$upload->id}",
                    'step' => $step,
                    'progress' => max(0, min(100, $progress)),
                    'status' => $status,
                    'elapsed' => $upload->created_at ? $this->formatDuration((float) $upload->created_at->diffInSeconds($now)) : 'N/A',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, href: string}>
     */
    private function buildHeaderActions(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        if ($user->hasRole('ops')) {
            return [
                ['label' => 'New Upload', 'href' => '/fmcg/uploads/new'],
                ['label' => 'Review Exceptions', 'href' => '/fmcg/uploads'],
            ];
        }

        if ($user->hasRole('approver')) {
            return [
                ['label' => 'Pending Approvals', 'href' => '/fmcg/approvals'],
                ['label' => 'Run Reconciliation', 'href' => '/fmcg/reconciliation'],
            ];
        }

        return [
            ['label' => 'New Upload', 'href' => '/fmcg/uploads/new'],
            ['label' => 'Review Exceptions', 'href' => '/fmcg/approvals'],
            ['label' => 'Run Reconciliation', 'href' => '/fmcg/reconciliation'],
        ];
    }

    private function failureRateForRange(CarbonInterface $start, CarbonInterface $end): float
    {
        $uploads = BulkUpload::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['total_rows', 'invalid_rows']);

        $totalRows = (int) $uploads->sum('total_rows');
        $invalidRows = (int) $uploads->sum('invalid_rows');

        if ($totalRows === 0) {
            return 0.0;
        }

        return ($invalidRows / $totalRows) * 100;
    }

    private function averageProcessingSecondsForRange(CarbonInterface $start, CarbonInterface $end): ?float
    {
        $durations = BulkUpload::query()
            ->whereBetween('finished_at', [$start, $end])
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->get(['started_at', 'finished_at'])
            ->map(function (BulkUpload $upload): int {
                return $upload->started_at?->diffInSeconds($upload->finished_at) ?? 0;
            })
            ->filter(fn (int $seconds): bool => $seconds > 0);

        if ($durations->isEmpty()) {
            return null;
        }

        return (float) $durations->avg();
    }

    private function mapUploadStatus(string $status): string
    {
        return match ($status) {
            BulkUpload::STATUS_VALID => 'ready',
            BulkUpload::STATUS_FAILED_ROWS, BulkUpload::STATUS_INVALID => 'failed',
            BulkUpload::STATUS_PROCESSED => 'completed',
            default => $status,
        };
    }

    private function formatPercentDelta(int $current, int $previous): string
    {
        if ($previous === 0) {
            if ($current === 0) {
                return '0%';
            }

            return '+100%';
        }

        $delta = (($current - $previous) / $previous) * 100;
        $prefix = $delta > 0 ? '+' : '';

        return $prefix.number_format($delta, 0).'%';
    }

    private function formatPointsDelta(float $current, float $previous): string
    {
        $delta = $current - $previous;
        $prefix = $delta > 0 ? '+' : '';

        return $prefix.number_format($delta, 1).'pp';
    }

    private function formatDurationDelta(?float $current, ?float $previous): string
    {
        if ($current === null || $previous === null) {
            return 'N/A';
        }

        $delta = (int) round($current - $previous);
        $prefix = $delta > 0 ? '+' : '';

        return $prefix.$this->formatDuration((float) abs($delta));
    }

    private function formatDuration(?float $seconds): string
    {
        if ($seconds === null) {
            return 'N/A';
        }

        $secondsInt = (int) round($seconds);
        $minutes = intdiv($secondsInt, 60);
        $remainingSeconds = $secondsInt % 60;

        return sprintf('%dm %02ds', $minutes, $remainingSeconds);
    }

    private function trendFromDiff(float|int $diff): string
    {
        if ($diff > 0) {
            return 'up';
        }

        if ($diff < 0) {
            return 'down';
        }

        return 'neutral';
    }
}
