<?php
namespace KitgenixAffiliateLinkManager\Admin;

defined( 'ABSPATH' ) || exit;

use KitgenixAffiliateLinkManager\Core\Affiliate_Link_Post_Type;
use KitgenixAffiliateLinkManager\Core\Analytics;
use KitgenixAffiliateLinkManager\Core\Event_Log;
use KitgenixAffiliateLinkManager\Core\Health_Checks;
use KitgenixAffiliateLinkManager\Core\Settings;

class Settings_UI {

    /**
     * Hook suffix returned by add_submenu_page().
     *
     * @var string|null
     */
    private static $page_hook = null;

    public static function init(): void {
        \add_action( 'admin_menu', [ __CLASS__, 'register_menu' ], 20 );
        \add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

        \add_action( 'admin_post_kitgenix_affiliate_link_save', [ __CLASS__, 'handle_link_save' ] );
        \add_action( 'admin_post_kitgenix_affiliate_link_delete', [ __CLASS__, 'handle_link_delete' ] );
        \add_action( 'admin_post_kitgenix_affiliate_link_duplicate', [ __CLASS__, 'handle_link_duplicate' ] );
        \add_action( 'admin_post_kitgenix_affiliate_link_reset_clicks', [ __CLASS__, 'handle_link_reset_clicks' ] );
        \add_action( 'admin_post_kitgenix_affiliate_link_bulk', [ __CLASS__, 'handle_link_bulk' ] );
        \add_action( 'admin_post_kitgenix_affiliate_link_manager_clear_event_log', [ __CLASS__, 'handle_clear_event_log' ] );
        \add_action( 'update_option_' . Settings::OPTION_NAME, [ __CLASS__, 'on_settings_saved' ], 10, 3 );
    }

    private static function get_manage_capability(): string {
        $capability = class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options';
        if ( ! \current_user_can( $capability ) && \current_user_can( 'manage_options' ) ) {
            $capability = 'manage_options';
        }

        /**
         * Filter the capability required to manage affiliate links.
         *
         * @param string $capability Capability name.
         */
        $capability = (string) \apply_filters( 'kitgenix_affiliate_manage_capability', $capability );
        $capability = $capability !== '' ? $capability : 'manage_options';

        return $capability;
    }

    private static function user_can_manage_links(): bool {
        return \current_user_can( self::get_manage_capability() );
    }

    private static function get_links_list_state( array $source, string $prefix = '' ): array {
        $search_key  = $prefix . 's';
        $group_key   = $prefix . 'group';
        $tag_key     = $prefix . 'tag';
        $paged_key   = $prefix . 'paged';
        $orderby_key = $prefix . 'orderby';
        $order_key   = $prefix . 'order';

        $search = isset( $source[ $search_key ] ) ? (string) \sanitize_text_field( \wp_unslash( (string) $source[ $search_key ] ) ) : '';
        $group  = isset( $source[ $group_key ] ) ? (string) \sanitize_key( \wp_unslash( (string) $source[ $group_key ] ) ) : '';
        $tag    = isset( $source[ $tag_key ] ) ? (string) \sanitize_key( \wp_unslash( (string) $source[ $tag_key ] ) ) : '';
        $paged  = isset( $source[ $paged_key ] ) ? (int) $source[ $paged_key ] : 1;
        if ( $paged < 1 ) {
            $paged = 1;
        }

        $orderby = isset( $source[ $orderby_key ] ) ? \sanitize_key( (string) \wp_unslash( (string) $source[ $orderby_key ] ) ) : 'date';
        $order   = isset( $source[ $order_key ] ) ? \strtoupper( \sanitize_key( (string) \wp_unslash( (string) $source[ $order_key ] ) ) ) : 'DESC';
        if ( ! \in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
            $order = 'DESC';
        }

        $allowed_orderby = [ 'date', 'title', 'slug', 'clicks' ];
        if ( ! \in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'date';
        }

        return [
            's'              => \trim( $search ),
            'campaign_group' => $group,
            'campaign_tag'   => $tag,
            'paged'          => $paged,
            'orderby'        => $orderby,
            'order'          => $order,
        ];
    }

    private static function get_links_tab_url( array $args = [] ): string {
        return \add_query_arg(
            array_merge(
                [
                    'page' => 'kitgenix-affiliate-link-manager',
                    'tab'  => 'links',
                ],
                $args
            ),
            \admin_url( 'admin.php' )
        ) . '#kitgenix-tab-links';
    }

    private static function format_schedule_timestamp( int $timestamp ): string {
        if ( $timestamp <= 0 ) {
            return '';
        }

        $timezone = function_exists( 'wp_timezone' ) ? \wp_timezone() : new \DateTimeZone( 'UTC' );
        return \wp_date( 'j M Y H:i', $timestamp, $timezone );
    }

    private static function get_destination_rules_summary( int $post_id ): string {
        $summary_bits = [];
        $starts_at    = Affiliate_Link_Post_Type::get_starts_at_timestamp( $post_id );
        $expires_at   = Affiliate_Link_Post_Type::get_expires_at_timestamp( $post_id );
        $fallback_url = Affiliate_Link_Post_Type::get_fallback_url( $post_id );
        $rotation_mode = Affiliate_Link_Post_Type::get_rotation_mode( $post_id );
        $rotation_pool = array_values(
            array_unique(
                array_filter(
                    array_merge(
                        [ Affiliate_Link_Post_Type::get_destination_url( $post_id ) ],
                        Affiliate_Link_Post_Type::get_rotation_destinations( $post_id )
                    )
                )
            )
        );

        if ( $starts_at > 0 ) {
            $summary_bits[] = sprintf(
                /* translators: %s: local schedule start timestamp. */
                __( 'Starts %s', 'kitgenix-affiliate-link-manager' ),
                self::format_schedule_timestamp( $starts_at )
            );
        }

        if ( $expires_at > 0 ) {
            $summary_bits[] = sprintf(
                /* translators: %s: local schedule expiry timestamp. */
                __( 'Expires %s', 'kitgenix-affiliate-link-manager' ),
                self::format_schedule_timestamp( $expires_at )
            );
        }

        if ( $fallback_url !== '' ) {
            $summary_bits[] = __( 'Fallback URL enabled', 'kitgenix-affiliate-link-manager' );
        }

        if ( $rotation_mode !== 'none' && count( $rotation_pool ) > 1 ) {
            $summary_bits[] = sprintf(
                /* translators: 1: rotation mode, 2: number of destinations. */
                __( '%1$s rotation across %2$d destinations', 'kitgenix-affiliate-link-manager' ),
                $rotation_mode === 'random' ? __( 'Random', 'kitgenix-affiliate-link-manager' ) : __( 'Sequential', 'kitgenix-affiliate-link-manager' ),
                count( $rotation_pool )
            );
        }

        return implode( ' | ', $summary_bits );
    }

    private static function format_analytics_date_label( string $date ): string {
        if ( $date === '' ) {
            return '';
        }

        try {
            $timezone = function_exists( 'wp_timezone' ) ? \wp_timezone() : new \DateTimeZone( 'UTC' );
            $value    = new \DateTimeImmutable( $date . ' 00:00:00', $timezone );
            return $value->format( 'j M' );
        } catch ( \Exception $exception ) {
            return $date;
        }
    }

    private static function format_analytics_timeline_summary( array $timeline ): string {
        $parts = [];

        foreach ( $timeline as $point ) {
            if ( ! is_array( $point ) ) {
                continue;
            }

            $date   = isset( $point['date'] ) ? (string) $point['date'] : '';
            $clicks = isset( $point['clicks'] ) ? (int) $point['clicks'] : 0;
            if ( $date === '' || $clicks < 1 ) {
                continue;
            }

            $parts[] = self::format_analytics_date_label( $date ) . ' ' . \number_format_i18n( $clicks );
        }

        if ( empty( $parts ) ) {
            return __( 'No clicks recorded in the last 14 days.', 'kitgenix-affiliate-link-manager' );
        }

        return implode( ' | ', array_slice( $parts, -7 ) );
    }

    private static function format_analytics_breakdown_summary( array $items, string $empty_message ): string {
        $parts = [];

        foreach ( $items as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $label  = isset( $item['label'] ) ? (string) $item['label'] : '';
            $clicks = isset( $item['clicks'] ) ? (int) $item['clicks'] : 0;
            if ( $label === '' || $clicks < 1 ) {
                continue;
            }

            $parts[] = $label . ' (' . \number_format_i18n( $clicks ) . ')';
        }

        return ! empty( $parts ) ? implode( ' | ', $parts ) : $empty_message;
    }

