-- =============================================================================
-- Materialized view: mv_dataset_stats
-- Computes 2-sigma (mean ± 2×stddev) per dataset for color scale auto-update.
-- Run ONCE on the server.
-- After new data is imported, run:
--   REFRESH MATERIALIZED VIEW CONCURRENTLY geoportal.mv_dataset_stats;
-- =============================================================================

CREATE MATERIALIZED VIEW IF NOT EXISTS geoportal.mv_dataset_stats AS

-- Vector: faa_l1 — direct per-point statistics
SELECT
    'faa_l1'::text AS dataset_code,
    ROUND((AVG(anomaly_value) - 2.0 * STDDEV_POP(anomaly_value))::numeric, 3) AS min_2sigma,
    ROUND((AVG(anomaly_value) + 2.0 * STDDEV_POP(anomaly_value))::numeric, 3) AS max_2sigma
FROM geoportal.faa_l1_points

UNION ALL

-- Vector: cba_l1
SELECT
    'cba_l1',
    ROUND((AVG(anomaly_value) - 2.0 * STDDEV_POP(anomaly_value))::numeric, 3),
    ROUND((AVG(anomaly_value) + 2.0 * STDDEV_POP(anomaly_value))::numeric, 3)
FROM geoportal.cba_l1_points

UNION ALL

-- Raster: faa_l2 — combine per-tile ST_SummaryStats using E[X²]-E[X]² formula
SELECT
    'faa_l2',
    ROUND((combined_mean - 2.0 * SQRT(GREATEST(combined_var, 0)))::numeric, 3),
    ROUND((combined_mean + 2.0 * SQRT(GREATEST(combined_var, 0)))::numeric, 3)
FROM (
    SELECT
        SUM(n * mean)  / NULLIF(SUM(n), 0) AS combined_mean,
        SUM(n * (std * std + mean * mean)) / NULLIF(SUM(n), 0)
            - (SUM(n * mean) / NULLIF(SUM(n), 0)) ^ 2 AS combined_var
    FROM (
        SELECT
            (ST_SummaryStats(rast)).count  AS n,
            (ST_SummaryStats(rast)).mean   AS mean,
            (ST_SummaryStats(rast)).stddev AS std
        FROM geoportal.faa_l2_raster
        WHERE rast IS NOT NULL
    ) ts
    WHERE n > 0
) agg

UNION ALL

-- Raster: cba_l2
SELECT
    'cba_l2',
    ROUND((combined_mean - 2.0 * SQRT(GREATEST(combined_var, 0)))::numeric, 3),
    ROUND((combined_mean + 2.0 * SQRT(GREATEST(combined_var, 0)))::numeric, 3)
FROM (
    SELECT
        SUM(n * mean)  / NULLIF(SUM(n), 0) AS combined_mean,
        SUM(n * (std * std + mean * mean)) / NULLIF(SUM(n), 0)
            - (SUM(n * mean) / NULLIF(SUM(n), 0)) ^ 2 AS combined_var
    FROM (
        SELECT
            (ST_SummaryStats(rast)).count  AS n,
            (ST_SummaryStats(rast)).mean   AS mean,
            (ST_SummaryStats(rast)).stddev AS std
        FROM geoportal.cba_l2_raster
        WHERE rast IS NOT NULL
    ) ts
    WHERE n > 0
) agg;

CREATE UNIQUE INDEX IF NOT EXISTS idx_mv_dataset_stats_code
    ON geoportal.mv_dataset_stats (dataset_code);

-- Verify
SELECT dataset_code, min_2sigma, max_2sigma
FROM geoportal.mv_dataset_stats
ORDER BY dataset_code;
