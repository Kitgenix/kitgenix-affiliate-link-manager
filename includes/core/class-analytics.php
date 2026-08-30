<?php
namespace KitgenixAffiliateLinkManager\Core;

defined( 'ABSPATH' ) || exit;

final class Analytics {

    public const DB_VERSION        = '1.1.1';
    public const OPTION_DB_VERSION = 'kitgenix_affiliate_link_manager_analytics_db_version';

    private const TIMELINE_DAYS   = 14;
    private const BREAKDOWN_DAYS  = 30;
    private const SHORT_WINDOW_DAYS = 7;
    private const BREAKDOWN_LIMIT = 5;

    /**
     * Overview snapshot transient. Six aggregate queries run against the click_stats
     * table to build this; caching briefly keeps the admin Links tab and REST/CLI
     * summaries from re-running them on every single page load.
     */
    private const OVERVIEW_CACHE_KEY = 'kitgenix_affiliate_link_manager_overview_snapshot';
    private const OVERVIEW_CACHE_TTL = 300;

    /**
     * @var array<int,array<string,mixed>>
     */
    private static $link_snapshots = [];

    /**
     * @var array<string,array<string,mixed>>
     */
    private static $overview_snapshots = [];

    public static function init(): void {
        \add_action( 'init', [ __CLASS__, 'maybe_install_table' ], 1 );
    }

    public static function maybe_install_table(): void {
        $installed_version = (string) \get_option( self::OPTION_DB_VERSION, '' );
        if ( $installed_version === self::DB_VERSION ) {
            return;
        }

        self::install_table();
    }