    private static function render_analytics_overview_panel(): void {
        $snapshot = Analytics::get_overview_snapshot();

        echo '<div class="kitgenix-card-header kitgenix-card-header--links">'
            . '<div class="kitgenix-card-header__title">'
            . '<h3 class="kitgenix-card-title">' . \esc_html__( 'Analytics Snapshot', 'kitgenix-affiliate-link-manager' ) . '</h3>'
            . '<p class="kitgenix-card-subtitle">' . \esc_html__( 'Daily click trends, top sources, and top referrers across all published affiliate links.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '</div>';

        echo '<div class="kitgenix-muted">'
            . \esc_html(
                sprintf(
                    /* translators: 1: clicks in the last 7 days, 2: clicks in the last 30 days. */
                    __( 'Last 7 days: %1$s clicks | Last 30 days: %2$s clicks', 'kitgenix-affiliate-link-manager' ),
                    \number_format_i18n( (int) ( $snapshot['clicks_last_7_days'] ?? 0 ) ),
                    \number_format_i18n( (int) ( $snapshot['clicks_last_30_days'] ?? 0 ) )
                )
            )
            . '</div>';

        echo '<div class="kitgenix-muted">'
            . '<strong>' . \esc_html__( 'Timeline:', 'kitgenix-affiliate-link-manager' ) . '</strong> '
            . \esc_html( self::format_analytics_timeline_summary( isset( $snapshot['timeline_14_days'] ) && is_array( $snapshot['timeline_14_days'] ) ? $snapshot['timeline_14_days'] : [] ) )
            . '</div>';

        echo '<div class="kitgenix-muted">'
            . '<strong>' . \esc_html__( 'Top sources:', 'kitgenix-affiliate-link-manager' ) . '</strong> '
            . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_sources_30_days'] ) && is_array( $snapshot['top_sources_30_days'] ) ? $snapshot['top_sources_30_days'] : [], __( 'No source data yet.', 'kitgenix-affiliate-link-manager' ) ) )
            . '</div>';

        echo '<div class="kitgenix-muted">'
            . '<strong>' . \esc_html__( 'Top referrers:', 'kitgenix-affiliate-link-manager' ) . '</strong> '
            . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_referrers_30_days'] ) && is_array( $snapshot['top_referrers_30_days'] ) ? $snapshot['top_referrers_30_days'] : [], __( 'No referrer data yet.', 'kitgenix-affiliate-link-manager' ) ) )
            . '</div>';

        echo '<div class="kitgenix-muted">'
            . '<strong>' . \esc_html__( 'Top placements:', 'kitgenix-affiliate-link-manager' ) . '</strong> '
            . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_placements_30_days'] ) && is_array( $snapshot['top_placements_30_days'] ) ? $snapshot['top_placements_30_days'] : [], __( 'No placement data yet.', 'kitgenix-affiliate-link-manager' ) ) )
            . '</div>';
    }

    private static function render_link_analytics_panel( int $post_id ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        $snapshot = Analytics::get_link_snapshot( $post_id );

        echo '<div class="kitgenix-field">'
            . '<h4>' . \esc_html__( 'Link Analytics', 'kitgenix-affiliate-link-manager' ) . '</h4>'
            . '<div class="kitgenix-muted">'
            . \esc_html(
                sprintf(
                    /* translators: 1: clicks in the last 7 days, 2: clicks in the last 30 days. */
                    __( 'Last 7 days: %1$s clicks | Last 30 days: %2$s clicks', 'kitgenix-affiliate-link-manager' ),
                    \number_format_i18n( (int) ( $snapshot['clicks_last_7_days'] ?? 0 ) ),
                    \number_format_i18n( (int) ( $snapshot['clicks_last_30_days'] ?? 0 ) )
                )
            )
            . '</div>'
            . '<div class="kitgenix-muted"><strong>' . \esc_html__( 'Timeline:', 'kitgenix-affiliate-link-manager' ) . '</strong> ' . \esc_html( self::format_analytics_timeline_summary( isset( $snapshot['timeline_14_days'] ) && is_array( $snapshot['timeline_14_days'] ) ? $snapshot['timeline_14_days'] : [] ) ) . '</div>'
            . '<div class="kitgenix-muted"><strong>' . \esc_html__( 'Top sources:', 'kitgenix-affiliate-link-manager' ) . '</strong> ' . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_sources_30_days'] ) && is_array( $snapshot['top_sources_30_days'] ) ? $snapshot['top_sources_30_days'] : [], __( 'No source data yet.', 'kitgenix-affiliate-link-manager' ) ) ) . '</div>'
            . '<div class="kitgenix-muted"><strong>' . \esc_html__( 'Top referrers:', 'kitgenix-affiliate-link-manager' ) . '</strong> ' . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_referrers_30_days'] ) && is_array( $snapshot['top_referrers_30_days'] ) ? $snapshot['top_referrers_30_days'] : [], __( 'No referrer data yet.', 'kitgenix-affiliate-link-manager' ) ) ) . '</div>'
            . '<div class="kitgenix-muted"><strong>' . \esc_html__( 'Top placements:', 'kitgenix-affiliate-link-manager' ) . '</strong> ' . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_placements_30_days'] ) && is_array( $snapshot['top_placements_30_days'] ) ? $snapshot['top_placements_30_days'] : [], __( 'No placement data yet.', 'kitgenix-affiliate-link-manager' ) ) ) . '</div>'
            . '</div>';
    }

    public static function register_menu(): void {
        if ( function_exists( 'kitgenix_ensure_admin_menu' ) ) {
            \kitgenix_ensure_admin_menu();
        }

        $capability = self::get_manage_capability();

        self::$page_hook = \add_submenu_page(
            'kitgenix',
            __( 'Affiliate Link Manager', 'kitgenix-affiliate-link-manager' ),
            __( 'Affiliate Links', 'kitgenix-affiliate-link-manager' ),
            $capability,
            'kitgenix-affiliate-link-manager',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function enqueue_assets( string $hook_suffix ): void {
        // Only enqueue on our page.
        if ( self::$page_hook && $hook_suffix !== self::$page_hook ) {
            return;
        }

        // Fallback if $page_hook isn't set for some reason.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page = isset( $_GET['page'] ) ? \sanitize_key( \wp_unslash( $_GET['page'] ) ) : '';
        if ( self::$page_hook === null && $page !== 'kitgenix-affiliate-link-manager' ) {
            return;
        }

        $ver = defined( 'KITGENIX_AFFILIATE_LINK_MANAGER_VERSION' ) ? (string) KITGENIX_AFFILIATE_LINK_MANAGER_VERSION : '1.1.1';
        $base_dir = defined( 'KITGENIX_AFFILIATE_LINK_MANAGER_DIR' ) ? (string) KITGENIX_AFFILIATE_LINK_MANAGER_DIR : '';
        $admin_css_file = $base_dir ? $base_dir . 'assets/css/admin.css' : '';
        $admin_js_file = $base_dir ? $base_dir . 'assets/js/admin.js' : '';
        $ui_js_file = $base_dir ? $base_dir . 'assets/js/kitgenix-admin-tabs.js' : '';
        $admin_css_ver = ( $admin_css_file && file_exists( $admin_css_file ) ) ? (string) filemtime( $admin_css_file ) : $ver;
        $admin_js_ver = ( $admin_js_file && file_exists( $admin_js_file ) ) ? (string) filemtime( $admin_js_file ) : $ver;
        $ui_js_ver = ( $ui_js_file && file_exists( $ui_js_file ) ) ? (string) filemtime( $ui_js_file ) : $ver;

        \wp_enqueue_style( 'kitgenix-admin-ui' );

        \wp_register_style( 'kitgenix-affiliate-link-manager-admin', \plugins_url( 'assets/css/admin.css', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ), [ 'kitgenix-admin-ui' ], $admin_css_ver );
        \wp_enqueue_style( 'kitgenix-affiliate-link-manager-admin' );

        \wp_register_script( 'kitgenix-admin-tabs', \plugins_url( 'assets/js/kitgenix-admin-tabs.js', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ), [], $ui_js_ver, true );
        \wp_enqueue_script( 'kitgenix-admin-tabs' );

        \wp_register_script( 'kitgenix-affiliate-link-manager-admin', \plugins_url( 'assets/js/admin.js', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ), [], $admin_js_ver, true );
        \wp_enqueue_script( 'kitgenix-affiliate-link-manager-admin' );
    }

    private static function render_admin_notices(): void {
        \settings_errors();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $saved = isset( $_GET['kitgenix_saved'] ) ? (int) $_GET['kitgenix_saved'] : 0;
        if ( $saved ) {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . \esc_html__( 'Link saved.', 'kitgenix-affiliate-link-manager' )
                . '</p></div>';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $deleted = isset( $_GET['kitgenix_deleted'] ) ? (int) $_GET['kitgenix_deleted'] : 0;
        if ( $deleted ) {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . \esc_html__( 'Link deleted.', 'kitgenix-affiliate-link-manager' )
                . '</p></div>';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $duplicated = isset( $_GET['kitgenix_duplicated'] ) ? (int) $_GET['kitgenix_duplicated'] : 0;
        if ( $duplicated ) {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . \esc_html__( 'Link duplicated.', 'kitgenix-affiliate-link-manager' )
                . '</p></div>';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $reset = isset( $_GET['kitgenix_reset'] ) ? (int) $_GET['kitgenix_reset'] : 0;
        if ( $reset ) {
            echo '<div class="notice notice-success is-dismissible"><p>'
                . \esc_html__( 'Click count reset.', 'kitgenix-affiliate-link-manager' )
                . '</p></div>';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bulk_deleted = isset( $_GET['kitgenix_bulk_deleted'] ) ? (int) $_GET['kitgenix_bulk_deleted'] : 0;
        if ( $bulk_deleted > 0 ) {
            /* translators: %d: number of affiliate links deleted by the bulk action. */
            $bulk_deleted_message = sprintf( \_n( '%d link deleted.', '%d links deleted.', $bulk_deleted, 'kitgenix-affiliate-link-manager' ), $bulk_deleted );
            echo '<div class="notice notice-success is-dismissible"><p>'
                . \esc_html( $bulk_deleted_message )
                . '</p></div>';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bulk_reset = isset( $_GET['kitgenix_bulk_reset'] ) ? (int) $_GET['kitgenix_bulk_reset'] : 0;
        if ( $bulk_reset > 0 ) {
            /* translators: %d: number of affiliate link click counts reset by the bulk action. */
            $bulk_reset_message = sprintf( \_n( '%d click count reset.', '%d click counts reset.', $bulk_reset, 'kitgenix-affiliate-link-manager' ), $bulk_reset );
            echo '<div class="notice notice-success is-dismissible"><p>'
                . \esc_html( $bulk_reset_message )
                . '</p></div>';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $error = isset( $_GET['kitgenix_error'] ) ? (string) \sanitize_text_field( \wp_unslash( $_GET['kitgenix_error'] ) ) : '';
        if ( $error !== '' ) {
            echo '<div class="notice notice-error"><p>'
                . \esc_html( $error )
                . '</p></div>';
        }

        self::render_health_summary_notice();
    }

    private static function render_health_summary_notice(): void {
        if ( ! class_exists( Health_Checks::class ) ) {
            return;
        }

        $summary = Health_Checks::get_summary();
        $total   = isset( $summary['total'] ) ? (int) $summary['total'] : 0;
        $critical = isset( $summary['critical'] ) ? (int) $summary['critical'] : 0;
        $warning  = isset( $summary['warning'] ) ? (int) $summary['warning'] : 0;
        $unknown  = isset( $summary['unknown'] ) ? (int) $summary['unknown'] : 0;
        $checked_at = isset( $summary['checked_at'] ) ? (int) strtotime( (string) $summary['checked_at'] ) : 0;

        if ( $total > 0 && $checked_at <= 0 ) {
            echo '<div class="notice notice-info"><p>'
                . \esc_html__( 'Destination health checks are queued. The first automated results will appear here shortly.', 'kitgenix-affiliate-link-manager' )
                . '</p></div>';
            return;
        }

        if ( $critical < 1 && $warning < 1 ) {
            return;
        }

        $messages = [];
        if ( $critical > 0 ) {
            $messages[] = sprintf(
                /* translators: %d: broken affiliate destination count. */
                \_n( '%d link has broken destinations.', '%d links have broken destinations.', $critical, 'kitgenix-affiliate-link-manager' ),
                $critical
            );
        }
        if ( $warning > 0 ) {
            $messages[] = sprintf(
                /* translators: %d: redirecting affiliate destination count. */
                \_n( '%d link has redirecting destinations.', '%d links have redirecting destinations.', $warning, 'kitgenix-affiliate-link-manager' ),
                $warning
            );
        }
        if ( $unknown > 0 ) {
            $messages[] = sprintf(
                /* translators: %d: link count still waiting for health results. */
                \_n( '%d link is still waiting for its first health check.', '%d links are still waiting for their first health check.', $unknown, 'kitgenix-affiliate-link-manager' ),
                $unknown
            );
        }

        echo '<div class="notice ' . ( $critical > 0 ? 'notice-error' : 'notice-warning' ) . '"><p>'
            . \esc_html( implode( ' ', $messages ) )
            . '</p>';

        if ( ! empty( $summary['issues'] ) && is_array( $summary['issues'] ) ) {
            $issue_links = [];

            foreach ( $summary['issues'] as $issue ) {
                if ( ! is_array( $issue ) || empty( $issue['id'] ) ) {
                    continue;
                }

                $issue_url = self::get_links_tab_url(
                    [
                        'kitgenix_action' => 'edit',
                        'link_id'         => (int) $issue['id'],
                    ]
                );

                $issue_links[] = '<a href="' . \esc_url( $issue_url ) . '">' . \esc_html( (string) ( $issue['title'] ?? '' ) ) . '</a>: ' . \esc_html( (string) ( $issue['message'] ?? '' ) );
            }

            if ( ! empty( $issue_links ) ) {
                echo '<p>' . \wp_kses_post( implode( ' | ', $issue_links ) ) . '</p>';
            }
        }

        if ( $checked_at > 0 ) {
            echo '<p><small>'
                . \esc_html(
                    sprintf(
                        /* translators: %s: local date and time of last health summary refresh. */
                        __( 'Last checked %s.', 'kitgenix-affiliate-link-manager' ),
                        self::format_schedule_timestamp( $checked_at )
                    )
                )
                . '</small></p>';
        }

        echo '</div>';
    }

    public static function render_page(): void {
        if ( ! self::user_can_manage_links() ) {
            \wp_die( \esc_html__( 'Sorry, you are not allowed to access this page.', 'kitgenix-affiliate-link-manager' ) );
        }

        $settings = Settings::get_settings();
        $prefix   = Settings::get_prefix();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab = isset( $_GET['tab'] ) ? \sanitize_key( \wp_unslash( $_GET['tab'] ) ) : '';
        $allowed_tabs = [ 'links', 'settings', 'support', 'log' ];
        $default_tab  = \in_array( $tab, $allowed_tabs, true ) ? $tab : 'links';

        $edit_post = null;
        if ( $default_tab === 'links' ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $action = isset( $_GET['kitgenix_action'] ) ? \sanitize_key( \wp_unslash( $_GET['kitgenix_action'] ) ) : '';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $edit_id = isset( $_GET['link_id'] ) ? (int) $_GET['link_id'] : 0;

            if ( $action === 'edit' && $edit_id > 0 ) {
                $p = \get_post( $edit_id );
                if ( $p instanceof \WP_Post && $p->post_type === Affiliate_Link_Post_Type::POST_TYPE ) {
                    $edit_post = $p;
                }
            }
        }

        $ver = defined( 'KITGENIX_AFFILIATE_LINK_MANAGER_VERSION' ) ? (string) KITGENIX_AFFILIATE_LINK_MANAGER_VERSION : '1.1.1';

        self::render_admin_notices();

        $logo        = \plugins_url( 'assets/images/logos/kitgenix-favicon-purple.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE );
        $base_tab_url = \admin_url( 'admin.php?page=' . rawurlencode( 'kitgenix-affiliate-link-manager' ) );

        echo '<div class="wrap kitgenix-admin-app kitgenix-affiliate-link-manager-use-top-tabs" data-kitgenix-tabs data-kitgenix-default-tab="' . \esc_attr( $default_tab ) . '" id="kitgenix-affiliate-link-manager-admin-app">';

        echo '<div class="kitgenix-affiliate-link-manager-settings-intro kitgenix-settings-header">'
            . '<div class="kitgenix-settings-header-row">'
            . '<div class="kitgenix-settings-header-main">'
            . '<div class="kitgenix-settings-brand">'
            . '<img class="kitgenix-settings-logo" src="' . \esc_url( $logo ) . '" alt="' . \esc_attr__( 'Kitgenix', 'kitgenix-affiliate-link-manager' ) . '" />'
            . '<h1 class="kitgenix-affiliate-link-manager-admin-title">' . \esc_html__( 'Kitgenix Affiliate Link Manager', 'kitgenix-affiliate-link-manager' ) . '</h1>'
            . '</div>'
            . '<p>' . \esc_html__( 'Create short redirect links for your affiliate URLs in one place. Share clean links like /go/my-link and track clicks.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '<div class="kitgenix-settings-meta">'
            . '<span class="kitgenix-settings-version" aria-label="Plugin version">v' . \esc_html( $ver ) . '</span>'
            . '</div>'
            . '</div>'
            . '<div class="kitgenix-settings-header-actions">'
            . '<div class="kitgenix-intro-links kitgenix-affiliate-link-manager-intro-links">'
            . '<a href="' . \esc_url( 'https://kitgenix.com/plugins/kitgenix-affiliate-link-manager/documentation/' ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Documentation', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '<a href="' . \esc_url( 'https://wordpress.org/support/plugin/kitgenix-affiliate-link-manager/reviews/#new-post' ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Review Plugin', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '<a href="' . \esc_url( 'https://wordpress.org/support/plugin/kitgenix-affiliate-link-manager/' ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Support Request', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '<a href="' . \esc_url( 'https://buymeacoffee.com/kitgenix' ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Support Kitgenix', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '</div>'
            . '<div class="kitgenix-social-links kitgenix-social-links--icons">'
            . '<a href="https://kitgenix.com" target="_blank" rel="noopener noreferrer" aria-label="Website" title="Website"><img src="' . \esc_url( \plugins_url( 'assets/images/social-media/globe-solid.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">Website</span></a>'
            . '<a href="https://www.facebook.com/groups/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="Facebook Community" title="Facebook Community"><img src="' . \esc_url( \plugins_url( 'assets/images/social-media/facebook-solid.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">Facebook Community</span></a>'
            . '<a href="https://www.facebook.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"><img src="' . \esc_url( \plugins_url( 'assets/images/social-media/facebook-solid.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">Facebook</span></a>'
            . '<a href="https://www.instagram.com/kitgenix/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram"><img src="' . \esc_url( \plugins_url( 'assets/images/social-media/instagram-solid.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">Instagram</span></a>'
            . '<a href="https://www.youtube.com/@Kitgenix" target="_blank" rel="noopener noreferrer" aria-label="YouTube" title="YouTube"><img src="' . \esc_url( \plugins_url( 'assets/images/social-media/youtube-solid.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">YouTube</span></a>'
            . '<a href="https://www.reddit.com/r/Kitgenix/" target="_blank" rel="noopener noreferrer" aria-label="Reddit" title="Reddit"><img src="' . \esc_url( \plugins_url( 'assets/images/social-media/reddit-solid.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">Reddit</span></a>'
            . '<a href="https://www.linkedin.com/company/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" title="LinkedIn"><img src="' . \esc_url( \plugins_url( 'assets/images/social-media/linkedin-solid.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">LinkedIn</span></a>'
            . '<a href="https://x.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="X" title="X"><img src="' . \esc_url( \plugins_url( 'assets/images/social-media/x-solid.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">X</span></a>'
            . '<a href="https://www.tiktok.com/@kitgenix" target="_blank" rel="noopener noreferrer" aria-label="TikTok" title="TikTok"><img src="' . \esc_url( \plugins_url( 'assets/images/social-media/tiktok-solid.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">TikTok</span></a>'
            . '<a href="https://github.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="GitHub" title="GitHub"><img src="' . \esc_url( \plugins_url( 'assets/images/social-media/github-solid.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">GitHub</span></a>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';

        echo '<h2 class="nav-tab-wrapper kitgenix-nav-tabs" aria-label="Settings navigation">'
            . '<a class="nav-tab kitgenix-tab-trigger ' . ( $default_tab === 'links' ? 'nav-tab-active' : '' ) . '" href="' . \esc_url( $base_tab_url . '&tab=links#kitgenix-tab-links' ) . '" data-kitgenix-tab="links">' . \esc_html__( 'Links', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '<a class="nav-tab kitgenix-tab-trigger ' . ( $default_tab === 'settings' ? 'nav-tab-active' : '' ) . '" href="' . \esc_url( $base_tab_url . '&tab=settings#kitgenix-tab-settings' ) . '" data-kitgenix-tab="settings">' . \esc_html__( 'Settings', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '<a class="nav-tab kitgenix-tab-trigger ' . ( $default_tab === 'support' ? 'nav-tab-active' : '' ) . '" href="' . \esc_url( $base_tab_url . '&tab=support#kitgenix-tab-support' ) . '" data-kitgenix-tab="support">' . \esc_html__( 'Support', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '<a class="nav-tab kitgenix-tab-trigger ' . ( $default_tab === 'log' ? 'nav-tab-active' : '' ) . '" href="' . \esc_url( $base_tab_url . '&tab=log#kitgenix-tab-log' ) . '" data-kitgenix-tab="log">' . \esc_html__( 'Log', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '</h2>';

        echo '<div class="kitgenix-settings-layout"><div class="kitgenix-settings-content" id="kitgenix-settings-content" tabindex="-1">';

        echo '<div id="kitgenix-tab-links" data-kitgenix-tab-panel="links"' . ( $default_tab !== 'links' ? ' hidden="hidden" style="display:none;"' : '' ) . '>';
        self::render_links_tab( $prefix, $edit_post );
        echo '</div>';

        echo '<div id="kitgenix-tab-settings" data-kitgenix-tab-panel="settings"' . ( $default_tab !== 'settings' ? ' hidden="hidden" style="display:none;"' : '' ) . '>';
        self::render_settings_tab( $settings, $prefix );
        echo '</div>';

        echo '<div id="kitgenix-tab-support" data-kitgenix-tab-panel="support"' . ( $default_tab !== 'support' ? ' hidden="hidden" style="display:none;"' : '' ) . '>';
        self::render_support_tab( $settings, $prefix );
        echo '</div>';

        echo '<div id="kitgenix-tab-log" data-kitgenix-tab-panel="log"' . ( $default_tab !== 'log' ? ' hidden="hidden" style="display:none;"' : '' ) . '>';
        self::render_log_tab();
        echo '</div>';

        echo '</div>';
        self::render_sidebar();
        echo '</div>';
    }

    private static function render_sidebar(): void {
        $social_base = \plugins_url( 'assets/images/social-media/', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE );

        echo '<aside class="kitgenix-settings-sidebar" aria-label="' . \esc_attr__( 'Help and links', 'kitgenix-affiliate-link-manager' ) . '">';

        echo '<div class="kitgenix-sidebar-card">'
            . '<h2>' . \esc_html__( 'Need Help?', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<p>' . \esc_html__( 'Open the documentation for setup guidance or send us a support request if you need help configuring the plugin.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '<div class="kitgenix-sidebar-actions">'
            . '<a class="button button-secondary" href="' . \esc_url( 'https://kitgenix.com/plugins/kitgenix-affiliate-link-manager/documentation/' ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Documentation', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '<a class="button button-primary" href="' . \esc_url( 'https://wordpress.org/support/plugin/kitgenix-affiliate-link-manager/' ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Request Support', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '</div>'
            . '</div>';

        echo '<div class="kitgenix-sidebar-card">'
            . '<h2>' . \esc_html__( 'Visit Our Official Facebook Group', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<p>' . \esc_html__( 'Join the Kitgenix community to ask questions, share feedback, and keep up with product updates.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '<div class="kitgenix-sidebar-actions">'
            . '<a class="button button-secondary" href="' . \esc_url( 'https://www.facebook.com/groups/kitgenix' ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Join Group', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '</div>'
            . '</div>';

        echo '<div class="kitgenix-sidebar-card">'
            . '<h2>' . \esc_html__( 'Follow Us', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<p>' . \esc_html__( 'Keep up with new releases, tutorials, and product news across our channels.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '<div class="kitgenix-sidebar-social-grid">'
            . '<a class="kitgenix-sidebar-social-link" href="https://kitgenix.com" target="_blank" rel="noopener noreferrer" aria-label="Website" title="Website"><img src="' . \esc_url( $social_base . 'globe-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
            . '<a class="kitgenix-sidebar-social-link" href="https://www.facebook.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"><img src="' . \esc_url( $social_base . 'facebook-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
            . '<a class="kitgenix-sidebar-social-link" href="https://www.instagram.com/kitgenix/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram"><img src="' . \esc_url( $social_base . 'instagram-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
            . '<a class="kitgenix-sidebar-social-link" href="https://www.youtube.com/@Kitgenix" target="_blank" rel="noopener noreferrer" aria-label="YouTube" title="YouTube"><img src="' . \esc_url( $social_base . 'youtube-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
            . '<a class="kitgenix-sidebar-social-link" href="https://www.reddit.com/r/Kitgenix/" target="_blank" rel="noopener noreferrer" aria-label="Reddit" title="Reddit"><img src="' . \esc_url( $social_base . 'reddit-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
            . '<a class="kitgenix-sidebar-social-link" href="https://www.linkedin.com/company/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" title="LinkedIn"><img src="' . \esc_url( $social_base . 'linkedin-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
            . '<a class="kitgenix-sidebar-social-link" href="https://x.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="X" title="X"><img src="' . \esc_url( $social_base . 'x-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
            . '<a class="kitgenix-sidebar-social-link" href="https://www.tiktok.com/@kitgenix" target="_blank" rel="noopener noreferrer" aria-label="TikTok" title="TikTok"><img src="' . \esc_url( $social_base . 'tiktok-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
            . '<a class="kitgenix-sidebar-social-link" href="https://github.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="GitHub" title="GitHub"><img src="' . \esc_url( $social_base . 'github-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
            . '</div>'
            . '</div>';

        echo '</aside>';
    }

    private static function render_links_tab( string $prefix, $edit_post ): void {
        $is_edit = ( $edit_post instanceof \WP_Post );
        $edit_id = $is_edit ? (int) $edit_post->ID : 0;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $state = self::get_links_list_state( $_GET );
        $search       = $state['s'];
        $group_filter = $state['campaign_group'];
        $tag_filter   = $state['campaign_tag'];
        $paged        = $state['paged'];
        $orderby      = $state['orderby'];
        $order        = $state['order'];

        if ( $group_filter !== '' && $group_filter !== '__ungrouped' ) {
            $group_term = \get_term_by( 'slug', $group_filter, Affiliate_Link_Post_Type::TAXONOMY_GROUP );
            if ( ! ( $group_term instanceof \WP_Term ) ) {
                $group_filter = '';
            }
        }

        if ( $tag_filter !== '' ) {
            $tag_term = \get_term_by( 'slug', $tag_filter, Affiliate_Link_Post_Type::TAXONOMY_TAG );
            if ( ! ( $tag_term instanceof \WP_Term ) ) {
                $tag_filter = '';
            }
        }

        $links_per_page = Settings::get_links_per_page();

        $group_terms = \get_terms(
            [
                'taxonomy'   => Affiliate_Link_Post_Type::TAXONOMY_GROUP,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]
        );
        if ( \is_wp_error( $group_terms ) || ! is_array( $group_terms ) ) {
            $group_terms = [];
        }

        $tag_terms = \get_terms(
            [
                'taxonomy'   => Affiliate_Link_Post_Type::TAXONOMY_TAG,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]
        );
        if ( \is_wp_error( $tag_terms ) || ! is_array( $tag_terms ) ) {
            $tag_terms = [];
        }

        $title         = $is_edit ? (string) $edit_post->post_title : '';
        $slug          = $is_edit ? (string) $edit_post->post_name : '';
        $dest          = $is_edit ? Affiliate_Link_Post_Type::get_destination_url( $edit_id ) : '';
        $rel           = $is_edit ? Affiliate_Link_Post_Type::get_rel_value( $edit_id ) : 'nofollow sponsored';
        $enabled       = $is_edit ? Affiliate_Link_Post_Type::is_enabled( $edit_id ) : true;
        $group_name    = $is_edit ? Affiliate_Link_Post_Type::get_primary_group_name( $edit_id ) : '';
        $tags          = $is_edit ? \implode( ', ', Affiliate_Link_Post_Type::get_tag_names( $edit_id ) ) : '';
        $campaign_note = $is_edit ? Affiliate_Link_Post_Type::get_campaign_note( $edit_id ) : '';
        $starts_at     = $is_edit ? Affiliate_Link_Post_Type::format_timestamp_for_input( Affiliate_Link_Post_Type::get_starts_at_timestamp( $edit_id ) ) : '';
        $expires_at    = $is_edit ? Affiliate_Link_Post_Type::format_timestamp_for_input( Affiliate_Link_Post_Type::get_expires_at_timestamp( $edit_id ) ) : '';
        $fallback_url  = $is_edit ? Affiliate_Link_Post_Type::get_fallback_url( $edit_id ) : '';
        $rotation_mode = $is_edit ? Affiliate_Link_Post_Type::get_rotation_mode( $edit_id ) : 'none';
        $rotation_destinations = $is_edit ? Affiliate_Link_Post_Type::get_rotation_destinations_text( $edit_id ) : '';

        $current_view_args = [
            'orderby' => $orderby,
            'order'   => $order,
        ];
        if ( $search !== '' ) {
            $current_view_args['s'] = $search;
        }
        if ( $group_filter !== '' ) {
            $current_view_args['campaign_group'] = $group_filter;
        }
        if ( $tag_filter !== '' ) {
            $current_view_args['campaign_tag'] = $tag_filter;
        }
        if ( $paged > 1 ) {
            $current_view_args['paged'] = $paged;
        }

        $action_return_args = [
            'return_orderby' => $orderby,
            'return_order'   => $order,
        ];
        if ( $search !== '' ) {
            $action_return_args['return_s'] = $search;
        }
        if ( $group_filter !== '' ) {
            $action_return_args['return_group'] = $group_filter;
        }
        if ( $tag_filter !== '' ) {
            $action_return_args['return_tag'] = $tag_filter;
        }
        if ( $paged > 1 ) {
            $action_return_args['return_paged'] = $paged;
        }

        echo '<div class="kitgenix-grid kitgenix-grid--2">';

        echo '<div class="kitgenix-card">'
            . '<h3>' . ( $is_edit ? \esc_html__( 'Edit Link', 'kitgenix-affiliate-link-manager' ) : \esc_html__( 'Add Link', 'kitgenix-affiliate-link-manager' ) ) . '</h3>'
            . '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" id="kitgenix-affiliate-add-form">'
            . '<input type="hidden" name="action" value="kitgenix_affiliate_link_save" />'
            . '<input type="hidden" name="post_id" value="' . \esc_attr( (string) $edit_id ) . '" />';

        \wp_nonce_field( 'kitgenix_affiliate_link_save', 'kitgenix_affiliate_link_nonce' );

        echo '<input type="hidden" name="return_orderby" value="' . \esc_attr( $orderby ) . '" />';
        echo '<input type="hidden" name="return_order" value="' . \esc_attr( $order ) . '" />';
        if ( $search !== '' ) {
            echo '<input type="hidden" name="return_s" value="' . \esc_attr( $search ) . '" />';
        }
        if ( $group_filter !== '' ) {
            echo '<input type="hidden" name="return_group" value="' . \esc_attr( $group_filter ) . '" />';
        }
        if ( $tag_filter !== '' ) {
            echo '<input type="hidden" name="return_tag" value="' . \esc_attr( $tag_filter ) . '" />';
        }
        if ( $paged > 1 ) {
            echo '<input type="hidden" name="return_paged" value="' . \esc_attr( (string) $paged ) . '" />';
        }

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_title">' . \esc_html__( 'Name', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="text" id="kitgenix_affiliate_title" name="title" value="' . \esc_attr( $title ) . '" class="regular-text" required />'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_slug">' . \esc_html__( 'Slug', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="text" id="kitgenix_affiliate_slug" name="slug" value="' . \esc_attr( $slug ) . '" class="regular-text" placeholder="' . \esc_attr__( 'Auto-generated from name', 'kitgenix-affiliate-link-manager' ) . '" />'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Leave blank to auto-generate. You can change it later.', 'kitgenix-affiliate-link-manager' ) . '<br />'
            . \esc_html( \home_url( '/' . $prefix . '/' ) ) . '<span class="kitgenix-code">{slug}</span></div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_destination">' . \esc_html__( 'Destination URL', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="url" id="kitgenix_affiliate_destination" name="destination_url" value="' . \esc_attr( $dest ) . '" class="regular-text" required />'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_starts_at">' . \esc_html__( 'Start date and time', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="datetime-local" id="kitgenix_affiliate_starts_at" name="starts_at" value="' . \esc_attr( $starts_at ) . '" class="regular-text" />'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Leave blank to make the link active immediately. Times use the site timezone.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_expires_at">' . \esc_html__( 'Expiry date and time', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="datetime-local" id="kitgenix_affiliate_expires_at" name="expires_at" value="' . \esc_attr( $expires_at ) . '" class="regular-text" />'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Leave blank to keep the active destination live until you change it.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_fallback_url">' . \esc_html__( 'Fallback URL', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="url" id="kitgenix_affiliate_fallback_url" name="fallback_url" value="' . \esc_attr( $fallback_url ) . '" class="regular-text" />'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Optional. When the link is scheduled but not live yet, or after it expires, visitors are redirected here instead of seeing a 404.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_rotation_mode">' . \esc_html__( 'Rotation mode', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<select id="kitgenix_affiliate_rotation_mode" name="rotation_mode">'
            . '<option value="none"' . \selected( $rotation_mode, 'none', false ) . '>' . \esc_html__( 'No rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="sequential"' . \selected( $rotation_mode, 'sequential', false ) . '>' . \esc_html__( 'Sequential rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="random"' . \selected( $rotation_mode, 'random', false ) . '>' . \esc_html__( 'Random rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select>'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Rotation uses the primary destination plus any additional destinations below while the link is active.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_rotation_destinations">' . \esc_html__( 'Additional rotation destinations', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<textarea id="kitgenix_affiliate_rotation_destinations" name="rotation_destinations" rows="4" class="large-text" placeholder="' . \esc_attr__( "https://example.com/offer-a\nhttps://example.com/offer-b", 'kitgenix-affiliate-link-manager' ) . '">' . \esc_textarea( $rotation_destinations ) . '</textarea>'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Add one URL per line. The primary destination stays in the rotation pool automatically.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_group_name">' . \esc_html__( 'Link group', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="text" id="kitgenix_affiliate_group_name" name="group_name" value="' . \esc_attr( $group_name ) . '" class="regular-text" list="kitgenix-affiliate-group-options" placeholder="' . \esc_attr__( 'Example: Summer Campaigns', 'kitgenix-affiliate-link-manager' ) . '" />'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Assign one primary group so larger link libraries can be split into reusable campaign views.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_tags">' . \esc_html__( 'Tags', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="text" id="kitgenix_affiliate_tags" name="tag_names" value="' . \esc_attr( $tags ) . '" class="regular-text" placeholder="' . \esc_attr__( 'Example: email, q3, review-post', 'kitgenix-affiliate-link-manager' ) . '" />'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Separate tags with commas to label vendors, traffic sources, or campaign types.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_campaign_note">' . \esc_html__( 'Campaign note', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<textarea id="kitgenix_affiliate_campaign_note" name="campaign_note" rows="4" class="large-text">' . \esc_textarea( $campaign_note ) . '</textarea>'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Store quick context for the link, such as placement, partner terms, or campaign reminders.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        if ( ! empty( $group_terms ) ) {
            echo '<datalist id="kitgenix-affiliate-group-options">';
            foreach ( $group_terms as $group_term_option ) {
                if ( ! ( $group_term_option instanceof \WP_Term ) ) {
                    continue;
                }

                echo '<option value="' . \esc_attr( $group_term_option->name ) . '"></option>';
            }
            echo '</datalist>';
        }

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_rel">' . \esc_html__( 'Link rel', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<select id="kitgenix_affiliate_rel" name="rel">'
            . '<option value="nofollow"' . \selected( $rel, 'nofollow', false ) . '>' . \esc_html__( 'nofollow', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="sponsored"' . \selected( $rel, 'sponsored', false ) . '>' . \esc_html__( 'sponsored', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="nofollow sponsored"' . \selected( $rel, 'nofollow sponsored', false ) . '>' . \esc_html__( 'nofollow sponsored', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select>'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Use sponsored/nofollow for affiliate links to follow search engine guidelines.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_enabled">' . \esc_html__( 'Enabled', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<label>'
            . '<input type="checkbox" id="kitgenix_affiliate_enabled" name="enabled" value="1"' . ( $enabled ? ' checked="checked"' : '' ) . ' /> '
            . \esc_html__( 'This link is active and will redirect', 'kitgenix-affiliate-link-manager' )
            . '</label>'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Disable to temporarily stop redirects without deleting the link (disabled links return 404).', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        \submit_button( $is_edit ? \__( 'Update Link', 'kitgenix-affiliate-link-manager' ) : \__( 'Add Link', 'kitgenix-affiliate-link-manager' ) );

        if ( $is_edit ) {
            echo '<a class="button" href="' . \esc_url( self::get_links_tab_url( $current_view_args ) ) . '">' . \esc_html__( 'Cancel', 'kitgenix-affiliate-link-manager' ) . '</a>';
        }

        echo '</form>';

        if ( $is_edit ) {
            self::render_link_analytics_panel( $edit_id );
        }

        echo '</div>';

        $counts = \wp_count_posts( Affiliate_Link_Post_Type::POST_TYPE );
        $total_links = ( is_object( $counts ) && isset( $counts->publish ) ) ? (int) $counts->publish : 0;
        $grouped_links = 0;
        foreach ( $group_terms as $group_term_count ) {
            if ( $group_term_count instanceof \WP_Term ) {
                $grouped_links += max( 0, (int) $group_term_count->count );
            }
        }
        $ungrouped_links = max( 0, $total_links - $grouped_links );

        $view_base_args = [
            'orderby' => $orderby,
            'order'   => $order,
        ];
        if ( $search !== '' ) {
            $view_base_args['s'] = $search;
        }
        if ( $tag_filter !== '' ) {
            $view_base_args['campaign_tag'] = $tag_filter;
        }

        echo '<div class="kitgenix-card">';
        echo '<div class="kitgenix-campaign-views">';
        echo '<span class="kitgenix-campaign-views__label">' . \esc_html__( 'Campaign views', 'kitgenix-affiliate-link-manager' ) . '</span>';
        echo '<div class="kitgenix-campaign-views__list">';
        echo '<a class="kitgenix-campaign-view' . ( $group_filter === '' ? ' is-active' : '' ) . '" href="' . \esc_url( self::get_links_tab_url( $view_base_args ) ) . '">' . \esc_html__( 'All links', 'kitgenix-affiliate-link-manager' ) . ' <span class="kitgenix-campaign-view__count">' . \esc_html( (string) $total_links ) . '</span></a>';

        foreach ( $group_terms as $group_term_view ) {
            if ( ! ( $group_term_view instanceof \WP_Term ) || (int) $group_term_view->count < 1 ) {
                continue;
            }

            $group_view_args = $view_base_args;
            $group_view_args['campaign_group'] = $group_term_view->slug;

            echo '<a class="kitgenix-campaign-view' . ( $group_filter === $group_term_view->slug ? ' is-active' : '' ) . '" href="' . \esc_url( self::get_links_tab_url( $group_view_args ) ) . '">' . \esc_html( $group_term_view->name ) . ' <span class="kitgenix-campaign-view__count">' . \esc_html( (string) (int) $group_term_view->count ) . '</span></a>';
        }

        if ( $ungrouped_links > 0 ) {
            $ungrouped_args = $view_base_args;
            $ungrouped_args['campaign_group'] = '__ungrouped';
            echo '<a class="kitgenix-campaign-view' . ( $group_filter === '__ungrouped' ? ' is-active' : '' ) . '" href="' . \esc_url( self::get_links_tab_url( $ungrouped_args ) ) . '">' . \esc_html__( 'Ungrouped', 'kitgenix-affiliate-link-manager' ) . ' <span class="kitgenix-campaign-view__count">' . \esc_html( (string) $ungrouped_links ) . '</span></a>';
        }

        echo '</div>';
        echo '</div>';

        self::render_analytics_overview_panel();

        echo '<div class="kitgenix-card-header kitgenix-card-header--links">'
            . '<div class="kitgenix-card-header__title">'
            . '<h3 class="kitgenix-card-title">' . \esc_html__( 'All Links', 'kitgenix-affiliate-link-manager' ) . '</h3>'
            . '<p class="kitgenix-card-subtitle">' . \esc_html__( 'Search, filter, and review links by campaign group, tags, notes, and recent analytics context.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<form class="kitgenix-links-toolbar" role="search" method="get" action="' . \esc_url( \admin_url( 'admin.php' ) ) . '">'
            . '<input type="hidden" name="page" value="kitgenix-affiliate-link-manager" />'
            . '<input type="hidden" name="tab" value="links" />'
            . '<input type="hidden" name="orderby" value="' . \esc_attr( $orderby ) . '" />'
            . '<input type="hidden" name="order" value="' . \esc_attr( $order ) . '" />'
            . '<label class="screen-reader-text" for="kitgenix-affiliate-links-search">' . \esc_html__( 'Search links', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="search" id="kitgenix-affiliate-links-search" name="s" value="' . \esc_attr( $search ) . '" data-kitgenix-server-search="1" class="regular-text" placeholder="' . \esc_attr__( 'Search links, groups, tags, or notes...', 'kitgenix-affiliate-link-manager' ) . '" autocomplete="off" />'
            . '<label class="screen-reader-text" for="kitgenix-affiliate-group-filter">' . \esc_html__( 'Filter by group', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<select id="kitgenix-affiliate-group-filter" name="campaign_group">'
            . '<option value="">' . \esc_html__( 'All groups', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="__ungrouped"' . \selected( $group_filter, '__ungrouped', false ) . '>' . \esc_html__( 'Ungrouped', 'kitgenix-affiliate-link-manager' ) . '</option>';

        foreach ( $group_terms as $group_filter_term ) {
            if ( ! ( $group_filter_term instanceof \WP_Term ) ) {
                continue;
            }

            echo '<option value="' . \esc_attr( $group_filter_term->slug ) . '"' . \selected( $group_filter, $group_filter_term->slug, false ) . '>' . \esc_html( $group_filter_term->name ) . '</option>';
        }

        echo '</select>'
            . '<label class="screen-reader-text" for="kitgenix-affiliate-tag-filter">' . \esc_html__( 'Filter by tag', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<select id="kitgenix-affiliate-tag-filter" name="campaign_tag">'
            . '<option value="">' . \esc_html__( 'All tags', 'kitgenix-affiliate-link-manager' ) . '</option>';

        foreach ( $tag_terms as $tag_filter_term ) {
            if ( ! ( $tag_filter_term instanceof \WP_Term ) ) {
                continue;
            }

            echo '<option value="' . \esc_attr( $tag_filter_term->slug ) . '"' . \selected( $tag_filter, $tag_filter_term->slug, false ) . '>' . \esc_html( $tag_filter_term->name ) . '</option>';
        }

        $clear_args = [
            'orderby' => $orderby,
            'order'   => $order,
        ];

        echo '</select>'
            . '<button type="submit" class="button">' . \esc_html__( 'Filter', 'kitgenix-affiliate-link-manager' ) . '</button>'
            . ( $search !== '' || $group_filter !== '' || $tag_filter !== ''
                ? '<a class="button" id="kitgenix-affiliate-links-clear" href="' . \esc_url( self::get_links_tab_url( $clear_args ) ) . '">' . \esc_html__( 'Clear', 'kitgenix-affiliate-link-manager' ) . '</a>'
                : '' )
            . '<span class="kitgenix-muted kitgenix-links-count" id="kitgenix-affiliate-links-count" aria-live="polite"></span>'
            . '</form>'
            . '</div>';

        $query_args = [
            'post_type'               => Affiliate_Link_Post_Type::POST_TYPE,
            'post_status'             => 'publish',
            'posts_per_page'          => $links_per_page,
            'paged'                   => $paged,
            'orderby'                 => 'date',
            'order'                   => $order,
            'no_found_rows'           => false,
            'cache_results'           => true,
            'update_post_meta_cache'  => true,
            'update_post_term_cache'  => true,
            'suppress_filters'        => false,
            'kitgenix_aff_links_list' => 1,
            'kitgenix_search'         => $search,
            'kitgenix_sort_column'    => $orderby,
        ];

        if ( $orderby === 'title' ) {
            $query_args['orderby'] = 'title';
        } elseif ( $orderby === 'slug' ) {
            $query_args['orderby'] = 'name';
        }

        $tax_query = [];
        if ( $group_filter === '__ungrouped' ) {
            $tax_query[] = [
                'taxonomy' => Affiliate_Link_Post_Type::TAXONOMY_GROUP,
                'operator' => 'NOT EXISTS',
            ];
        } elseif ( $group_filter !== '' ) {
            $tax_query[] = [
                'taxonomy' => Affiliate_Link_Post_Type::TAXONOMY_GROUP,
                'field'    => 'slug',
                'terms'    => [ $group_filter ],
            ];
        }

        if ( $tag_filter !== '' ) {
            $tax_query[] = [
                'taxonomy' => Affiliate_Link_Post_Type::TAXONOMY_TAG,
                'field'    => 'slug',
                'terms'    => [ $tag_filter ],
            ];
        }

        if ( ! empty( $tax_query ) ) {
            if ( count( $tax_query ) > 1 ) {
                $tax_query['relation'] = 'AND';
            }
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filters are optional list constraints in admin and keep query results accurate.
            $query_args['tax_query'] = $tax_query;
        }

        $join_filter     = null;
        $where_filter    = null;
        $distinct_filter = null;
        $clauses_filter  = null;

        if ( $search !== '' ) {
            $join_filter = static function ( string $join, \WP_Query $q ): string {
                if ( ! $q->get( 'kitgenix_aff_links_list' ) || $q->get( 'kitgenix_search' ) === '' ) {
                    return $join;
                }

                global $wpdb;
                if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
                    return $join;
                }

                $join .= $wpdb->prepare(
                    " LEFT JOIN {$wpdb->postmeta} AS kitgenix_aff_dest_meta ON ({$wpdb->posts}.ID = kitgenix_aff_dest_meta.post_id AND kitgenix_aff_dest_meta.meta_key = %s) ",
                    Affiliate_Link_Post_Type::META_DESTINATION
                );
                $join .= $wpdb->prepare(
                    " LEFT JOIN {$wpdb->postmeta} AS kitgenix_aff_note_meta ON ({$wpdb->posts}.ID = kitgenix_aff_note_meta.post_id AND kitgenix_aff_note_meta.meta_key = %s) ",
                    Affiliate_Link_Post_Type::META_CAMPAIGN_NOTE
                );
                $join .= " LEFT JOIN {$wpdb->term_relationships} AS kitgenix_aff_term_rel ON ({$wpdb->posts}.ID = kitgenix_aff_term_rel.object_id) ";
                $join .= " LEFT JOIN {$wpdb->term_taxonomy} AS kitgenix_aff_term_tax ON (kitgenix_aff_term_rel.term_taxonomy_id = kitgenix_aff_term_tax.term_taxonomy_id) ";
                $join .= " LEFT JOIN {$wpdb->terms} AS kitgenix_aff_terms ON (kitgenix_aff_term_tax.term_id = kitgenix_aff_terms.term_id) ";

                return $join;
            };

            $where_filter = static function ( string $where, \WP_Query $q ): string {
                if ( ! $q->get( 'kitgenix_aff_links_list' ) || $q->get( 'kitgenix_search' ) === '' ) {
                    return $where;
                }

                global $wpdb;
                if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
                    return $where;
                }

                $term = (string) $q->get( 'kitgenix_search' );
                $like = '%' . $wpdb->esc_like( $term ) . '%';

                return $where . $wpdb->prepare(
                    " AND ( {$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_name LIKE %s OR kitgenix_aff_dest_meta.meta_value LIKE %s OR kitgenix_aff_note_meta.meta_value LIKE %s OR ( kitgenix_aff_term_tax.taxonomy IN (%s, %s) AND kitgenix_aff_terms.name LIKE %s ) ) ",
                    $like,
                    $like,
                    $like,
                    $like,
                    Affiliate_Link_Post_Type::TAXONOMY_GROUP,
                    Affiliate_Link_Post_Type::TAXONOMY_TAG,
                    $like
                );
            };

            $distinct_filter = static function ( string $distinct, \WP_Query $q ): string {
                if ( ! $q->get( 'kitgenix_aff_links_list' ) || $q->get( 'kitgenix_search' ) === '' ) {
                    return $distinct;
                }

                return 'DISTINCT';
            };

            \add_filter( 'posts_join', $join_filter, 10, 2 );
            \add_filter( 'posts_where', $where_filter, 10, 2 );
            \add_filter( 'posts_distinct', $distinct_filter, 10, 2 );
        }

        if ( $orderby === 'clicks' ) {
            $clauses_filter = static function ( array $clauses, \WP_Query $q ): array {
                if ( ! $q->get( 'kitgenix_aff_links_list' ) || 'clicks' !== $q->get( 'kitgenix_sort_column' ) ) {
                    return $clauses;
                }

                global $wpdb;
                if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
                    return $clauses;
                }

                $clicks_join = $wpdb->prepare(
                    " LEFT JOIN {$wpdb->postmeta} AS kitgenix_aff_clicks_meta ON ({$wpdb->posts}.ID = kitgenix_aff_clicks_meta.post_id AND kitgenix_aff_clicks_meta.meta_key = %s) ",
                    Affiliate_Link_Post_Type::META_CLICKS
                );

                if ( false === strpos( $clauses['join'], 'kitgenix_aff_clicks_meta' ) ) {
                    $clauses['join'] .= $clicks_join;
                }

                $sort_direction = strtoupper( (string) $q->get( 'order' ) );
                $sort_direction = in_array( $sort_direction, [ 'ASC', 'DESC' ], true ) ? $sort_direction : 'DESC';
                $clauses['groupby'] = "{$wpdb->posts}.ID";
                $clauses['orderby'] = "CAST(COALESCE(kitgenix_aff_clicks_meta.meta_value, '0') AS UNSIGNED) {$sort_direction}, {$wpdb->posts}.post_date DESC";

                return $clauses;
            };

            \add_filter( 'posts_clauses', $clauses_filter, 10, 2 );
        }

        try {
            $links_query = new \WP_Query( $query_args );
        } finally {
            if ( $orderby === 'clicks' ) {
                \remove_filter( 'posts_clauses', $clauses_filter, 10 );
            }
            if ( $search !== '' ) {
                \remove_filter( 'posts_join', $join_filter, 10 );
                \remove_filter( 'posts_where', $where_filter, 10 );
                \remove_filter( 'posts_distinct', $distinct_filter, 10 );
            }
        }

        $links = isset( $links_query->posts ) && is_array( $links_query->posts ) ? $links_query->posts : [];

        if ( empty( $links ) ) {
            if ( $search !== '' || $group_filter !== '' || $tag_filter !== '' ) {
                echo '<p class="kitgenix-muted">' . \esc_html__( 'No links match the current campaign filters.', 'kitgenix-affiliate-link-manager' ) . '</p>';
            } else {
                echo '<p class="kitgenix-muted">' . \esc_html__( 'No links yet. Add your first one on the left.', 'kitgenix-affiliate-link-manager' ) . '</p>';
            }

            echo '</div></div>';
            return;
        }

        $base_args = [
            'page'    => 'kitgenix-affiliate-link-manager',
            'tab'     => 'links',
            'orderby' => $orderby,
            'order'   => $order,
        ];
        if ( $search !== '' ) {
            $base_args['s'] = $search;
        }
        if ( $group_filter !== '' ) {
            $base_args['campaign_group'] = $group_filter;
        }
        if ( $tag_filter !== '' ) {
            $base_args['campaign_tag'] = $tag_filter;
        }
        if ( $paged > 1 ) {
            $base_args['paged'] = $paged;
        }

        $build_sort_url = static function ( string $col ) use ( $base_args, $orderby, $order ): string {
            $next_order = 'ASC';
            if ( $orderby === $col ) {
                $next_order = ( $order === 'ASC' ) ? 'DESC' : 'ASC';
            } else {
                $next_order = ( $col === 'date' || $col === 'clicks' ) ? 'DESC' : 'ASC';
            }

            $args = $base_args;
            $args['orderby'] = $col;
            $args['order']   = $next_order;
            return \add_query_arg( $args, \admin_url( 'admin.php' ) ) . '#kitgenix-tab-links';
        };

        $aria_sort = static function ( string $col ) use ( $orderby, $order ): string {
            if ( $orderby !== $col ) {
                return 'none';
            }

            return $order === 'ASC' ? 'ascending' : 'descending';
        };

        echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" class="kitgenix-links-bulk">'
            . '<input type="hidden" name="action" value="kitgenix_affiliate_link_bulk" />';
        \wp_nonce_field( 'kitgenix_affiliate_link_bulk', 'kitgenix_affiliate_link_bulk_nonce' );

        echo '<input type="hidden" name="return_orderby" value="' . \esc_attr( $orderby ) . '" />'
            . '<input type="hidden" name="return_order" value="' . \esc_attr( $order ) . '" />';
        if ( $search !== '' ) {
            echo '<input type="hidden" name="return_s" value="' . \esc_attr( $search ) . '" />';
        }
        if ( $group_filter !== '' ) {
            echo '<input type="hidden" name="return_group" value="' . \esc_attr( $group_filter ) . '" />';
        }
        if ( $tag_filter !== '' ) {
            echo '<input type="hidden" name="return_tag" value="' . \esc_attr( $tag_filter ) . '" />';
        }
        if ( $paged > 1 ) {
            echo '<input type="hidden" name="return_paged" value="' . \esc_attr( (string) $paged ) . '" />';
        }

        echo '<div class="tablenav top">'
            . '<div class="alignleft actions bulkactions">'
            . '<label class="screen-reader-text" for="kitgenix_bulk_action">' . \esc_html__( 'Select bulk action', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<select name="bulk_action" id="kitgenix_bulk_action">'
            . '<option value="">' . \esc_html__( 'Bulk actions', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="delete">' . \esc_html__( 'Delete', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="reset_clicks">' . \esc_html__( 'Reset clicks', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="export_csv">' . \esc_html__( 'Export CSV', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select> '
            . '<button type="submit" class="button">' . \esc_html__( 'Apply', 'kitgenix-affiliate-link-manager' ) . '</button>'
            . '</div>'
            . '</div>';

        echo '<div class="kitgenix-table-wrap">'
            . '<table class="kitgenix-table kitgenix-links-table" id="kitgenix-affiliate-links-table">'
            . '<thead><tr>'
            . '<th class="kitgenix-col-select"><input type="checkbox" id="kitgenix-select-all-links" aria-label="' . \esc_attr__( 'Select all links', 'kitgenix-affiliate-link-manager' ) . '" /></th>'
            . '<th class="kitgenix-col-name" aria-sort="' . \esc_attr( $aria_sort( 'title' ) ) . '"><a href="' . \esc_url( $build_sort_url( 'title' ) ) . '">' . \esc_html__( 'Name', 'kitgenix-affiliate-link-manager' ) . '</a></th>'
            . '<th class="kitgenix-col-destination">' . \esc_html__( 'Destination', 'kitgenix-affiliate-link-manager' ) . '</th>'
            . '<th class="kitgenix-col-short">' . \esc_html__( 'Short URL', 'kitgenix-affiliate-link-manager' ) . '</th>'
            . '<th class="kitgenix-col-clicks" aria-sort="' . \esc_attr( $aria_sort( 'clicks' ) ) . '"><a href="' . \esc_url( $build_sort_url( 'clicks' ) ) . '">' . \esc_html__( 'Clicks', 'kitgenix-affiliate-link-manager' ) . '</a></th>'
            . '<th class="kitgenix-col-actions">' . \esc_html__( 'Actions', 'kitgenix-affiliate-link-manager' ) . '</th>'
            . '</tr></thead><tbody>';

        foreach ( $links as $link ) {
            if ( ! ( $link instanceof \WP_Post ) ) {
                continue;
            }

            $link_id         = (int) $link->ID;
            $row_title       = (string) $link->post_title;
            $row_slug        = (string) $link->post_name;
            $row_dest        = Affiliate_Link_Post_Type::get_destination_url( $link_id );
            $rel_value       = Affiliate_Link_Post_Type::get_rel_value( $link_id );
            $row_enabled     = Affiliate_Link_Post_Type::is_enabled( $link_id );
            $row_group_name  = Affiliate_Link_Post_Type::get_primary_group_name( $link_id );
            $row_tag_names   = Affiliate_Link_Post_Type::get_tag_names( $link_id );
            $row_note        = Affiliate_Link_Post_Type::get_campaign_note( $link_id );
            $row_note_short  = $row_note !== '' ? \wp_html_excerpt( $row_note, 140, '...' ) : '';
            $row_starts_at   = Affiliate_Link_Post_Type::format_timestamp_for_input( Affiliate_Link_Post_Type::get_starts_at_timestamp( $link_id ) );
            $row_expires_at  = Affiliate_Link_Post_Type::format_timestamp_for_input( Affiliate_Link_Post_Type::get_expires_at_timestamp( $link_id ) );
            $row_fallback_url = Affiliate_Link_Post_Type::get_fallback_url( $link_id );
            $row_rotation_mode = Affiliate_Link_Post_Type::get_rotation_mode( $link_id );
            $row_rotation_destinations = Affiliate_Link_Post_Type::get_rotation_destinations_text( $link_id );
            $row_redirect_state = Affiliate_Link_Post_Type::get_redirect_state( $link_id );
            $row_rules_summary = self::get_destination_rules_summary( $link_id );
            $row_health_status = Health_Checks::get_health_status( $link_id );
            $row_health_message = Health_Checks::get_health_message( $link_id );
            $row_health_checked_at = Health_Checks::get_health_checked_at_timestamp( $link_id );
            $row_health_checked_at_label = $row_health_checked_at > 0 ? self::format_schedule_timestamp( $row_health_checked_at ) : '';
            $short           = Settings::build_short_url( $row_slug );
            $clicks          = (int) \get_post_meta( $link_id, Affiliate_Link_Post_Type::META_CLICKS, true );

            $edit_args = [
                'page'            => 'kitgenix-affiliate-link-manager',
                'tab'             => 'links',
                'kitgenix_action' => 'edit',
                'link_id'         => $link_id,
                'orderby'         => $orderby,
                'order'           => $order,
            ];
            if ( $search !== '' ) {
                $edit_args['s'] = $search;
            }
            if ( $group_filter !== '' ) {
                $edit_args['campaign_group'] = $group_filter;
            }
            if ( $tag_filter !== '' ) {
                $edit_args['campaign_tag'] = $tag_filter;
            }
            if ( $paged > 1 ) {
                $edit_args['paged'] = $paged;
            }

            $edit_url = \add_query_arg( $edit_args, \admin_url( 'admin.php' ) ) . '#kitgenix-tab-links';

            $delete_url = \add_query_arg(
                array_merge(
                    [
                        'action'  => 'kitgenix_affiliate_link_delete',
                        'link_id' => $link_id,
                    ],
                    $action_return_args
                ),
                \admin_url( 'admin-post.php' )
            );
            $delete_url = \wp_nonce_url( $delete_url, 'kitgenix_affiliate_link_delete', 'nonce' );

            $duplicate_url = \add_query_arg(
                array_merge(
                    [
                        'action'  => 'kitgenix_affiliate_link_duplicate',
                        'link_id' => $link_id,
                    ],
                    $action_return_args
                ),
                \admin_url( 'admin-post.php' )
            );
            $duplicate_url = \wp_nonce_url( $duplicate_url, 'kitgenix_affiliate_link_duplicate', 'nonce' );

            $reset_url = \add_query_arg(
                array_merge(
                    [
                        'action'  => 'kitgenix_affiliate_link_reset_clicks',
                        'link_id' => $link_id,
                    ],
                    $action_return_args
                ),
                \admin_url( 'admin-post.php' )
            );
            $reset_url = \wp_nonce_url( $reset_url, 'kitgenix_affiliate_link_reset_clicks', 'nonce' );

            $meta_bits = [];
            if ( $row_group_name !== '' ) {
                $meta_bits[] = '<span class="kitgenix-affiliate-pill kitgenix-affiliate-pill--group">' . \esc_html( $row_group_name ) . '</span>';
            }
            foreach ( $row_tag_names as $row_tag_name ) {
                $meta_bits[] = '<span class="kitgenix-affiliate-pill kitgenix-affiliate-pill--tag">' . \esc_html( $row_tag_name ) . '</span>';
            }
            if ( $row_redirect_state === 'scheduled' ) {
                $meta_bits[] = '<span class="kitgenix-affiliate-pill">' . \esc_html__( 'Scheduled', 'kitgenix-affiliate-link-manager' ) . '</span>';
            } elseif ( $row_redirect_state === 'expired' ) {
                $meta_bits[] = '<span class="kitgenix-affiliate-pill">' . \esc_html__( 'Expired', 'kitgenix-affiliate-link-manager' ) . '</span>';
            }
            if ( $row_health_status === 'critical' ) {
                $meta_bits[] = '<span class="kitgenix-affiliate-pill">' . \esc_html__( 'Health Alert', 'kitgenix-affiliate-link-manager' ) . '</span>';
            } elseif ( $row_health_status === 'warning' ) {
                $meta_bits[] = '<span class="kitgenix-affiliate-pill">' . \esc_html__( 'Health Warning', 'kitgenix-affiliate-link-manager' ) . '</span>';
            }

            $row_health_note = '';
            if ( $row_health_status === 'critical' || $row_health_status === 'warning' ) {
                $row_health_note = $row_health_message;
                if ( $row_health_checked_at_label !== '' ) {
                    $row_health_note .= ' ' . sprintf(
                        /* translators: %s: local date and time of the latest destination health check. */
                        __( 'Last checked %s.', 'kitgenix-affiliate-link-manager' ),
                        $row_health_checked_at_label
                    );
                }
            } elseif ( $row_health_status === 'unknown' ) {
                $row_health_note = __( 'Health check pending.', 'kitgenix-affiliate-link-manager' );
            }

            echo '<tr>'
                . '<td class="kitgenix-col-select"><input type="checkbox" name="link_ids[]" value="' . \esc_attr( (string) $link_id ) . '" /></td>'
                . '<td class="kitgenix-col-name"><strong>' . \esc_html( $row_title ) . '</strong>'
                . ( $row_enabled ? '' : ' <span class="kitgenix-muted">' . \esc_html__( '(Disabled)', 'kitgenix-affiliate-link-manager' ) . '</span>' )
                . ( ! empty( $meta_bits ) ? '<div class="kitgenix-affiliate-meta">' . \wp_kses_post( \implode( '', $meta_bits ) ) . '</div>' : '' )
                . ( $row_rules_summary !== '' ? '<div class="kitgenix-muted">' . \esc_html( $row_rules_summary ) . '</div>' : '' )
                . ( $row_health_note !== '' ? '<div class="kitgenix-muted">' . \esc_html( $row_health_note ) . '</div>' : '' )
                . ( $row_note_short !== '' ? '<div class="kitgenix-affiliate-note">' . \esc_html( $row_note_short ) . '</div>' : '' )
                . '</td>'
                . '<td class="kitgenix-col-destination"><a href="' . \esc_url( $row_dest ) . '" target="_blank" rel="noopener noreferrer ' . \esc_attr( $rel_value ) . '">' . \esc_html( $row_dest ) . '</a></td>'
                . '<td class="kitgenix-col-short"><span class="kitgenix-code">' . \esc_html( $short ) . '</span> '
                . '<a href="#" class="button button-small" data-default-label="' . \esc_attr__( 'Copy', 'kitgenix-affiliate-link-manager' ) . '" data-copied-label="' . \esc_attr__( 'Copied', 'kitgenix-affiliate-link-manager' ) . '" data-kitgenix-copy="' . \esc_attr( $short ) . '">' . \esc_html__( 'Copy', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '</td>'
                . '<td class="kitgenix-col-clicks">' . \esc_html( (string) $clicks ) . '</td>'
                . '<td class="kitgenix-actions kitgenix-col-actions">'
                . '<a class="kitgenix-affiliate-edit" href="' . \esc_url( $edit_url ) . '"'
                . ' data-kitgenix-edit="1"'
                . ' data-link-id="' . \esc_attr( (string) $link_id ) . '"'
                . ' data-title="' . \esc_attr( $row_title ) . '"'
                . ' data-slug="' . \esc_attr( $row_slug ) . '"'
                . ' data-destination="' . \esc_attr( $row_dest ) . '"'
                . ' data-group-name="' . \esc_attr( $row_group_name ) . '"'
                . ' data-tags="' . \esc_attr( \implode( ', ', $row_tag_names ) ) . '"'
                . ' data-campaign-note="' . \esc_attr( $row_note ) . '"'
                . ' data-starts-at="' . \esc_attr( $row_starts_at ) . '"'
                . ' data-expires-at="' . \esc_attr( $row_expires_at ) . '"'
                . ' data-fallback-url="' . \esc_attr( $row_fallback_url ) . '"'
                . ' data-rotation-mode="' . \esc_attr( $row_rotation_mode ) . '"'
                . ' data-rotation-destinations="' . \esc_attr( $row_rotation_destinations ) . '"'
                . ' data-rel="' . \esc_attr( $rel_value ) . '"'
                . ' data-enabled="' . \esc_attr( $row_enabled ? '1' : '0' ) . '"'
                . ' data-delete-url="' . \esc_attr( $delete_url ) . '"'
                . '>' . \esc_html__( 'Edit', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '<a href="' . \esc_url( $duplicate_url ) . '">' . \esc_html__( 'Duplicate', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '<a href="' . \esc_url( $reset_url ) . '" onclick="return confirm(\'' . \esc_js( \__( 'Reset click count to 0?', 'kitgenix-affiliate-link-manager' ) ) . '\');">' . \esc_html__( 'Reset clicks', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '<a href="' . \esc_url( $delete_url ) . '" onclick="return confirm(\'' . \esc_js( \__( 'Move this link to the Trash?', 'kitgenix-affiliate-link-manager' ) ) . '\');">' . \esc_html__( 'Delete', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '</td>'
                . '</tr>';
        }

        echo '</tbody></table></div>';

        $total_pages = isset( $links_query ) ? (int) $links_query->max_num_pages : 0;
        if ( $total_pages > 1 ) {
            $paging_args = [
                'page'    => 'kitgenix-affiliate-link-manager',
                'tab'     => 'links',
                'paged'   => '%#%',
                'orderby' => $orderby,
                'order'   => $order,
            ];
            if ( $search !== '' ) {
                $paging_args['s'] = $search;
            }
            if ( $group_filter !== '' ) {
                $paging_args['campaign_group'] = $group_filter;
            }
            if ( $tag_filter !== '' ) {
                $paging_args['campaign_tag'] = $tag_filter;
            }

            $base_url = \add_query_arg( $paging_args, \admin_url( 'admin.php' ) ) . '#kitgenix-tab-links';

            $pagination_links = \paginate_links(
                [
                    'base'      => $base_url,
                    'format'    => '',
                    'current'   => $paged,
                    'total'     => $total_pages,
                    'prev_text' => \__( '&laquo;', 'kitgenix-affiliate-link-manager' ),
                    'next_text' => \__( '&raquo;', 'kitgenix-affiliate-link-manager' ),
                ]
            );

            if ( is_string( $pagination_links ) && '' !== $pagination_links ) {
                echo '<div class="tablenav"><div class="tablenav-pages">'
                    . \wp_kses_post( $pagination_links )
                    . '</div></div>';
            }
        }

        echo '</form>';

        echo '<div class="kitgenix-modal" id="kitgenix-affiliate-edit-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="kitgenix-affiliate-modal-title" aria-describedby="kitgenix-affiliate-modal-desc">'
            . '<div class="kitgenix-modal__backdrop" data-kitgenix-modal-close="1"></div>'
            . '<div class="kitgenix-modal__dialog" role="document">'
            . '<div class="kitgenix-modal__header">'
            . '<h2 class="kitgenix-modal__title" id="kitgenix-affiliate-modal-title">' . \esc_html__( 'Edit Link', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<button type="button" class="button kitgenix-modal__close" data-kitgenix-modal-close="1">' . \esc_html__( 'Close', 'kitgenix-affiliate-link-manager' ) . '</button>'
            . '</div>'
            . '<div class="kitgenix-modal__body">'
            . '<p id="kitgenix-affiliate-modal-desc" class="screen-reader-text">' . \esc_html__( 'Edit affiliate link details.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" id="kitgenix-affiliate-edit-form">'
            . '<input type="hidden" name="action" value="kitgenix_affiliate_link_save" />'
            . '<input type="hidden" name="post_id" value="" />';

        \wp_nonce_field( 'kitgenix_affiliate_link_save', 'kitgenix_affiliate_link_nonce' );

        echo '<input type="hidden" name="return_orderby" value="' . \esc_attr( $orderby ) . '" />';
        echo '<input type="hidden" name="return_order" value="' . \esc_attr( $order ) . '" />';
        if ( $search !== '' ) {
            echo '<input type="hidden" name="return_s" value="' . \esc_attr( $search ) . '" />';
        }
        if ( $group_filter !== '' ) {
            echo '<input type="hidden" name="return_group" value="' . \esc_attr( $group_filter ) . '" />';
        }
        if ( $tag_filter !== '' ) {
            echo '<input type="hidden" name="return_tag" value="' . \esc_attr( $tag_filter ) . '" />';
        }
        if ( $paged > 1 ) {
            echo '<input type="hidden" name="return_paged" value="' . \esc_attr( (string) $paged ) . '" />';
        }

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_title">' . \esc_html__( 'Name', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="text" id="kitgenix_affiliate_modal_title" name="title" value="" class="regular-text" required />'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_slug">' . \esc_html__( 'Slug', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="text" id="kitgenix_affiliate_modal_slug" name="slug" value="" class="regular-text" placeholder="' . \esc_attr__( 'Auto-generated from name', 'kitgenix-affiliate-link-manager' ) . '" />'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Leave blank to auto-generate. You can change it later.', 'kitgenix-affiliate-link-manager' ) . '<br />'
            . \esc_html( \home_url( '/' . $prefix . '/' ) ) . '<span class="kitgenix-code">{slug}</span></div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_destination">' . \esc_html__( 'Destination URL', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="url" id="kitgenix_affiliate_modal_destination" name="destination_url" value="" class="regular-text" required />'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_starts_at">' . \esc_html__( 'Start date and time', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="datetime-local" id="kitgenix_affiliate_modal_starts_at" name="starts_at" value="" class="regular-text" />'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_expires_at">' . \esc_html__( 'Expiry date and time', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="datetime-local" id="kitgenix_affiliate_modal_expires_at" name="expires_at" value="" class="regular-text" />'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_fallback_url">' . \esc_html__( 'Fallback URL', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="url" id="kitgenix_affiliate_modal_fallback_url" name="fallback_url" value="" class="regular-text" />'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_rotation_mode">' . \esc_html__( 'Rotation mode', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<select id="kitgenix_affiliate_modal_rotation_mode" name="rotation_mode">'
            . '<option value="none">' . \esc_html__( 'No rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="sequential">' . \esc_html__( 'Sequential rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="random">' . \esc_html__( 'Random rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_rotation_destinations">' . \esc_html__( 'Additional rotation destinations', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<textarea id="kitgenix_affiliate_modal_rotation_destinations" name="rotation_destinations" rows="4" class="large-text"></textarea>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_group_name">' . \esc_html__( 'Link group', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="text" id="kitgenix_affiliate_modal_group_name" name="group_name" value="" class="regular-text" list="kitgenix-affiliate-group-options" placeholder="' . \esc_attr__( 'Example: Summer Campaigns', 'kitgenix-affiliate-link-manager' ) . '" />'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_tags">' . \esc_html__( 'Tags', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="text" id="kitgenix_affiliate_modal_tags" name="tag_names" value="" class="regular-text" placeholder="' . \esc_attr__( 'Example: email, q3, review-post', 'kitgenix-affiliate-link-manager' ) . '" />'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_campaign_note">' . \esc_html__( 'Campaign note', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<textarea id="kitgenix_affiliate_modal_campaign_note" name="campaign_note" rows="4" class="large-text"></textarea>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_rel">' . \esc_html__( 'Link rel', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<select id="kitgenix_affiliate_modal_rel" name="rel">'
            . '<option value="nofollow">' . \esc_html__( 'nofollow', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="sponsored">' . \esc_html__( 'sponsored', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="nofollow sponsored">' . \esc_html__( 'nofollow sponsored', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select>'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Use sponsored/nofollow for affiliate links to follow search engine guidelines.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_modal_enabled">' . \esc_html__( 'Enabled', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<label>'
            . '<input type="checkbox" id="kitgenix_affiliate_modal_enabled" name="enabled" value="1" checked="checked" /> '
            . \esc_html__( 'This link is active and will redirect', 'kitgenix-affiliate-link-manager' )
            . '</label>'
            . '</div>';

        echo '<div class="kitgenix-modal__actions">';
        echo '<a href="#" class="button kitgenix-modal__cancel" data-kitgenix-modal-close="1">' . \esc_html__( 'Cancel', 'kitgenix-affiliate-link-manager' ) . '</a>';
        echo ' <a href="#" class="button button-link-delete kitgenix-modal__delete" id="kitgenix-affiliate-modal-delete">' . \esc_html__( 'Delete', 'kitgenix-affiliate-link-manager' ) . '</a>';
        echo ' ';
        \submit_button( \__( 'Save Changes', 'kitgenix-affiliate-link-manager' ), 'primary', 'submit', false );
        echo '</div>';

        echo '</form>'
            . '</div>'
            . '</div>'
            . '</div>';

        echo '</div></div>';
    }

    private static function render_settings_tab( array $settings, string $prefix ): void {
        $status = isset( $settings['redirect_status'] ) ? (int) $settings['redirect_status'] : 307;
        if ( ! \in_array( $status, [ 301, 302, 307 ], true ) ) {
            $status = 307;
        }

        $links_per_page = isset( $settings['links_per_page'] ) ? (int) $settings['links_per_page'] : 50;
        if ( $links_per_page < 10 ) {
            $links_per_page = 10;
        }
        if ( $links_per_page > 200 ) {
            $links_per_page = 200;
        }

        $delete_on_uninstall = ! empty( $settings['delete_data_on_uninstall'] );

        echo '<div class="kitgenix-card">';
        echo '<form method="post" action="options.php">';

        \settings_fields( 'kitgenix_affiliate_link_manager_settings_group' );
        \wp_nonce_field( 'kitgenix_affiliate_link_manager_settings_save', 'kitgenix_affiliate_link_manager_settings_nonce' );

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_prefix">' . \esc_html__( 'Link prefix', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="text" id="kitgenix_affiliate_prefix" name="' . \esc_attr( Settings::OPTION_NAME ) . '[prefix]" value="' . \esc_attr( $prefix ) . '" class="regular-text" />'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Example: go → https://example.com/go/my-link', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_redirect_status">' . \esc_html__( 'Redirect type', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<select id="kitgenix_affiliate_redirect_status" name="' . \esc_attr( Settings::OPTION_NAME ) . '[redirect_status]">'
            . '<option value="307"' . \selected( $status, 307, false ) . '>' . \esc_html__( '307 (Temporary – recommended)', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="302"' . \selected( $status, 302, false ) . '>' . \esc_html__( '302 (Temporary)', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="301"' . \selected( $status, 301, false ) . '>' . \esc_html__( '301 (Permanent)', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_links_per_page">' . \esc_html__( 'Links per page', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="number" id="kitgenix_affiliate_links_per_page" name="' . \esc_attr( Settings::OPTION_NAME ) . '[links_per_page]" value="' . \esc_attr( (string) $links_per_page ) . '" min="10" max="200" step="1" class="small-text" />'
            . '<div class="kitgenix-muted">' . \esc_html__( 'Controls how many links are shown per page in the Links tab (10–200).', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        echo '<div class="kitgenix-field">'
            . '<label for="kitgenix_affiliate_delete_data_on_uninstall">' . \esc_html__( 'Data removal', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<label>'
            . '<input type="checkbox" id="kitgenix_affiliate_delete_data_on_uninstall" name="' . \esc_attr( Settings::OPTION_NAME ) . '[delete_data_on_uninstall]" value="1"' . ( $delete_on_uninstall ? ' checked="checked"' : '' ) . ' /> '
            . \esc_html__( 'Delete all affiliate links and click data when the plugin is uninstalled', 'kitgenix-affiliate-link-manager' )
            . '</label>'
            . '<div class="kitgenix-muted">' . \esc_html__( 'By default, uninstall keeps your links/click data. Enable this only if you want a clean uninstall.', 'kitgenix-affiliate-link-manager' ) . '</div>'
            . '</div>';

        \submit_button( \__( 'Save Settings', 'kitgenix-affiliate-link-manager' ) );

        echo '</form>';
        echo '</div>';
    }

    private static function render_support_tab( array $settings, string $prefix ): void {
        $status = isset( $settings['redirect_status'] ) ? (int) $settings['redirect_status'] : 307;
        if ( ! \in_array( $status, [ 301, 302, 307 ], true ) ) {
            $status = 307;
        }

        $link_count = 0;
        $counts     = \wp_count_posts( Affiliate_Link_Post_Type::POST_TYPE );
        if ( is_object( $counts ) && isset( $counts->publish ) ) {
            $link_count = (int) $counts->publish;
        }

        $total_clicks = Affiliate_Link_Post_Type::get_total_clicks();

        $donate_once_url      = 'https://buymeacoffee.com/kitgenix';
        $monthly_support_url  = 'https://buymeacoffee.com/kitgenix/membership';
        $plugin_page_url      = 'https://kitgenix.com/plugins/kitgenix-affiliate-link-manager/';
        $review_url           = 'https://wordpress.org/support/plugin/kitgenix-affiliate-link-manager/reviews/#new-post';
        $support_request_url  = 'https://kitgenix.com/plugins/kitgenix-affiliate-link-manager/support';
        $copy_onclick         = "if(window.navigator&&navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(" . \wp_json_encode( $plugin_page_url ) . ");}else{window.prompt(" . \wp_json_encode( \__( 'Copy plugin link:', 'kitgenix-affiliate-link-manager' ) ) . ", " . \wp_json_encode( $plugin_page_url ) . ");}return false;";
        $monthly_options      = [
            [ 'label' => \__( 'Kitgenix Supporter (£4/month)', 'kitgenix-affiliate-link-manager' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
            [ 'label' => \__( 'Kitgenix Plus (£8/month)', 'kitgenix-affiliate-link-manager' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
            [ 'label' => \__( 'Kitgenix Pro Supporter (£19/month)', 'kitgenix-affiliate-link-manager' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
            [ 'label' => \__( 'Kitgenix Agency (£37/month)', 'kitgenix-affiliate-link-manager' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
            [ 'label' => \__( 'Kitgenix Partner (£75/month)', 'kitgenix-affiliate-link-manager' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
            [ 'label' => \__( 'Kitgenix YouTube Sponsor (£730/month)', 'kitgenix-affiliate-link-manager' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
        ];
        $impact_cards = [
            [
                'label' => \__( 'Affiliate links managed', 'kitgenix-affiliate-link-manager' ),
                'value' => \number_format_i18n( $link_count ),
                'meta'  => \__( 'Published short links currently active on your site.', 'kitgenix-affiliate-link-manager' ),
            ],
            [
                'label' => \__( 'Tracked redirects', 'kitgenix-affiliate-link-manager' ),
                'value' => \number_format_i18n( $total_clicks ),
                'meta'  => \__( 'Recorded clicks across your managed affiliate links.', 'kitgenix-affiliate-link-manager' ),
            ],
            [
                'label' => \__( 'Redirect type', 'kitgenix-affiliate-link-manager' ),
                'value' => (string) $status,
                'meta'  => \__( 'The response code currently handling outbound traffic.', 'kitgenix-affiliate-link-manager' ),
            ],
        ];
        $meaning_points = [
            \__( 'You already have a measurable number of short affiliate links under management from one place.', 'kitgenix-affiliate-link-manager' ),
            \__( 'Tracked redirects show how often visitors are actually using those links.', 'kitgenix-affiliate-link-manager' ),
            \__( 'Your redirect status controls how outbound affiliate traffic is handled for browsers and search engines.', 'kitgenix-affiliate-link-manager' ),
        ];
        $support_points = [
            \__( 'Compatibility updates for new WordPress / WooCommerce releases', 'kitgenix-affiliate-link-manager' ),
            \__( 'Bug fixes, edge-case testing, and better affiliate-link coverage', 'kitgenix-affiliate-link-manager' ),
            \__( 'Security hardening and ongoing performance improvements', 'kitgenix-affiliate-link-manager' ),
            \__( 'Documentation upgrades and faster, clearer support responses', 'kitgenix-affiliate-link-manager' ),
        ];
        $trust_points = [
            \__( 'No paid features locked behind donations', 'kitgenix-affiliate-link-manager' ),
            \__( 'No tracking or invasive upsells', 'kitgenix-affiliate-link-manager' ),
            \__( 'Support is always optional, and genuinely appreciated.', 'kitgenix-affiliate-link-manager' ),
        ];
        ?>
        <div class="kitgenix-card kitgenix-affiliate-link-manager-support-page kitgenix-support-page">
            <div class="kitgenix-support-shell">
                <section class="kitgenix-support-hero">
                    <div class="kitgenix-support-hero__copy">
                        <span class="kitgenix-support-eyebrow"><?php echo \esc_html__( 'Help keep Kitgenix independent', 'kitgenix-affiliate-link-manager' ); ?></span>
                        <h2 class="kitgenix-support-heading"><?php echo \esc_html__( 'Support Kitgenix', 'kitgenix-affiliate-link-manager' ); ?></h2>
                        <p class="description kitgenix-support-intro"><?php echo \esc_html__( 'We try to keep Kitgenix plugins lightweight, privacy-friendly, and free for everyone. If Affiliate Link Manager saves you admin time or helps prevent messy raw affiliate URLs across your site, please consider supporting Kitgenix. Your support directly funds ongoing development, testing, and maintenance so we can keep features open and updates frequent.', 'kitgenix-affiliate-link-manager' ); ?></p>
                    </div>
                    <div class="kitgenix-support-hero__aside">
                        <p class="kitgenix-support-kicker"><?php echo \esc_html__( 'Support this plugin', 'kitgenix-affiliate-link-manager' ); ?></p>
                        <div class="kitgenix-support-actions">
                            <a href="<?php echo \esc_url( $donate_once_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary"><?php echo \esc_html__( 'Buy Me a Coffee', 'kitgenix-affiliate-link-manager' ); ?></a>
                            <a href="<?php echo \esc_url( $monthly_support_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary"><?php echo \esc_html__( 'Become a member', 'kitgenix-affiliate-link-manager' ); ?></a>
                        </div>
                        <p class="kitgenix-support-note"><?php echo \esc_html__( 'Via Buy Me a Coffee. Cancel anytime.', 'kitgenix-affiliate-link-manager' ); ?></p>
                    </div>
                </section>

                <section class="kitgenix-support-section kitgenix-support-section--feature">
                    <div class="kitgenix-support-section__header">
                        <h3 class="kitgenix-support-subheading"><?php echo \esc_html__( 'Your site impact', 'kitgenix-affiliate-link-manager' ); ?></h3>
                        <p class="description"><?php echo \esc_html__( 'These stats show how Affiliate Link Manager is currently working on your site:', 'kitgenix-affiliate-link-manager' ); ?></p>
                    </div>
                    <div class="kitgenix-support-metric-grid">
                        <?php foreach ( $impact_cards as $impact_card ) : ?>
                            <div class="kitgenix-support-stat">
                                <span class="kitgenix-support-stat__label"><?php echo \esc_html( $impact_card['label'] ); ?></span>
                                <strong class="kitgenix-support-stat__value"><?php echo \esc_html( $impact_card['value'] ); ?></strong>
                                <span class="kitgenix-support-stat__meta"><?php echo \esc_html( $impact_card['meta'] ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <div class="kitgenix-support-grid">
                    <section class="kitgenix-support-section">
                        <h3 class="kitgenix-support-subheading"><?php echo \esc_html__( 'Support options', 'kitgenix-affiliate-link-manager' ); ?></h3>
                        <p class="description"><?php echo \esc_html__( 'Buy Me a Coffee: A quick way to say thanks and help fund the next round of improvements.', 'kitgenix-affiliate-link-manager' ); ?></p>
                        <p class="description"><?php echo \esc_html__( 'A membership helps keep development consistent if you rely on Affiliate Link Manager day-to-day.', 'kitgenix-affiliate-link-manager' ); ?></p>
                        <div class="kitgenix-support-chip-list">
                            <?php foreach ( $monthly_options as $monthly_option ) : ?>
                                <?php
                                $monthly_label = (string) $monthly_option['label'];
                                $monthly_name  = $monthly_label;
                                $monthly_price = '';
                                if ( preg_match( '/^(.*)\(([^)]+)\)$/u', $monthly_label, $monthly_parts ) ) {
                                    $monthly_name  = trim( $monthly_parts[1] );
                                    $monthly_price = trim( $monthly_parts[2] );
                                }
                                ?>
                                <a class="kitgenix-support-chip" href="<?php echo \esc_url( $monthly_option['url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                    <span class="kitgenix-support-chip__name"><?php echo \esc_html( $monthly_name ); ?></span>
                                    <?php if ( '' !== $monthly_price ) : ?>
                                        <span class="kitgenix-support-chip__price"><?php echo \esc_html( $monthly_price ); ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="kitgenix-support-section">
                        <h3 class="kitgenix-support-subheading"><?php echo \esc_html__( 'What this means', 'kitgenix-affiliate-link-manager' ); ?></h3>
                        <ul class="kitgenix-support-list">
                            <?php foreach ( $meaning_points as $meaning_point ) : ?>
                                <li><?php echo \esc_html( $meaning_point ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <section class="kitgenix-support-section kitgenix-support-section--soft">
                        <h3 class="kitgenix-support-subheading"><?php echo \esc_html__( 'What your support helps with', 'kitgenix-affiliate-link-manager' ); ?></h3>
                        <ul class="kitgenix-support-list">
                            <?php foreach ( $support_points as $support_point ) : ?>
                                <li><?php echo \esc_html( $support_point ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>

                    <section class="kitgenix-support-section">
                        <h3 class="kitgenix-support-subheading"><?php echo \esc_html__( 'Not in a position to donate?', 'kitgenix-affiliate-link-manager' ); ?></h3>
                        <p class="description"><?php echo \esc_html__( 'No worries - you can still massively help:', 'kitgenix-affiliate-link-manager' ); ?></p>
                        <p class="description"><?php echo \esc_html__( 'Reviews help others discover the plugin and keep the project sustainable. Sharing the plugin with other site owners and sending strong support reports both help the project move faster.', 'kitgenix-affiliate-link-manager' ); ?></p>
                        <div class="kitgenix-support-actions">
                            <a href="<?php echo \esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary"><?php echo \esc_html__( 'Leave a WordPress.org review', 'kitgenix-affiliate-link-manager' ); ?></a>
                            <button type="button" class="button button-secondary" onclick="<?php echo \esc_attr( $copy_onclick ); ?>"><?php echo \esc_html__( 'Copy plugin link', 'kitgenix-affiliate-link-manager' ); ?></button>
                            <a href="<?php echo \esc_url( $support_request_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary"><?php echo \esc_html__( 'Open support / feature request', 'kitgenix-affiliate-link-manager' ); ?></a>
                        </div>
                    </section>

                    <section class="kitgenix-support-section kitgenix-support-section--full">
                        <h3 class="kitgenix-support-subheading"><?php echo \esc_html__( 'A small note on trust & privacy', 'kitgenix-affiliate-link-manager' ); ?></h3>
                        <ul class="kitgenix-support-list">
                            <?php foreach ( $trust_points as $trust_point ) : ?>
                                <li><?php echo \esc_html( $trust_point ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="kitgenix-support-footer-note"><?php echo \esc_html__( 'Thank you for supporting Kitgenix.', 'kitgenix-affiliate-link-manager' ); ?></p>
                    </section>
                </div>
            </div>
        </div>
        <?php
    }

    public static function handle_link_save(): void {
        if ( ! self::user_can_manage_links() ) {
            \wp_die( \esc_html__( 'Forbidden', 'kitgenix-affiliate-link-manager' ) );
        }

        $nonce = isset( $_POST['kitgenix_affiliate_link_nonce'] )
            ? \sanitize_text_field( \wp_unslash( $_POST['kitgenix_affiliate_link_nonce'] ) )
            : '';

        if ( '' === $nonce ) {
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_save' ) ) {
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        $title   = isset( $_POST['title'] ) ? (string) \sanitize_text_field( \wp_unslash( $_POST['title'] ) ) : '';
        $slug    = isset( $_POST['slug'] ) ? (string) \sanitize_title( \wp_unslash( $_POST['slug'] ) ) : '';
        $dest    = isset( $_POST['destination_url'] ) ? (string) \esc_url_raw( \wp_unslash( $_POST['destination_url'] ) ) : '';
        $starts_at = isset( $_POST['starts_at'] ) ? (string) \sanitize_text_field( \wp_unslash( $_POST['starts_at'] ) ) : '';
        $expires_at = isset( $_POST['expires_at'] ) ? (string) \sanitize_text_field( \wp_unslash( $_POST['expires_at'] ) ) : '';
        $fallback_url = isset( $_POST['fallback_url'] ) ? (string) \esc_url_raw( \wp_unslash( $_POST['fallback_url'] ) ) : '';
        $rotation_mode = isset( $_POST['rotation_mode'] ) ? (string) \sanitize_key( \wp_unslash( $_POST['rotation_mode'] ) ) : 'none';
        $rotation_destinations = isset( $_POST['rotation_destinations'] ) ? (string) \sanitize_textarea_field( \wp_unslash( $_POST['rotation_destinations'] ) ) : '';
        $group   = isset( $_POST['group_name'] ) ? (string) \sanitize_text_field( \wp_unslash( $_POST['group_name'] ) ) : '';
        $tags    = isset( $_POST['tag_names'] ) ? (string) \sanitize_text_field( \wp_unslash( $_POST['tag_names'] ) ) : '';
        $campaign_note = isset( $_POST['campaign_note'] ) ? (string) \sanitize_textarea_field( \wp_unslash( $_POST['campaign_note'] ) ) : '';
        $rel     = isset( $_POST['rel'] ) ? (string) \sanitize_text_field( \wp_unslash( $_POST['rel'] ) ) : '';
        $enabled = ! empty( $_POST['enabled'] ) ? 1 : 0;
        $return_state = self::get_links_list_state( $_POST, 'return_' );

        $base_args = [
            'orderby' => $return_state['orderby'],
            'order'   => $return_state['order'],
        ];
        if ( $return_state['s'] !== '' ) {
            $base_args['s'] = $return_state['s'];
        }
        if ( $return_state['campaign_group'] !== '' ) {
            $base_args['campaign_group'] = $return_state['campaign_group'];
        }
        if ( $return_state['campaign_tag'] !== '' ) {
            $base_args['campaign_tag'] = $return_state['campaign_tag'];
        }
        if ( $return_state['paged'] > 1 ) {
            $base_args['paged'] = $return_state['paged'];
        }

        $result = Affiliate_Link_Post_Type::upsert_link(
            [
                'post_id'         => $post_id,
                'title'           => $title,
                'slug'            => $slug,
                'destination_url' => $dest,
                'starts_at'       => $starts_at,
                'expires_at'      => $expires_at,
                'fallback_url'    => $fallback_url,
                'rotation_mode'   => $rotation_mode,
                'rotation_destinations' => $rotation_destinations,
                'group_name'      => $group,
                'tag_names'       => $tags,
                'campaign_note'   => $campaign_note,
                'rel'             => $rel,
                'enabled'         => $enabled,
            ]
        );

        $base_url = self::get_links_tab_url( $base_args );

        if ( $result instanceof \WP_Error ) {
            $msg = $result->get_error_message();
            $url = \add_query_arg( [ 'kitgenix_error' => $msg ], $base_url );
            \wp_safe_redirect( $url );
            exit;
        }

        $url = \add_query_arg( [ 'kitgenix_saved' => 1 ], $base_url );
        \wp_safe_redirect( $url );
        exit;
    }

    public static function handle_link_delete(): void {
        if ( ! self::user_can_manage_links() ) {
            \wp_die( \esc_html__( 'Forbidden', 'kitgenix-affiliate-link-manager' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = isset( $_GET['nonce'] ) ? \sanitize_text_field( \wp_unslash( $_GET['nonce'] ) ) : '';
        if ( '' === $nonce ) {
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_delete' ) ) {
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $link_id = isset( $_GET['link_id'] ) ? (int) $_GET['link_id'] : 0;
        if ( $link_id ) {
            Affiliate_Link_Post_Type::delete_link( $link_id );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $return_state = self::get_links_list_state( $_GET, 'return_' );
        $return_args = [
            'orderby' => $return_state['orderby'],
            'order'   => $return_state['order'],
        ];
        if ( $return_state['s'] !== '' ) {
            $return_args['s'] = $return_state['s'];
        }
        if ( $return_state['campaign_group'] !== '' ) {
            $return_args['campaign_group'] = $return_state['campaign_group'];
        }
        if ( $return_state['campaign_tag'] !== '' ) {
            $return_args['campaign_tag'] = $return_state['campaign_tag'];
        }
        if ( $return_state['paged'] > 1 ) {
            $return_args['paged'] = $return_state['paged'];
        }

        $url = \add_query_arg( [ 'kitgenix_deleted' => 1 ], self::get_links_tab_url( $return_args ) );
        \wp_safe_redirect( $url );
        exit;
    }

    public static function handle_link_duplicate(): void {
        if ( ! self::user_can_manage_links() ) {
            \wp_die( \esc_html__( 'Forbidden', 'kitgenix-affiliate-link-manager' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = isset( $_GET['nonce'] ) ? \sanitize_text_field( \wp_unslash( $_GET['nonce'] ) ) : '';
        if ( '' === $nonce ) {
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_duplicate' ) ) {
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $link_id = isset( $_GET['link_id'] ) ? (int) $_GET['link_id'] : 0;
        if ( $link_id <= 0 ) {
            \wp_die( \esc_html__( 'Invalid link.', 'kitgenix-affiliate-link-manager' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $return_state = self::get_links_list_state( $_GET, 'return_' );

        $result = Affiliate_Link_Post_Type::duplicate_link( $link_id );
        if ( $result instanceof \WP_Error ) {
            $return_args = [
                'orderby' => $return_state['orderby'],
                'order'   => $return_state['order'],
            ];
            if ( $return_state['s'] !== '' ) {
                $return_args['s'] = $return_state['s'];
            }
            if ( $return_state['campaign_group'] !== '' ) {
                $return_args['campaign_group'] = $return_state['campaign_group'];
            }
            if ( $return_state['campaign_tag'] !== '' ) {
                $return_args['campaign_tag'] = $return_state['campaign_tag'];
            }
            if ( $return_state['paged'] > 1 ) {
                $return_args['paged'] = $return_state['paged'];
            }

            $url = \add_query_arg( [ 'kitgenix_error' => $result->get_error_message() ], self::get_links_tab_url( $return_args ) );
            \wp_safe_redirect( $url );
            exit;
        }

        $edit_args = [
            'page'                => 'kitgenix-affiliate-link-manager',
            'tab'                 => 'links',
            'kitgenix_action'     => 'edit',
            'link_id'             => (int) $result,
            'kitgenix_duplicated' => 1,
            'orderby'             => $return_state['orderby'],
            'order'               => $return_state['order'],
        ];
        if ( $return_state['s'] !== '' ) {
            $edit_args['s'] = $return_state['s'];
        }
        if ( $return_state['campaign_group'] !== '' ) {
            $edit_args['campaign_group'] = $return_state['campaign_group'];
        }
        if ( $return_state['campaign_tag'] !== '' ) {
            $edit_args['campaign_tag'] = $return_state['campaign_tag'];
        }
        if ( $return_state['paged'] > 1 ) {
            $edit_args['paged'] = $return_state['paged'];
        }

        $url = \add_query_arg( $edit_args, \admin_url( 'admin.php' ) ) . '#kitgenix-tab-links';
        \wp_safe_redirect( $url );
        exit;
    }

    public static function handle_link_reset_clicks(): void {
        if ( ! self::user_can_manage_links() ) {
            \wp_die( \esc_html__( 'Forbidden', 'kitgenix-affiliate-link-manager' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = isset( $_GET['nonce'] ) ? \sanitize_text_field( \wp_unslash( $_GET['nonce'] ) ) : '';
        if ( '' === $nonce ) {
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_reset_clicks' ) ) {
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $link_id = isset( $_GET['link_id'] ) ? (int) $_GET['link_id'] : 0;
        if ( $link_id > 0 ) {
            Affiliate_Link_Post_Type::reset_clicks( $link_id );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $return_state = self::get_links_list_state( $_GET, 'return_' );
        $return_args = [
            'orderby' => $return_state['orderby'],
            'order'   => $return_state['order'],
        ];
        if ( $return_state['s'] !== '' ) {
            $return_args['s'] = $return_state['s'];
        }
        if ( $return_state['campaign_group'] !== '' ) {
            $return_args['campaign_group'] = $return_state['campaign_group'];
        }
        if ( $return_state['campaign_tag'] !== '' ) {
            $return_args['campaign_tag'] = $return_state['campaign_tag'];
        }
        if ( $return_state['paged'] > 1 ) {
            $return_args['paged'] = $return_state['paged'];
        }

        $url = \add_query_arg( [ 'kitgenix_reset' => 1 ], self::get_links_tab_url( $return_args ) );
        \wp_safe_redirect( $url );
        exit;
    }

    public static function handle_link_bulk(): void {
        if ( ! self::user_can_manage_links() ) {
            \wp_die( \esc_html__( 'Forbidden', 'kitgenix-affiliate-link-manager' ) );
        }

        $nonce = isset( $_POST['kitgenix_affiliate_link_bulk_nonce'] )
            ? \sanitize_text_field( \wp_unslash( $_POST['kitgenix_affiliate_link_bulk_nonce'] ) )
            : '';
        if ( '' === $nonce ) {
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_bulk' ) ) {
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }

        $action = isset( $_POST['bulk_action'] ) ? \sanitize_key( (string) \wp_unslash( $_POST['bulk_action'] ) ) : '';
        $ids    = isset( $_POST['link_ids'] ) && is_array( $_POST['link_ids'] )
            ? array_map( 'absint', \wp_unslash( $_POST['link_ids'] ) )
            : [];

        // Return/list state.
        $return_state = self::get_links_list_state( $_POST, 'return_' );
        $base_url = self::get_links_tab_url();
        $return_args = [
            'orderby' => $return_state['orderby'],
            'order'   => $return_state['order'],
        ];
        if ( $return_state['s'] !== '' ) {
            $return_args['s'] = $return_state['s'];
        }
        if ( $return_state['campaign_group'] !== '' ) {
            $return_args['campaign_group'] = $return_state['campaign_group'];
        }
        if ( $return_state['campaign_tag'] !== '' ) {
            $return_args['campaign_tag'] = $return_state['campaign_tag'];
        }
        if ( $return_state['paged'] > 1 ) {
            $return_args['paged'] = $return_state['paged'];
        }

        $link_ids = [];
        foreach ( $ids as $id ) {
            $id = (int) $id;
            if ( $id > 0 ) {
                $link_ids[] = $id;
            }
        }
        $link_ids = array_values( array_unique( $link_ids ) );

        if ( empty( $action ) ) {
            $url      = \add_query_arg( $return_args + [ 'kitgenix_error' => \__( 'Please select a bulk action.', 'kitgenix-affiliate-link-manager' ) ], $base_url );
            $url     .= '#kitgenix-tab-links';
            \wp_safe_redirect( $url );
            exit;
        }

        if ( empty( $link_ids ) ) {
            $url      = \add_query_arg( $return_args + [ 'kitgenix_error' => \__( 'Please select at least one link.', 'kitgenix-affiliate-link-manager' ) ], $base_url );
            $url     .= '#kitgenix-tab-links';
            \wp_safe_redirect( $url );
            exit;
        }

        if ( $action === 'export_csv' ) {
            \nocache_headers();
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=kitgenix-affiliate-links.csv' );

            $headers = Affiliate_Link_Post_Type::get_export_headers();
            self::output_csv_row( array_values( $headers ) );

            foreach ( $link_ids as $id ) {
                $record = Affiliate_Link_Post_Type::get_link_record( (int) $id );
                if ( ! is_array( $record ) ) {
                    continue;
                }

                $row = Affiliate_Link_Post_Type::flatten_link_record_for_export( $record );
                $ordered_row = [];

                foreach ( array_keys( $headers ) as $header_key ) {
                    $ordered_row[] = $row[ $header_key ] ?? '';
                }

                self::output_csv_row( $ordered_row );
            }
            exit;
        }

        $deleted = 0;
        $reset   = 0;

        foreach ( $link_ids as $id ) {
            $post = \get_post( $id );
            if ( ! ( $post instanceof \WP_Post ) || $post->post_type !== Affiliate_Link_Post_Type::POST_TYPE ) {
                continue;
            }

            if ( $action === 'delete' ) {
                if ( Affiliate_Link_Post_Type::delete_link( $id ) ) {
                    $deleted++;
                }
            } elseif ( $action === 'reset_clicks' ) {
                if ( Affiliate_Link_Post_Type::reset_clicks( $id ) ) {
                    $reset++;
                }
            }
        }

        $args     = [];
        if ( $deleted > 0 ) {
            $args['kitgenix_bulk_deleted'] = $deleted;
        }
        if ( $reset > 0 ) {
            $args['kitgenix_bulk_reset'] = $reset;
        }

        if ( empty( $args ) ) {
            $args['kitgenix_error'] = \__( 'No changes were made.', 'kitgenix-affiliate-link-manager' );
        }

        $url  = \add_query_arg( $return_args + $args, $base_url );
        $url .= '#kitgenix-tab-links';
        \wp_safe_redirect( $url );
        exit;
    }

    private static function output_csv_row( array $fields ): void {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV responses are not HTML output.
        echo self::build_csv_row( $fields );
    }

    private static function render_log_tab(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['kitgenix_log_cleared'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . \esc_html__( 'Event log cleared.', 'kitgenix-affiliate-link-manager' ) . '</p></div>';
        }

        $clear_url = \wp_nonce_url(
            \admin_url( 'admin-post.php?action=kitgenix_affiliate_link_manager_clear_event_log' ),
            'kitgenix_affiliate_link_manager_clear_event_log'
        );

        echo '<div class="kitgenix-settings-section">';
        echo '<h2>' . \esc_html__( 'Activity Log', 'kitgenix-affiliate-link-manager' ) . '</h2>';
        echo '<p class="description">' . \esc_html__( 'A record of recent plugin events. Entries show the timestamp, context, outcome, and a plain-English note. IP addresses and sensitive data are never stored here.', 'kitgenix-affiliate-link-manager' ) . '</p>';
        echo '<textarea class="large-text code" rows="20" readonly>' . \esc_textarea( Event_Log::get_log_text() ) . '</textarea>';
        echo '<p>';
        echo '<a href="' . \esc_url( $clear_url ) . '" class="button button-secondary" onclick="return confirm(\'' . \esc_js( \__( 'Clear all log entries?', 'kitgenix-affiliate-link-manager' ) ) . '\')">' . \esc_html__( 'Clear log', 'kitgenix-affiliate-link-manager' ) . '</a>';
        echo '</p>';
        echo '</div>';
    }

    public static function handle_clear_event_log(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( \esc_html__( 'Permission denied.', 'kitgenix-affiliate-link-manager' ), 403 );
        }
        \check_admin_referer( 'kitgenix_affiliate_link_manager_clear_event_log' );
        Event_Log::clear();
        \wp_safe_redirect( \admin_url( 'admin.php?page=kitgenix-affiliate-link-manager&tab=log&kitgenix_log_cleared=1' ) );
        exit;
    }

    public static function on_settings_saved( mixed $old_value, mixed $new_value, string $option ): void {
        Event_Log::record( 'settings-saved', 'success', \__( 'Plugin settings were saved via the admin settings page.', 'kitgenix-affiliate-link-manager' ) );
    }

    private static function build_csv_row( array $fields ): string {
        $escaped_fields = [];

        foreach ( $fields as $field ) {
            $value = str_replace( '"', '""', (string) $field );
            if ( false !== strpbrk( $value, ",\r\n\"" ) ) {
                $value = '"' . $value . '"';
            }
            $escaped_fields[] = $value;
        }

        return implode( ',', $escaped_fields ) . "\r\n";
    }
}
