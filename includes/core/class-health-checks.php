<?php
namespace KitgenixAffiliateLinkManager\Core;

defined( 'ABSPATH' ) || exit;

final class Health_Checks {

    public const CRON_HOOK       = 'kitgenix_affiliate_run_health_checks';
    public const SINGLE_CRON_HOOK = 'kitgenix_affiliate_run_health_checks_once';
    public const OPTION_SUMMARY  = 'kitgenix_affiliate_link_manager_health_summary';
    public const OPTION_CURSOR   = 'kitgenix_affiliate_link_manager_health_cursor';

    public const META_STATUS       = '_kitgenix_affiliate_health_status';
    public const META_MESSAGE      = '_kitgenix_affiliate_health_message';
    public const META_CHECKED_AT   = '_kitgenix_affiliate_health_checked_at';
    public const META_HTTP_CODE    = '_kitgenix_affiliate_health_http_code';
    public const META_FINAL_URL    = '_kitgenix_affiliate_health_final_url';
    public const META_REDIRECT_HOPS = '_kitgenix_affiliate_health_redirect_hops';
    public const META_CODE         = '_kitgenix_affiliate_health_code';

    private const BATCH_SIZE        = 10;
    private const MAX_REDIRECT_HOPS = 5;

    private const CRON_SCHEDULE_MAP = [
        'thirty_minutes' => 'kitgenix_affiliate_thirty_minutes',
        'hourly'         => 'hourly',
        'twicedaily'     => 'twicedaily',
        'daily'          => 'daily',
    ];