    public static function install_table(): void {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return;
        }

        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            link_id bigint(20) unsigned NOT NULL,
            event_date date NOT NULL,
            source_key varchar(32) NOT NULL DEFAULT 'direct',
            source_label varchar(191) NOT NULL DEFAULT 'Direct',
            placement_key varchar(32) NOT NULL DEFAULT 'unassigned',
            placement_label varchar(191) NOT NULL DEFAULT 'Unassigned',
            referrer_host varchar(64) NOT NULL DEFAULT '',
            clicks bigint(20) unsigned NOT NULL DEFAULT 0,
            last_clicked_at datetime NULL DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY link_date_source_placement_referrer (link_id,event_date,source_key,placement_key,referrer_host),
            KEY link_id (link_id),
            KEY event_date (event_date),
            KEY source_key (source_key),
            KEY placement_key (placement_key),
            KEY referrer_host (referrer_host)
        ) {$charset_collate};";

        \dbDelta( $sql );
        \update_option( self::OPTION_DB_VERSION, self::DB_VERSION, false );
    }

    public static function get_table_name(): string {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return '';
        }

        $prefix = (string) $wpdb->prefix;
        $prefix = \preg_replace( '/[^A-Za-z0-9_]/', '', $prefix );

        return ( is_string( $prefix ) ? $prefix : '' ) . 'kitgenix_aff_click_stats';
    }

    public static function record_click( int $post_id ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        self::maybe_install_table();

        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return;
        }

        $table_name = self::get_table_name();
        if ( $table_name === '' ) {
            return;
        }

        $table_name = \esc_sql( $table_name );
        $context = self::get_request_context();
        $result  = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are stored in a plugin-owned table.
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is plugin-owned; sanitized with esc_sql() before interpolation.
                "INSERT INTO {$table_name} (link_id, event_date, source_key, source_label, placement_key, placement_label, referrer_host, clicks, last_clicked_at)
                VALUES (%d, %s, %s, %s, %s, %s, %s, 1, %s)
                ON DUPLICATE KEY UPDATE
                    clicks = clicks + 1,
                    source_label = VALUES(source_label),
                    placement_label = VALUES(placement_label),
                    last_clicked_at = VALUES(last_clicked_at)",
                $post_id,
                self::get_local_date_for_storage(),
                $context['source_key'],
                $context['source_label'],
                $context['placement_key'],
                $context['placement_label'],
                $context['referrer_host'],
                \current_time( 'mysql', true )
            )
        );

        if ( $result !== false ) {
            unset( self::$link_snapshots[ $post_id ] );
            self::$overview_snapshots = [];
            \delete_transient( self::OVERVIEW_CACHE_KEY );
        }
    }

    public static function reset_link_analytics( int $post_id ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return;
        }

        $table_name = self::get_table_name();
        if ( $table_name === '' ) {
            return;
        }

        $table_name = \esc_sql( $table_name );
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Resetting analytics is a direct mutation of the plugin-owned table.
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is plugin-owned; sanitized with esc_sql() before interpolation.
                "DELETE FROM {$table_name} WHERE link_id = %d",
                $post_id
            )
        );

        unset( self::$link_snapshots[ $post_id ] );
        self::$overview_snapshots = [];
        \delete_transient( self::OVERVIEW_CACHE_KEY );
    }

    public static function prime_link_snapshots( array $post_ids ): void {
        $post_ids = array_values( array_unique( array_filter( array_map( 'intval', $post_ids ) ) ) );
        if ( empty( $post_ids ) ) {
            return;
        }

        $missing_ids = [];
        foreach ( $post_ids as $post_id ) {
            if ( ! array_key_exists( $post_id, self::$link_snapshots ) ) {
                $missing_ids[] = $post_id;
            }
        }

        if ( empty( $missing_ids ) ) {
            return;
        }

        self::maybe_install_table();

        foreach ( $missing_ids as $post_id ) {
            self::$link_snapshots[ $post_id ] = self::get_empty_snapshot();
        }

        self::load_link_period_totals( $missing_ids, self::SHORT_WINDOW_DAYS, 'clicks_last_7_days' );
        self::load_link_period_totals( $missing_ids, self::BREAKDOWN_DAYS, 'clicks_last_30_days' );
        self::load_link_timeline( $missing_ids, self::TIMELINE_DAYS );
        self::load_link_breakdown( $missing_ids, self::BREAKDOWN_DAYS, 'source', 'top_sources_30_days' );
        self::load_link_breakdown( $missing_ids, self::BREAKDOWN_DAYS, 'placement', 'top_placements_30_days' );
        self::load_link_breakdown( $missing_ids, self::BREAKDOWN_DAYS, 'referrer', 'top_referrers_30_days' );
    }

    public static function get_link_snapshot( int $post_id ): array {
        if ( $post_id <= 0 ) {
            return self::get_empty_snapshot();
        }

        self::prime_link_snapshots( [ $post_id ] );

        return self::$link_snapshots[ $post_id ] ?? self::get_empty_snapshot();
    }

    public static function get_overview_snapshot(): array {
        $cache_key = 'all';
        if ( isset( self::$overview_snapshots[ $cache_key ] ) ) {
            return self::$overview_snapshots[ $cache_key ];
        }

        // Six aggregate queries build this snapshot; a short transient keeps repeated
        // admin page loads (e.g. reopening the Links tab) from re-running all of them
        // every time. record_click()/reset_link_analytics() clear it immediately on write.
        $cached = \get_transient( self::OVERVIEW_CACHE_KEY );
        if ( is_array( $cached ) ) {
            self::$overview_snapshots[ $cache_key ] = $cached;
            return $cached;
        }

        self::maybe_install_table();

        $snapshot = self::get_empty_snapshot();
        $snapshot['clicks_last_7_days']      = self::query_total_clicks_for_period( self::SHORT_WINDOW_DAYS );
        $snapshot['clicks_last_30_days']     = self::query_total_clicks_for_period( self::BREAKDOWN_DAYS );
        $snapshot['timeline_14_days']        = self::query_timeline_for_period( self::TIMELINE_DAYS );
        $snapshot['top_sources_30_days']     = self::query_breakdown_for_period( self::BREAKDOWN_DAYS, 'source' );
        $snapshot['top_placements_30_days']  = self::query_breakdown_for_period( self::BREAKDOWN_DAYS, 'placement' );
        $snapshot['top_referrers_30_days']   = self::query_breakdown_for_period( self::BREAKDOWN_DAYS, 'referrer' );

        \set_transient( self::OVERVIEW_CACHE_KEY, $snapshot, self::OVERVIEW_CACHE_TTL );
        self::$overview_snapshots[ $cache_key ] = $snapshot;

        return $snapshot;
    }

    public static function format_timeline_for_export( array $timeline ): string {
        $parts = [];

        foreach ( $timeline as $point ) {
            if ( ! is_array( $point ) ) {
                continue;
            }

            $date   = isset( $point['date'] ) ? (string) $point['date'] : '';
            $clicks = isset( $point['clicks'] ) ? (int) $point['clicks'] : 0;
            if ( $date === '' ) {
                continue;
            }

            $parts[] = $date . ':' . $clicks;
        }

        return implode( ' | ', $parts );
    }

    public static function format_breakdown_for_export( array $items ): string {
        $parts = [];

        foreach ( $items as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $label  = isset( $item['label'] ) ? (string) $item['label'] : '';
            $clicks = isset( $item['clicks'] ) ? (int) $item['clicks'] : 0;
            if ( $label === '' ) {
                continue;
            }

            $parts[] = $label . ' (' . $clicks . ')';
        }

        return implode( ' | ', $parts );
    }

    private static function get_request_context(): array {
        $query      = [];
        $query_data = \filter_input_array( INPUT_GET, FILTER_UNSAFE_RAW );
        if ( is_array( $query_data ) ) {
            $query = \wp_unslash( $query_data );
        }

        $source_value    = self::get_first_non_empty_value( $query, [ 'utm_source', 'source', 'src', 'ref', 'referrer' ] );
        $placement_value = self::get_first_non_empty_value( $query, [ 'placement', 'utm_content', 'utm_campaign', 'utm_medium', 'slot', 'variant' ] );
        $raw_referrer    = function_exists( 'wp_get_raw_referer' ) ? (string) \wp_get_raw_referer() : '';

        if ( $raw_referrer === '' ) {
            $server_referrer = \filter_input( INPUT_SERVER, 'HTTP_REFERER', FILTER_UNSAFE_RAW );
            if ( is_string( $server_referrer ) && $server_referrer !== '' ) {
                $raw_referrer = (string) \wp_unslash( $server_referrer );
            }
        }

        $referrer_host = self::normalize_referrer_host( $raw_referrer );
        $source        = self::normalize_dimension_value(
            $source_value,
            $referrer_host !== '' ? $referrer_host : 'direct',
            $referrer_host !== '' ? $referrer_host : __( 'Direct', 'kitgenix-affiliate-link-manager' )
        );
        $placement     = self::normalize_dimension_value(
            $placement_value,
            'unassigned',
            __( 'Unassigned', 'kitgenix-affiliate-link-manager' )
        );

        return [
            'source_key'      => $source['key'],
            'source_label'    => $source['label'],
            'placement_key'   => $placement['key'],
            'placement_label' => $placement['label'],
            'referrer_host'   => $referrer_host,
        ];
    }

    private static function get_first_non_empty_value( array $source, array $keys ): string {
        foreach ( $keys as $key ) {
            if ( ! array_key_exists( $key, $source ) ) {
                continue;
            }

            $value = \trim( \sanitize_text_field( (string) $source[ $key ] ) );
            if ( $value !== '' ) {
                return $value;
            }
        }

        return '';
    }

    private static function normalize_dimension_value( string $value, string $fallback_key, string $fallback_label ): array {
        $label = \trim( \sanitize_text_field( $value ) );
        if ( $label === '' ) {
            $label = $fallback_label;
        }

        $key = \sanitize_title( $label );
        if ( $key === '' ) {
            $key = \sanitize_key( $fallback_key );
        }
        if ( $key === '' ) {
            $key = 'unknown';
        }

        return [
            'key'   => substr( $key, 0, 32 ),
            'label' => substr( $label, 0, 191 ),
        ];
    }

    private static function normalize_referrer_host( string $raw_referrer ): string {
        $host = (string) \wp_parse_url( $raw_referrer, PHP_URL_HOST );
        $host = strtolower( \trim( $host ) );
        if ( strpos( $host, 'www.' ) === 0 ) {
            $host = substr( $host, 4 );
        }

        return substr( \sanitize_text_field( $host ), 0, 64 );
    }

    private static function get_local_date_for_storage( ?int $timestamp = null ): string {
        $timezone = function_exists( 'wp_timezone' ) ? \wp_timezone() : new \DateTimeZone( 'UTC' );
        $time     = $timestamp ?? (int) \current_time( 'timestamp' );
        return \wp_date( 'Y-m-d', $time, $timezone );
    }

    private static function get_start_date_for_period( int $days ): string {
        $days = max( 1, $days );
        $time = (int) \current_time( 'timestamp' ) - ( ( $days - 1 ) * DAY_IN_SECONDS );
        return self::get_local_date_for_storage( $time );
    }

    private static function get_empty_snapshot(): array {
        return [
            'clicks_last_7_days'     => 0,
            'clicks_last_30_days'    => 0,
            'timeline_14_days'       => self::get_empty_timeline( self::TIMELINE_DAYS ),
            'top_sources_30_days'    => [],
            'top_placements_30_days' => [],
            'top_referrers_30_days'  => [],
        ];
    }

    /**
     * @return array<int,array{date:string,clicks:int}>
     */
    private static function get_empty_timeline( int $days ): array {
        $days     = max( 1, $days );
        $timezone = function_exists( 'wp_timezone' ) ? \wp_timezone() : new \DateTimeZone( 'UTC' );
        $start    = new \DateTimeImmutable( self::get_start_date_for_period( $days ) . ' 00:00:00', $timezone );
        $timeline = [];

        for ( $offset = 0; $offset < $days; $offset++ ) {
            $date = $start->modify( '+' . $offset . ' day' );
            $timeline[] = [
                'date'   => $date->format( 'Y-m-d' ),
                'clicks' => 0,
            ];
        }

        return $timeline;
    }

    private static function load_link_period_totals( array $post_ids, int $days, string $target_key ): void {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return;
        }

        $table_name = self::get_table_name();
        if ( $table_name === '' || empty( $post_ids ) ) {
            return;
        }

        $table_name      = \esc_sql( $table_name );
        $id_placeholders = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );
        $params          = array_merge( [ self::get_start_date_for_period( $days ) ], $post_ids );
        $rows            = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are read directly from the plugin-owned table.
            $wpdb->prepare(
                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- $table_name is sanitized with esc_sql(); $id_placeholders contains only %d literals generated by array_fill.
                "SELECT link_id, SUM(clicks) AS clicks
                FROM {$table_name}
                WHERE event_date >= %s
                    AND link_id IN ({$id_placeholders})
                GROUP BY link_id",
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
                $params
            ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return;
        }

        foreach ( $rows as $row ) {
            $post_id = isset( $row['link_id'] ) ? (int) $row['link_id'] : 0;
            if ( $post_id <= 0 || ! isset( self::$link_snapshots[ $post_id ] ) ) {
                continue;
            }

            self::$link_snapshots[ $post_id ][ $target_key ] = isset( $row['clicks'] ) ? (int) $row['clicks'] : 0;
        }
    }

    private static function load_link_timeline( array $post_ids, int $days ): void {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return;
        }

        $table_name = self::get_table_name();
        if ( $table_name === '' || empty( $post_ids ) ) {
            return;
        }

        $table_name      = \esc_sql( $table_name );
        $id_placeholders = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );
        $params          = array_merge( [ self::get_start_date_for_period( $days ) ], $post_ids );
        $rows            = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are read directly from the plugin-owned table.
            $wpdb->prepare(
                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- $table_name is sanitized with esc_sql(); $id_placeholders contains only %d literals generated by array_fill.
                "SELECT link_id, event_date, SUM(clicks) AS clicks
                FROM {$table_name}
                WHERE event_date >= %s
                    AND link_id IN ({$id_placeholders})
                GROUP BY link_id, event_date
                ORDER BY event_date ASC",
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
                $params
            ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return;
        }

        $timelines = [];
        foreach ( $post_ids as $post_id ) {
            $timelines[ $post_id ] = self::get_empty_timeline( $days );
        }

        foreach ( $rows as $row ) {
            $post_id    = isset( $row['link_id'] ) ? (int) $row['link_id'] : 0;
            $event_date = isset( $row['event_date'] ) ? (string) $row['event_date'] : '';
            $clicks     = isset( $row['clicks'] ) ? (int) $row['clicks'] : 0;
            if ( $post_id <= 0 || ! isset( $timelines[ $post_id ] ) || $event_date === '' ) {
                continue;
            }

            foreach ( $timelines[ $post_id ] as $index => $point ) {
                if ( $point['date'] === $event_date ) {
                    $timelines[ $post_id ][ $index ]['clicks'] = $clicks;
                    break;
                }
            }
        }

        foreach ( $timelines as $post_id => $timeline ) {
            if ( isset( self::$link_snapshots[ $post_id ] ) ) {
                self::$link_snapshots[ $post_id ]['timeline_14_days'] = $timeline;
            }
        }
    }

    private static function load_link_breakdown( array $post_ids, int $days, string $dimension, string $target_key ): void {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return;
        }

        $table_name = self::get_table_name();
        if ( $table_name === '' || empty( $post_ids ) ) {
            return;
        }

        $table_name      = \esc_sql( $table_name );
        $id_placeholders = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );
        $params          = array_merge( [ self::get_start_date_for_period( $days ) ], $post_ids );
        if ( $dimension === 'source' ) {
            $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are read directly from the plugin-owned table.
                $wpdb->prepare(
                    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- $table_name is sanitized with esc_sql(); $id_placeholders contains only %d literals generated by array_fill.
                    "SELECT link_id, source_key AS item_key, source_label AS item_label, SUM(clicks) AS clicks
                    FROM {$table_name}
                    WHERE event_date >= %s
                        AND link_id IN ({$id_placeholders})
                    GROUP BY link_id, source_key, source_label
                    ORDER BY link_id ASC, clicks DESC, item_label ASC",
                    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
                    $params
                ),
                ARRAY_A
            );
        } elseif ( $dimension === 'placement' ) {
            $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are read directly from the plugin-owned table.
                $wpdb->prepare(
                    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- $table_name is sanitized with esc_sql(); $id_placeholders contains only %d literals generated by array_fill.
                    "SELECT link_id, placement_key AS item_key, placement_label AS item_label, SUM(clicks) AS clicks
                    FROM {$table_name}
                    WHERE event_date >= %s
                        AND link_id IN ({$id_placeholders})
                    GROUP BY link_id, placement_key, placement_label
                    ORDER BY link_id ASC, clicks DESC, item_label ASC",
                    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
                    $params
                ),
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are read directly from the plugin-owned table.
                $wpdb->prepare(
                    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- $table_name is sanitized with esc_sql(); $id_placeholders contains only %d literals generated by array_fill.
                    "SELECT link_id,
                        CASE WHEN referrer_host = '' THEN 'direct' ELSE referrer_host END AS item_key,
                        CASE WHEN referrer_host = '' THEN 'direct' ELSE referrer_host END AS item_label,
                        SUM(clicks) AS clicks
                    FROM {$table_name}
                    WHERE event_date >= %s
                        AND link_id IN ({$id_placeholders})
                    GROUP BY link_id,
                        CASE WHEN referrer_host = '' THEN 'direct' ELSE referrer_host END,
                        CASE WHEN referrer_host = '' THEN 'direct' ELSE referrer_host END
                    ORDER BY link_id ASC, clicks DESC, item_label ASC",
                    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
                    $params
                ),
                ARRAY_A
            );
        }

        if ( ! is_array( $rows ) ) {
            return;
        }

        $grouped = [];
        foreach ( $rows as $row ) {
            $post_id = isset( $row['link_id'] ) ? (int) $row['link_id'] : 0;
            if ( $post_id <= 0 ) {
                continue;
            }

            if ( ! isset( $grouped[ $post_id ] ) ) {
                $grouped[ $post_id ] = [];
            }

            if ( count( $grouped[ $post_id ] ) >= self::BREAKDOWN_LIMIT ) {
                continue;
            }

            $grouped[ $post_id ][] = [
                'key'    => isset( $row['item_key'] ) ? (string) $row['item_key'] : '',
                'label'  => isset( $row['item_label'] ) ? (string) $row['item_label'] : '',
                'clicks' => isset( $row['clicks'] ) ? (int) $row['clicks'] : 0,
            ];
        }

        foreach ( $grouped as $post_id => $items ) {
            if ( isset( self::$link_snapshots[ $post_id ] ) ) {
                self::$link_snapshots[ $post_id ][ $target_key ] = $items;
            }
        }
    }

    private static function query_total_clicks_for_period( int $days ): int {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return 0;
        }

        $table_name = self::get_table_name();
        if ( $table_name === '' ) {
            return 0;
        }

        $table_name = \esc_sql( $table_name );
        $total      = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are read directly from the plugin-owned table.
            $wpdb->prepare(
                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is plugin-owned; sanitized with esc_sql() before interpolation.
                "SELECT COALESCE(SUM(stats.clicks), 0)
                FROM {$table_name} AS stats
                INNER JOIN {$wpdb->posts} AS posts ON posts.ID = stats.link_id
                WHERE stats.event_date >= %s
                    AND posts.post_type = %s
                    AND posts.post_status = 'publish'",
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                self::get_start_date_for_period( $days ),
                Affiliate_Link_Post_Type::POST_TYPE
            )
        );

        return is_numeric( $total ) ? (int) $total : 0;
    }

    /**
     * @return array<int,array{date:string,clicks:int}>
     */
    private static function query_timeline_for_period( int $days ): array {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return self::get_empty_timeline( $days );
        }

        $table_name = self::get_table_name();
        if ( $table_name === '' ) {
            return self::get_empty_timeline( $days );
        }

        $table_name = \esc_sql( $table_name );
        $timeline   = self::get_empty_timeline( $days );
        $rows       = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are read directly from the plugin-owned table.
            $wpdb->prepare(
                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is plugin-owned; sanitized with esc_sql() before interpolation.
                "SELECT stats.event_date, SUM(stats.clicks) AS clicks
                FROM {$table_name} AS stats
                INNER JOIN {$wpdb->posts} AS posts ON posts.ID = stats.link_id
                WHERE stats.event_date >= %s
                    AND posts.post_type = %s
                    AND posts.post_status = 'publish'
                GROUP BY stats.event_date
                ORDER BY stats.event_date ASC",
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                self::get_start_date_for_period( $days ),
                Affiliate_Link_Post_Type::POST_TYPE
            ),
            ARRAY_A
        );

        if ( ! is_array( $rows ) ) {
            return $timeline;
        }

        foreach ( $rows as $row ) {
            $event_date = isset( $row['event_date'] ) ? (string) $row['event_date'] : '';
            $clicks     = isset( $row['clicks'] ) ? (int) $row['clicks'] : 0;
            if ( $event_date === '' ) {
                continue;
            }

            foreach ( $timeline as $index => $point ) {
                if ( $point['date'] === $event_date ) {
                    $timeline[ $index ]['clicks'] = $clicks;
                    break;
                }
            }
        }

        return $timeline;
    }

    /**
     * @return array<int,array{key:string,label:string,clicks:int}>
     */
    private static function query_breakdown_for_period( int $days, string $dimension ): array {
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
            return [];
        }

        $table_name = self::get_table_name();
        if ( $table_name === '' ) {
            return [];
        }

        $table_name = \esc_sql( $table_name );
        $limit      = self::BREAKDOWN_LIMIT;
        if ( $dimension === 'source' ) {
            $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are read directly from the plugin-owned table.
                $wpdb->prepare(
                    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is plugin-owned; sanitized with esc_sql() before interpolation.
                    "SELECT stats.source_key AS item_key, stats.source_label AS item_label, SUM(stats.clicks) AS clicks
                    FROM {$table_name} AS stats
                    INNER JOIN {$wpdb->posts} AS posts ON posts.ID = stats.link_id
                    WHERE stats.event_date >= %s
                        AND posts.post_type = %s
                        AND posts.post_status = 'publish'
                    GROUP BY stats.source_key, stats.source_label
                    ORDER BY clicks DESC, item_label ASC
                    LIMIT %d",
                    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    self::get_start_date_for_period( $days ),
                    Affiliate_Link_Post_Type::POST_TYPE,
                    $limit
                ),
                ARRAY_A
            );
        } elseif ( $dimension === 'placement' ) {
            $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are read directly from the plugin-owned table.
                $wpdb->prepare(
                    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is plugin-owned; sanitized with esc_sql() before interpolation.
                    "SELECT stats.placement_key AS item_key, stats.placement_label AS item_label, SUM(stats.clicks) AS clicks
                    FROM {$table_name} AS stats
                    INNER JOIN {$wpdb->posts} AS posts ON posts.ID = stats.link_id
                    WHERE stats.event_date >= %s
                        AND posts.post_type = %s
                        AND posts.post_status = 'publish'
                    GROUP BY stats.placement_key, stats.placement_label
                    ORDER BY clicks DESC, item_label ASC
                    LIMIT %d",
                    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    self::get_start_date_for_period( $days ),
                    Affiliate_Link_Post_Type::POST_TYPE,
                    $limit
                ),
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Aggregated analytics are read directly from the plugin-owned table.
                $wpdb->prepare(
                    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is plugin-owned; sanitized with esc_sql() before interpolation.
                    "SELECT
                        CASE WHEN stats.referrer_host = '' THEN 'direct' ELSE stats.referrer_host END AS item_key,
                        CASE WHEN stats.referrer_host = '' THEN 'direct' ELSE stats.referrer_host END AS item_label,
                        SUM(stats.clicks) AS clicks
                    FROM {$table_name} AS stats
                    INNER JOIN {$wpdb->posts} AS posts ON posts.ID = stats.link_id
                    WHERE stats.event_date >= %s
                        AND posts.post_type = %s
                        AND posts.post_status = 'publish'
                    GROUP BY
                        CASE WHEN stats.referrer_host = '' THEN 'direct' ELSE stats.referrer_host END,
                        CASE WHEN stats.referrer_host = '' THEN 'direct' ELSE stats.referrer_host END
                    ORDER BY clicks DESC, item_label ASC
                    LIMIT %d",
                    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    self::get_start_date_for_period( $days ),
                    Affiliate_Link_Post_Type::POST_TYPE,
                    $limit
                ),
                ARRAY_A
            );
        }

        if ( ! is_array( $rows ) ) {
            return [];
        }

        $items = [];
        foreach ( $rows as $row ) {
            $items[] = [
                'key'    => isset( $row['item_key'] ) ? (string) $row['item_key'] : '',
                'label'  => isset( $row['item_label'] ) ? (string) $row['item_label'] : '',
                'clicks' => isset( $row['clicks'] ) ? (int) $row['clicks'] : 0,
            ];
        }

        return $items;
    }
}