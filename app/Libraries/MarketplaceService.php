<?php

namespace App\Libraries;

use App\Models\DataProviderModel;
use App\Models\DownloadTransactionModel;
use App\Models\RevenueShareModel;
use App\Models\SubscriptionModel;

class MarketplaceService
{
    private const PLATFORM_PCT = 0.25; // 25% retained by Gravport
    private const PROVIDER_PCT = 0.75; // 75% to data provider

    private DataProviderModel      $providers;
    private SubscriptionModel      $subscriptions;
    private DownloadTransactionModel $transactions;
    private RevenueShareModel      $revenueShares;

    public function __construct()
    {
        $this->providers     = new DataProviderModel();
        $this->subscriptions = new SubscriptionModel();
        $this->transactions  = new DownloadTransactionModel();
        $this->revenueShares = new RevenueShareModel();
    }

    // ── Quota ────────────────────────────────────────────────────────

    /**
     * Check whether a user may download.
     *
     * Returns:
     *   ['allowed'=>bool, 'used'=>int, 'limit'=>int|null, 'tier'=>string, 'reason'=>string]
     *
     * reason: 'ok' | 'no_subscription' | 'quota_exceeded'
     * used/limit are bytes for Solo tier, 0/null for unlimited tiers.
     */
    public function checkQuota(int $userId): array
    {
        if ($userId <= 0) {
            return ['allowed' => false, 'used' => 0, 'limit' => 0, 'tier' => 'none', 'reason' => 'no_subscription'];
        }

        $sub = $this->subscriptions->activeFor($userId);

        if ($sub === null) {
            return ['allowed' => false, 'used' => 0, 'limit' => 0, 'tier' => 'none', 'reason' => 'no_subscription'];
        }

        $tier       = strtolower($sub['tier_name'] ?? 'none');
        $limitBytes = isset($sub['download_limit_bytes_week']) && $sub['download_limit_bytes_week'] !== null
            ? (int) $sub['download_limit_bytes_week']
            : null;

        if ($limitBytes === null) {
            // Pro, Team, or legacy enterprise/government — unlimited
            return ['allowed' => true, 'used' => 0, 'limit' => null, 'tier' => $tier, 'reason' => 'ok'];
        }

        // Solo — check weekly bytes quota
        $usedBytes = $this->subscriptions->weeklyDownloadBytes($userId);

        if ($usedBytes >= $limitBytes) {
            return ['allowed' => false, 'used' => $usedBytes, 'limit' => $limitBytes, 'tier' => $tier, 'reason' => 'quota_exceeded'];
        }

        return ['allowed' => true, 'used' => $usedBytes, 'limit' => $limitBytes, 'tier' => $tier, 'reason' => 'ok'];
    }

    // ── Logging ──────────────────────────────────────────────────────

    /**
     * Log a completed download and compute the revenue split.
     * Returns the new transaction_id.
     */
    public function logDownload(array $params): int
    {
        $userId      = (int) ($params['user_id'] ?? 0) ?: null;
        $datasetCode = (string) ($params['dataset_code'] ?? '');
        $datasetType = (string) ($params['dataset_type'] ?? 'vector');
        $amount      = (float) ($params['transaction_amount'] ?? 0);
        $rowCount    = $params['row_count'] ?? null;
        $sizeBytes   = $params['download_size_bytes'] ?? null;
        $filterParams = $params['filter_params'] ?? [];

        $commission    = round($amount * self::PLATFORM_PCT, 2);
        $providerShare = round($amount * self::PROVIDER_PCT, 2);

        $provider   = $this->providers->findByDatasetCode($datasetCode);
        $providerId = $provider ? (int) $provider['provider_id'] : null;

        return $this->transactions->log([
            'user_id'             => $userId,
            'provider_id'         => $providerId,
            'dataset_code'        => $datasetCode,
            'dataset_type'        => $datasetType,
            'filter_params'       => $filterParams,
            'row_count'           => $rowCount,
            'download_size_bytes' => $sizeBytes,
            'transaction_amount'  => $amount,
            'gravport_commission' => $commission,
            'provider_revenue'    => $providerShare,
            'user_agent'          => $params['user_agent'] ?? null,
        ]);
    }

    // ── Revenue Share ────────────────────────────────────────────────

    /**
     * Aggregate download_transactions for all active providers into revenue_shares
     * for the given year/month. Safe to re-run (uses UPSERT).
     * Returns list of generated/updated rows.
     */
    public function generateMonthlyRevenue(int $year, int $month): array
    {
        $periodStart = sprintf('%04d-%02d-01', $year, $month);
        $periodEnd   = date('Y-m-d', strtotime($periodStart . ' +1 month'));

        $providers = $this->providers->all();
        $results   = [];

        foreach ($providers as $provider) {
            $pid = (int) $provider['provider_id'];
            $agg = $this->transactions->aggregateForPeriod($pid, $periodStart, $periodEnd);

            $this->revenueShares->upsert($pid, $periodStart, $periodEnd, $agg);

            $results[] = [
                'provider_id'    => $pid,
                'provider_name'  => $provider['provider_name'],
                'total_downloads'=> (int) ($agg['total_downloads'] ?? 0),
                'gross_revenue'  => (float) ($agg['gross_revenue'] ?? 0),
                'provider_share' => (float) ($agg['provider_share'] ?? 0),
                'platform_share' => (float) ($agg['platform_share'] ?? 0),
            ];
        }

        return $results;
    }

    // ── Dashboard ────────────────────────────────────────────────────

    public function dashboardStats(): array
    {
        $txStats  = $this->transactions->dashboardStats();
        $tierDist = $this->subscriptions->tierDistribution();
        $breakdown = $this->transactions->datasetBreakdown();

        return [
            'total_downloads_this_month'    => (int)   ($txStats['total_this_month']            ?? 0),
            'total_downloads_all_time'      => (int)   ($txStats['total_all_time']              ?? 0),
            'provider_revenue_this_month'   => (float) ($txStats['provider_revenue_this_month'] ?? 0),
            'platform_revenue_this_month'   => (float) ($txStats['platform_revenue_this_month'] ?? 0),
            'tier_distribution'             => $tierDist,
            'dataset_breakdown'             => $breakdown,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Return the active tier name for a user ('free', 'enterprise', etc.).
     */
    public function userTier(int $userId): string
    {
        if ($userId <= 0) {
            return 'free';
        }
        $sub = $this->subscriptions->activeFor($userId);
        return strtolower($sub['tier_name'] ?? 'free');
    }

    public static function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