    public static function init(): void {
        \add_filter( 'cron_schedules', [ __CLASS__, 'register_cron_schedules' ] ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected -- interval is user-configurable via plugin settings.
        \add_action( 'init', [ __CLASS__, 'maybe_schedule_health_checks' ] );
        \add_action( self::CRON_HOOK, [ __CLASS__, 'run_scheduled_checks' ] );
        \add_action( self::SINGLE_CRON_HOOK, [ __CLASS__, 'run_scheduled_checks' ] );
    }

    /**
     * @param array<string,array<string,mixed>> $schedules
     * @return array<string,array<string,mixed>>
     */
    public static function register_cron_schedules( array $schedules ): array {
        if ( ! isset( $schedules['kitgenix_affiliate_thirty_minutes'] ) ) {
            $schedules['kitgenix_affiliate_thirty_minutes'] = [
                'interval' => 30 * MINUTE_IN_SECONDS,
                'display'  => \__( 'Every 30 minutes (Kitgenix Affiliate Link Manager)', 'kitgenix-affiliate-link-manager' ),
            ];
        }

        return $schedules;
    }

    private static function get_cron_schedule_slug(): string {
        $frequency = Settings::get_health_check_frequency();
        return self::CRON_SCHEDULE_MAP[ $frequency ] ?? 'hourly';
    }

    public static function maybe_schedule_health_checks(): void {
        $desired_schedule = self::get_cron_schedule_slug();
        $scheduled_event   = \wp_get_scheduled_event( self::CRON_HOOK );

        if ( $scheduled_event && isset( $scheduled_event->schedule ) && $scheduled_event->schedule !== $desired_schedule ) {
            // The configured frequency changed since this was last scheduled; re-schedule at the new interval.
            \wp_clear_scheduled_hook( self::CRON_HOOK );
            $scheduled_event = false;
        }

        if ( ! $scheduled_event ) {
            $first_run = (int) \current_time( 'timestamp', true ) + 5 * MINUTE_IN_SECONDS;
            \wp_schedule_event( $first_run, $desired_schedule, self::CRON_HOOK );
        }

        $summary = self::get_summary();
        if ( empty( $summary['checked_at'] ) && ! \wp_next_scheduled( self::SINGLE_CRON_HOOK ) ) {
            $first_run = (int) \current_time( 'timestamp', true ) + MINUTE_IN_SECONDS;
            \wp_schedule_single_event( $first_run, self::SINGLE_CRON_HOOK );
        }
    }

    public static function clear_scheduled_health_checks(): void {
        \wp_clear_scheduled_hook( self::CRON_HOOK );
        \wp_clear_scheduled_hook( self::SINGLE_CRON_HOOK );
    }

    public static function get_summary(): array {
        $summary = \get_option( self::OPTION_SUMMARY, [] );
        return \is_array( $summary ) ? $summary : [];
    }

    public static function get_health_status( int $post_id ): string {
        $status = \sanitize_key( (string) \get_post_meta( $post_id, self::META_STATUS, true ) );
        if ( ! \in_array( $status, [ 'unknown', 'healthy', 'warning', 'critical' ], true ) ) {
            $status = 'unknown';
        }

        return $status;
    }

    public static function get_health_message( int $post_id ): string {
        return \trim( (string) \get_post_meta( $post_id, self::META_MESSAGE, true ) );
    }

    public static function get_health_checked_at_timestamp( int $post_id ): int {
        return max( 0, (int) \get_post_meta( $post_id, self::META_CHECKED_AT, true ) );
    }

    public static function get_health_checked_at( int $post_id ): string {
        $timestamp = self::get_health_checked_at_timestamp( $post_id );
        return $timestamp > 0 ? \gmdate( 'c', $timestamp ) : '';
    }

    public static function get_health_http_code( int $post_id ): int {
        return max( 0, (int) \get_post_meta( $post_id, self::META_HTTP_CODE, true ) );
    }

    public static function get_health_final_url( int $post_id ): string {
        return \esc_url_raw( (string) \get_post_meta( $post_id, self::META_FINAL_URL, true ) );
    }

    public static function get_health_redirect_hops( int $post_id ): int {
        return max( 0, (int) \get_post_meta( $post_id, self::META_REDIRECT_HOPS, true ) );
    }

    public static function get_health_code( int $post_id ): string {
        return \sanitize_key( (string) \get_post_meta( $post_id, self::META_CODE, true ) );
    }

    public static function run_scheduled_checks(): void {
        $offset = max( 0, (int) \get_option( self::OPTION_CURSOR, 0 ) );
        $ids    = \get_posts(
            [
                'post_type'              => Affiliate_Link_Post_Type::POST_TYPE,
                'post_status'            => 'publish',
                'fields'                 => 'ids',
                'posts_per_page'         => self::BATCH_SIZE,
                'offset'                 => $offset,
                'orderby'                => 'ID',
                'order'                  => 'ASC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'suppress_filters'       => false,
            ]
        );

        if ( ! \is_array( $ids ) ) {
            $ids = [];
        }

        foreach ( $ids as $post_id ) {
            $post_id = (int) $post_id;
            if ( $post_id <= 0 ) {
                continue;
            }

            self::store_health_report( $post_id, self::check_link_destinations( $post_id ) );
        }

        if ( count( $ids ) < self::BATCH_SIZE ) {
            \update_option( self::OPTION_CURSOR, 0, false );
        } else {
            \update_option( self::OPTION_CURSOR, $offset + count( $ids ), false );
        }

        self::rebuild_summary();
    }

    /**
     * Run a health check for a single link right now, outside the hourly cron batch,
     * and refresh the site-wide summary. Used by the admin "Check now" row action.
     *
     * @return array<string,mixed>
     */
    public static function run_single_check( int $post_id ): array {
        $report = self::check_link_destinations( $post_id );
        self::store_health_report( $post_id, $report );
        self::rebuild_summary();

        return $report;
    }

    /**
     * Run a health check for every published link right now, bypassing the cursor-based
     * batching used by the hourly cron. Used by the admin "Run health check now" action.
     */
    public static function run_check_for_all(): int {
        $ids = \get_posts(
            [
                'post_type'              => Affiliate_Link_Post_Type::POST_TYPE,
                'post_status'            => 'publish',
                'fields'                 => 'ids',
                'numberposts'            => -1,
                'orderby'                => 'ID',
                'order'                  => 'ASC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'suppress_filters'       => false,
            ]
        );

        if ( ! \is_array( $ids ) ) {
            $ids = [];
        }

        $checked = 0;
        foreach ( $ids as $post_id ) {
            $post_id = (int) $post_id;
            if ( $post_id <= 0 ) {
                continue;
            }

            self::store_health_report( $post_id, self::check_link_destinations( $post_id ) );
            $checked++;
        }

        self::rebuild_summary();

        return $checked;
    }

    private static function rebuild_summary(): void {
        $ids = \get_posts(
            [
                'post_type'              => Affiliate_Link_Post_Type::POST_TYPE,
                'post_status'            => 'publish',
                'fields'                 => 'ids',
                'posts_per_page'         => -1,
                'orderby'                => 'ID',
                'order'                  => 'ASC',
                'no_found_rows'          => true,
                'update_post_meta_cache' => true,
                'update_post_term_cache' => false,
                'suppress_filters'       => false,
            ]
        );

        if ( ! \is_array( $ids ) ) {
            $ids = [];
        }

        $counts = [
            'unknown'  => 0,
            'healthy'  => 0,
            'warning'  => 0,
            'critical' => 0,
        ];
        $issues = [];

        foreach ( $ids as $post_id ) {
            $post_id = (int) $post_id;
            if ( $post_id <= 0 ) {
                continue;
            }

            $status = self::get_health_status( $post_id );
            if ( ! isset( $counts[ $status ] ) ) {
                $status = 'unknown';
            }

            $counts[ $status ]++;

            if ( \in_array( $status, [ 'warning', 'critical' ], true ) && count( $issues ) < 5 ) {
                $issues[] = [
                    'id'         => $post_id,
                    'title'      => \get_the_title( $post_id ),
                    'status'     => $status,
                    'message'    => self::get_health_message( $post_id ),
                    'checked_at' => self::get_health_checked_at( $post_id ),
                ];
            }
        }

        \update_option(
            self::OPTION_SUMMARY,
            [
                'checked_at' => \gmdate( 'c' ),
                'total'      => count( $ids ),
                'unknown'    => $counts['unknown'],
                'healthy'    => $counts['healthy'],
                'warning'    => $counts['warning'],
                'critical'   => $counts['critical'],
                'issues'     => $issues,
            ],
            false
        );
    }

    /**
     * @return array<string,mixed>
     */
    private static function check_link_destinations( int $post_id ): array {
        $destinations = self::get_destinations_for_link( $post_id );
        if ( empty( $destinations ) ) {
            return [
                'status'        => 'unknown',
                'message'       => \__( 'No destinations were available to check.', 'kitgenix-affiliate-link-manager' ),
                'checked_at'    => (int) \current_time( 'timestamp', true ),
                'http_code'     => 0,
                'final_url'     => '',
                'redirect_hops' => 0,
                'code'          => 'health_check_no_destinations',
            ];
        }

        $broken_count   = 0;
        $redirect_count = 0;
        $max_hops       = 0;
        $top_http_code  = 0;
        $top_final_url  = '';
        $first_issue    = '';
        $first_status   = 'healthy';
        $first_code     = '';

        foreach ( $destinations as $destination_url ) {
            $result = self::inspect_destination( $destination_url );

            $max_hops = max( $max_hops, (int) $result['redirect_hops'] );
            if ( $top_http_code === 0 && ! empty( $result['http_code'] ) ) {
                $top_http_code = (int) $result['http_code'];
            }
            if ( $top_final_url === '' && ! empty( $result['final_url'] ) ) {
                $top_final_url = (string) $result['final_url'];
            }

            if ( $result['status'] === 'critical' ) {
                $broken_count++;
            } elseif ( $result['status'] === 'warning' ) {
                $redirect_count++;
            }

            if ( $first_issue === '' && $result['status'] !== 'healthy' ) {
                $first_issue  = (string) $result['message'];
                $first_status = (string) $result['status'];
                $top_http_code = (int) $result['http_code'];
                $top_final_url = (string) $result['final_url'];
                $first_code    = (string) ( $result['code'] ?? '' );
            }
        }

        $status  = 'healthy';
        $message = '';

        if ( $broken_count > 0 ) {
            $status = 'critical';
            if ( $redirect_count > 0 ) {
                $message = sprintf(
                    /* translators: 1: broken destination count, 2: redirecting destination count. */
                    \__( '%1$d broken destinations and %2$d redirecting destinations detected.', 'kitgenix-affiliate-link-manager' ),
                    $broken_count,
                    $redirect_count
                );
            } else {
                $message = sprintf(
                    /* translators: %d: broken destination count. */
                    \_n( '%d broken destination detected.', '%d broken destinations detected.', $broken_count, 'kitgenix-affiliate-link-manager' ),
                    $broken_count
                );
            }
        } elseif ( $redirect_count > 0 ) {
            $status = 'warning';
            $message = sprintf(
                /* translators: %d: redirecting destination count. */
                \_n( '%d redirecting destination detected.', '%d redirecting destinations detected.', $redirect_count, 'kitgenix-affiliate-link-manager' ),
                $redirect_count
            );
        } else {
            $message = count( $destinations ) === 1
                ? \__( 'Destination responded successfully.', 'kitgenix-affiliate-link-manager' )
                : sprintf(
                    /* translators: %d: number of checked destinations. */
                    \__( 'All %d destinations responded successfully.', 'kitgenix-affiliate-link-manager' ),
                    count( $destinations )
                );
        }

        if ( $first_issue !== '' && $first_status !== 'healthy' ) {
            $message .= ' ' . $first_issue;
        }

        return [
            'status'        => $status,
            'message'       => $message,
            'checked_at'    => (int) \current_time( 'timestamp', true ),
            'http_code'     => $top_http_code,
            'final_url'     => $top_final_url,
            'redirect_hops' => $max_hops,
            'code'          => $first_code !== '' ? $first_code : 'health_link_ok',
        ];
    }

    /**
     * @return string[]
     */
    private static function get_destinations_for_link( int $post_id ): array {
        $destinations = [
            Affiliate_Link_Post_Type::get_destination_url( $post_id ),
            Affiliate_Link_Post_Type::get_fallback_url( $post_id ),
        ];

        foreach ( Affiliate_Link_Post_Type::get_rotation_destinations( $post_id ) as $destination ) {
            $destinations[] = $destination;
        }

        $clean = [];
        foreach ( $destinations as $destination ) {
            $destination = \esc_url_raw( \trim( (string) $destination ) );
            if ( $destination === '' ) {
                continue;
            }

            $clean[ $destination ] = $destination;
        }

        return array_values( $clean );
    }

    /**
     * @return array<string,mixed>
     */
    private static function inspect_destination( string $destination_url ): array {
        $current_url    = $destination_url;
        $redirect_hops  = 0;
        $http_code      = 0;
        $final_url      = $destination_url;
        $last_response  = null;

        while ( $redirect_hops <= self::MAX_REDIRECT_HOPS ) {
            $response = self::perform_request( $current_url );
            if ( \is_wp_error( $response ) ) {
                return [
                    'status'        => 'critical',
                    'message'       => sprintf(
                        /* translators: %s: network error message. */
                        \__( 'Health check failed: %s', 'kitgenix-affiliate-link-manager' ),
                        $response->get_error_message()
                    ),
                    'http_code'     => 0,
                    'final_url'     => $current_url,
                    'redirect_hops' => $redirect_hops,
                    'code'          => self::classify_transport_error( $response ),
                ];
            }

            $last_response = $response;
            $http_code     = (int) \wp_remote_retrieve_response_code( $response );
            $location      = \wp_remote_retrieve_header( $response, 'location' );

            if ( $http_code >= 300 && $http_code < 400 && is_string( $location ) && $location !== '' ) {
                $redirect_hops++;

                if ( $redirect_hops > self::MAX_REDIRECT_HOPS ) {
                    return [
                        'status'        => 'critical',
                        'message'       => \__( 'Destination entered a redirect loop or exceeded the redirect limit.', 'kitgenix-affiliate-link-manager' ),
                        'http_code'     => $http_code,
                        'final_url'     => $current_url,
                        'redirect_hops' => $redirect_hops,
                        'code'          => 'health_redirect_error',
                    ];
                }

                $resolved_url = self::resolve_redirect_location( $current_url, $location );
                if ( $resolved_url === '' || ! \wp_http_validate_url( $resolved_url ) ) {
                    return [
                        'status'        => 'critical',
                        'message'       => \__( 'Destination returned an invalid redirect target.', 'kitgenix-affiliate-link-manager' ),
                        'http_code'     => $http_code,
                        'final_url'     => $location,
                        'redirect_hops' => $redirect_hops,
                        'code'          => 'health_redirect_error',
                    ];
                }

                $current_url = $resolved_url;
                $final_url   = $resolved_url;
                continue;
            }

            $final_url = $current_url;
            break;
        }

        if ( $last_response === null ) {
            return [
                'status'        => 'unknown',
                'message'       => \__( 'No response was recorded during the health check.', 'kitgenix-affiliate-link-manager' ),
                'http_code'     => 0,
                'final_url'     => $final_url,
                'redirect_hops' => $redirect_hops,
                'code'          => 'health_check_unknown',
            ];
        }

        if ( $http_code >= 400 ) {
            return [
                'status'        => 'critical',
                'message'       => sprintf(
                    /* translators: %d: HTTP status code. */
                    \__( 'Destination returned HTTP %d.', 'kitgenix-affiliate-link-manager' ),
                    $http_code
                ),
                'http_code'     => $http_code,
                'final_url'     => $final_url,
                'redirect_hops' => $redirect_hops,
                'code'          => 'health_link_dead',
            ];
        }

        if ( $redirect_hops > 0 ) {
            return [
                'status'        => 'warning',
                'message'       => sprintf(
                    /* translators: %d: redirect hop count. */
                    \_n( 'Destination redirected %d time before loading.', 'Destination redirected %d times before loading.', $redirect_hops, 'kitgenix-affiliate-link-manager' ),
                    $redirect_hops
                ),
                'http_code'     => $http_code,
                'final_url'     => $final_url,
                'redirect_hops' => $redirect_hops,
                'code'          => 'health_link_redirect',
            ];
        }

        return [
            'status'        => 'healthy',
            'message'       => \__( 'Destination responded successfully.', 'kitgenix-affiliate-link-manager' ),
            'http_code'     => $http_code,
            'final_url'     => $final_url,
            'redirect_hops' => 0,
            'code'          => 'health_link_ok',
        ];
    }

    /**
     * @return mixed
     */
    private static function perform_request( string $url ) {
        $args = [
            'timeout'            => (int) \apply_filters( 'kitgenix_affiliate_health_check_timeout', Settings::get_health_check_timeout(), $url ),
            'redirection'        => 0,
            'limit_response_size'=> 1024,
            'reject_unsafe_urls' => true,
            'user-agent'         => 'Kitgenix Affiliate Link Manager/' . ( defined( 'KITGENIX_AFFILIATE_LINK_MANAGER_VERSION' ) ? (string) KITGENIX_AFFILIATE_LINK_MANAGER_VERSION : '2.0.0' ) . '; ' . \home_url( '/' ),
        ];

        $response = \wp_remote_head( $url, $args );
        $code     = \is_wp_error( $response ) ? 0 : (int) \wp_remote_retrieve_response_code( $response );

        if ( \is_wp_error( $response ) || \in_array( $code, [ 403, 405, 501 ], true ) ) {
            $response = \wp_remote_get( $url, $args );
        }

        return $response;
    }

    /**
     * Classify a transport-level WP_Error into a structured health code so genuinely dead
     * links can be told apart from transient network blips and site-level request blocks.
     */
    private static function classify_transport_error( \WP_Error $error ): string {
        $message = strtolower( $error->get_error_message() );

        // WP_Http itself returns this exact message when reject_unsafe_urls rejects the
        // target (e.g. it resolves to a private/loopback address) – not a dead destination.
        if ( false !== strpos( $message, 'a valid url was not provided' ) || false !== strpos( $message, 'not accessible' ) ) {
            return 'health_check_blocked';
        }

        if ( false !== strpos( $message, 'refused' ) ) {
            return 'health_link_dead';
        }

        if ( false !== strpos( $message, 'could not resolve host' )
            || false !== strpos( $message, 'timed out' )
            || false !== strpos( $message, 'timeout' )
            || false !== strpos( $message, 'resolve' )
        ) {
            return 'health_network_error';
        }

        return 'health_network_error';
    }

    private static function resolve_redirect_location( string $base_url, string $location ): string {
        $location = \trim( $location );
        if ( $location === '' ) {
            return '';
        }

        if ( \wp_http_validate_url( $location ) ) {
            return $location;
        }

        $base = \wp_parse_url( $base_url );
        if ( ! \is_array( $base ) || empty( $base['scheme'] ) || empty( $base['host'] ) ) {
            return '';
        }

        if ( strpos( $location, '//' ) === 0 ) {
            return $base['scheme'] . ':' . $location;
        }

        $port = isset( $base['port'] ) ? ':' . (int) $base['port'] : '';
        $root = $base['scheme'] . '://' . $base['host'] . $port;

        if ( strpos( $location, '/' ) === 0 ) {
            return $root . $location;
        }

        $path      = isset( $base['path'] ) ? (string) $base['path'] : '/';
        $directory = trailingslashit( preg_replace( '#/[^/]*$#', '/', $path ) ?: '/' );
        $combined  = $directory . $location;
        $segments  = [];

        foreach ( explode( '/', $combined ) as $segment ) {
            if ( $segment === '' || $segment === '.' ) {
                continue;
            }

            if ( $segment === '..' ) {
                array_pop( $segments );
                continue;
            }

            $segments[] = $segment;
        }

        return $root . '/' . implode( '/', $segments );
    }

    private static function store_health_report( int $post_id, array $report ): void {
        $status = isset( $report['status'] ) ? (string) $report['status'] : 'unknown';
        $code   = isset( $report['code'] ) ? \sanitize_key( (string) $report['code'] ) : '';

        \update_post_meta( $post_id, self::META_STATUS, $status );
        \update_post_meta( $post_id, self::META_MESSAGE, isset( $report['message'] ) ? (string) $report['message'] : '' );
        \update_post_meta( $post_id, self::META_CHECKED_AT, isset( $report['checked_at'] ) ? (int) $report['checked_at'] : 0 );
        \update_post_meta( $post_id, self::META_HTTP_CODE, isset( $report['http_code'] ) ? (int) $report['http_code'] : 0 );
        \update_post_meta( $post_id, self::META_FINAL_URL, isset( $report['final_url'] ) ? (string) $report['final_url'] : '' );
        \update_post_meta( $post_id, self::META_REDIRECT_HOPS, isset( $report['redirect_hops'] ) ? (int) $report['redirect_hops'] : 0 );
        \update_post_meta( $post_id, self::META_CODE, $code );

        // Only warning/critical/unknown results are logged – an hourly cron checking every
        // link would otherwise flood the capped 100-entry log with routine "healthy" noise.
        if ( 'healthy' === $status ) {
            return;
        }

        Event_Log::record(
            'health-check',
            'critical' === $status ? 'error' : $status,
            sprintf(
                /* translators: 1: link title, 2: health check message. */
                \__( '%1$s: %2$s', 'kitgenix-affiliate-link-manager' ),
                (string) \get_the_title( $post_id ),
                isset( $report['message'] ) ? (string) $report['message'] : ''
            ),
            $code
        );
    }
}