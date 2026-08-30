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
        \add_action( 'admin_post_kitgenix_affiliate_link_check_health', [ __CLASS__, 'handle_link_check_health' ] );
        \add_action( 'admin_post_kitgenix_affiliate_run_health_checks_now', [ __CLASS__, 'handle_run_health_checks_now' ] );
        \add_action( 'admin_post_kitgenix_affiliate_link_import', [ __CLASS__, 'handle_link_import' ] );
        \add_action( 'admin_post_kitgenix_affiliate_link_manager_clear_event_log', [ __CLASS__, 'handle_clear_event_log' ] );
        \add_action( 'update_option_' . Settings::OPTION_NAME, [ __CLASS__, 'on_settings_saved' ], 10, 3 );
    }

    /**
     * Small inline icon set used inside card heads / action tiles on the
     * Support tab. Output is static trusted markup (no user input), safe
     * to echo unescaped.
     */
    private static function icon( string $name ): string {
        $icons = [
            'heart' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 17s-6.5-4.06-6.5-8.4A3.6 3.6 0 0 1 10 6.2a3.6 3.6 0 0 1 6.5 2.4C16.5 12.94 10 17 10 17Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
            'sync' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 10a6 6 0 0 1-10.6 3.8M4 10a6 6 0 0 1 10.6-3.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M4 13.8V17h3.2M16 6.2V3h-3.2" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
            'tools' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.5 4.5a3 3 0 0 0-3.9 3.9l-6.1 6.1 2 2 6.1-6.1a3 3 0 0 0 3.9-3.9l-2.1 2.1-2-2 2.1-2.1Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
            'check' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 10.5 8 14.5 16 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'logs' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3.5" y="3" width="13" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M6.5 7h7M6.5 10h7M6.5 13h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
            'audit' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 3.5h7l3 3V16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7 9h6M7 12h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
            'chat' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.5 5.5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-3.5 3v-3a2 2 0 0 1-2-2v-6Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
            'star' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 3.2l2.02 4.1 4.53.66-3.28 3.2.78 4.52L10 13.5l-4.05 2.13.78-4.52-3.28-3.2 4.53-.66L10 3.2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>',
            'copy' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="7" y="7" width="9.5" height="9.5" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M13 7V4.5A1.5 1.5 0 0 0 11.5 3h-7A1.5 1.5 0 0 0 3 4.5v7A1.5 1.5 0 0 0 4.5 13H7" stroke="currentColor" stroke-width="1.5"/></svg>',
            'users' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="7" cy="7" r="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M2.5 16c.5-3 2.2-4.5 4.5-4.5s4 1.5 4.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="14" cy="7.5" r="2" stroke="currentColor" stroke-width="1.5"/><path d="M12.5 11.3c1.8.2 3 1.5 3.5 4.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
            'search' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="9" r="5.5" stroke="currentColor" stroke-width="1.5"/><path d="M17 17l-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
            'shield' => '<svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2.5l6 2.2v4.4c0 4.2-2.6 7.4-6 8.4-3.4-1-6-4.2-6-8.4V4.7l6-2.2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.3 10.2l1.9 1.9 3.5-3.9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'paypal' => '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.7 15.5H4.9a.3.3 0 0 1-.3-.35L6.5 3.8a.5.5 0 0 1 .5-.4h4.4c2 0 3.4 1.2 3.1 3.2-.3 2.4-2.1 3.7-4.3 3.7H8.6a.5.5 0 0 0-.5.4l-.7 3.9a.5.5 0 0 1-.5.4Z" fill="currentColor" opacity=".55"/><path d="M9.2 15.5H7.4a.3.3 0 0 1-.3-.35L9 3.8a.5.5 0 0 1 .5-.4h4.4c2 0 3.4 1.2 3.1 3.2-.3 2.4-2.1 3.7-4.3 3.7h-1.6a.5.5 0 0 0-.5.4l-.7 3.9a.5.5 0 0 1-.5.4Z" fill="currentColor"/></svg>',
        ];
        return $icons[ $name ] ?? '';
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

    /**
     * Log a failed nonce check before wp_die(). A stale nonce on a cached admin page is far
     * more common than an actual forgery attempt, so this is recorded as a likely false
     * positive rather than a security block.
     */
    private static function log_nonce_invalid( string $context ): void {
        Event_Log::record(
            $context,
            'error',
            \__( 'A security token for this admin action was missing or did not verify.', 'kitgenix-affiliate-link-manager' ),
            'nonce_invalid'
        );
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

        $stats = [
            [
                'label' => __( 'Last 7 days', 'kitgenix-affiliate-link-manager' ),
                'value' => \number_format_i18n( (int) ( $snapshot['clicks_last_7_days'] ?? 0 ) ),
                'meta'  => __( 'Clicks across all published links', 'kitgenix-affiliate-link-manager' ),
            ],
            [
                'label' => __( 'Last 30 days', 'kitgenix-affiliate-link-manager' ),
                'value' => \number_format_i18n( (int) ( $snapshot['clicks_last_30_days'] ?? 0 ) ),
                'meta'  => __( 'Clicks across all published links', 'kitgenix-affiliate-link-manager' ),
            ],
        ];

        echo '<div class="kitgenix-card">'
            . '<div class="kitgenix-card-head">'
            . '<div class="kitgenix-card-head-main">'
            . '<div class="kitgenix-card-head-text">'
            . '<h3>' . \esc_html__( 'Analytics Snapshot', 'kitgenix-affiliate-link-manager' ) . '</h3>'
            . '<p>' . \esc_html__( 'Daily click trends, top sources, and top referrers across all published affiliate links.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '</div>'
            . '</div>';

        echo '<div class="kitgenix-card-body">';

        echo '<div class="kitgenix-stat-grid">';
        foreach ( $stats as $stat ) {
            echo '<div class="kitgenix-stat-tile">'
                . '<div class="kitgenix-stat-tile-head"><span class="kitgenix-stat-tile-label">' . \esc_html( $stat['label'] ) . '</span></div>'
                . '<div class="kitgenix-stat-tile-value">' . \esc_html( $stat['value'] ) . '</div>'
                . '<div class="kitgenix-muted">' . \esc_html( $stat['meta'] ) . '</div>'
                . '</div>';
        }
        echo '</div>';

        echo '<div class="kitgenix-settings-group">';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label>' . \esc_html__( 'Timeline', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control kitgenix-muted">'
            . \esc_html( self::format_analytics_timeline_summary( isset( $snapshot['timeline_14_days'] ) && is_array( $snapshot['timeline_14_days'] ) ? $snapshot['timeline_14_days'] : [] ) )
            . '</div></div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label>' . \esc_html__( 'Top sources', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control kitgenix-muted">'
            . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_sources_30_days'] ) && is_array( $snapshot['top_sources_30_days'] ) ? $snapshot['top_sources_30_days'] : [], __( 'No source data yet.', 'kitgenix-affiliate-link-manager' ) ) )
            . '</div></div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label>' . \esc_html__( 'Top referrers', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control kitgenix-muted">'
            . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_referrers_30_days'] ) && is_array( $snapshot['top_referrers_30_days'] ) ? $snapshot['top_referrers_30_days'] : [], __( 'No referrer data yet.', 'kitgenix-affiliate-link-manager' ) ) )
            . '</div></div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label>' . \esc_html__( 'Top placements', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control kitgenix-muted">'
            . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_placements_30_days'] ) && is_array( $snapshot['top_placements_30_days'] ) ? $snapshot['top_placements_30_days'] : [], __( 'No placement data yet.', 'kitgenix-affiliate-link-manager' ) ) )
            . '</div></div>';

        echo '</div>'; // .kitgenix-settings-group
        echo '</div>'; // .kitgenix-card-body
        echo '</div>'; // .kitgenix-card
    }

    private static function render_link_analytics_panel( int $post_id ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        $snapshot = Analytics::get_link_snapshot( $post_id );

        echo '<div class="kitgenix-card" data-kitgenix-collapsible>'
            . '<button type="button" class="kitgenix-collapsible-trigger" aria-expanded="true">'
            . '<span class="kitgenix-card-head-main">'
            . '<span class="kitgenix-card-head-text">'
            . '<span class="kitgenix-collapsible-title">' . \esc_html__( 'Link Analytics', 'kitgenix-affiliate-link-manager' ) . '</span>'
            . '<span class="kitgenix-collapsible-desc">'
            . \esc_html(
                sprintf(
                    /* translators: 1: clicks in the last 7 days, 2: clicks in the last 30 days. */
                    __( 'Last 7 days: %1$s clicks | Last 30 days: %2$s clicks', 'kitgenix-affiliate-link-manager' ),
                    \number_format_i18n( (int) ( $snapshot['clicks_last_7_days'] ?? 0 ) ),
                    \number_format_i18n( (int) ( $snapshot['clicks_last_30_days'] ?? 0 ) )
                )
            )
            . '</span>'
            . '</span>'
            . '</span>'
            . '<svg class="kitgenix-collapsible-chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            . '</button>';

        echo '<div class="kitgenix-collapsible-panel"><div class="kitgenix-card-body">';
        echo '<div class="kitgenix-settings-group">';
        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label>' . \esc_html__( 'Timeline', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control kitgenix-muted">' . \esc_html( self::format_analytics_timeline_summary( isset( $snapshot['timeline_14_days'] ) && is_array( $snapshot['timeline_14_days'] ) ? $snapshot['timeline_14_days'] : [] ) ) . '</div></div>';
        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label>' . \esc_html__( 'Top sources', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control kitgenix-muted">' . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_sources_30_days'] ) && is_array( $snapshot['top_sources_30_days'] ) ? $snapshot['top_sources_30_days'] : [], __( 'No source data yet.', 'kitgenix-affiliate-link-manager' ) ) ) . '</div></div>';
        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label>' . \esc_html__( 'Top referrers', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control kitgenix-muted">' . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_referrers_30_days'] ) && is_array( $snapshot['top_referrers_30_days'] ) ? $snapshot['top_referrers_30_days'] : [], __( 'No referrer data yet.', 'kitgenix-affiliate-link-manager' ) ) ) . '</div></div>';
        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label>' . \esc_html__( 'Top placements', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control kitgenix-muted">' . \esc_html( self::format_analytics_breakdown_summary( isset( $snapshot['top_placements_30_days'] ) && is_array( $snapshot['top_placements_30_days'] ) ? $snapshot['top_placements_30_days'] : [], __( 'No placement data yet.', 'kitgenix-affiliate-link-manager' ) ) ) . '</div></div>';
        echo '</div>'; // .kitgenix-settings-group
        echo '</div></div>'; // .kitgenix-card-body, .kitgenix-collapsible-panel
        echo '</div>'; // .kitgenix-card
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

        $ver = defined( 'KITGENIX_AFFILIATE_LINK_MANAGER_VERSION' ) ? (string) KITGENIX_AFFILIATE_LINK_MANAGER_VERSION : '2.0.0';
        $base_dir = defined( 'KITGENIX_AFFILIATE_LINK_MANAGER_DIR' ) ? (string) KITGENIX_AFFILIATE_LINK_MANAGER_DIR : '';
        $admin_css_file = $base_dir ? $base_dir . 'assets/css/admin.css' : '';
        $admin_js_file = $base_dir ? $base_dir . 'assets/js/admin.js' : '';
        $ui_js_file = $base_dir ? $base_dir . 'assets/js/kitgenix-admin-tabs.js' : '';
        $components_js_file = $base_dir ? $base_dir . 'assets/js/kitgenix-admin-components.js' : '';
        $admin_css_ver = ( $admin_css_file && file_exists( $admin_css_file ) ) ? (string) filemtime( $admin_css_file ) : $ver;
        $admin_js_ver = ( $admin_js_file && file_exists( $admin_js_file ) ) ? (string) filemtime( $admin_js_file ) : $ver;
        $ui_js_ver = ( $ui_js_file && file_exists( $ui_js_file ) ) ? (string) filemtime( $ui_js_file ) : $ver;
        $components_js_ver = ( $components_js_file && file_exists( $components_js_file ) ) ? (string) filemtime( $components_js_file ) : $ver;

        \wp_enqueue_style( 'kitgenix-affiliate-link-manager-admin-ui' );

        \wp_register_style( 'kitgenix-affiliate-link-manager-admin', \plugins_url( 'assets/css/admin.css', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ), [ 'kitgenix-affiliate-link-manager-admin-ui' ], $admin_css_ver );
        \wp_enqueue_style( 'kitgenix-affiliate-link-manager-admin' );

        \wp_register_script( 'kitgenix-admin-tabs', \plugins_url( 'assets/js/kitgenix-admin-tabs.js', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ), [], $ui_js_ver, true );
        \wp_enqueue_script( 'kitgenix-admin-tabs' );

        \wp_register_script( 'kitgenix-admin-components', \plugins_url( 'assets/js/kitgenix-admin-components.js', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ), [], $components_js_ver, true );
        \wp_enqueue_script( 'kitgenix-admin-components' );

        \wp_register_script( 'kitgenix-affiliate-link-manager-admin', \plugins_url( 'assets/js/admin.js', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE ), [ 'kitgenix-admin-components' ], $admin_js_ver, true );
        \wp_enqueue_script( 'kitgenix-affiliate-link-manager-admin' );
    }

    private static function notice( string $message, string $type = 'success', bool $dismissible = true ): void {
        $classes = 'kitgenix-notice kitgenix-notice-' . \sanitize_html_class( $type );
        echo '<div class="' . \esc_attr( $classes ) . '" role="status">'
            . '<div class="kitgenix-notice-body"><p class="kitgenix-notice-text">' . \esc_html( $message ) . '</p></div>'
            . ( $dismissible ? '<button type="button" class="kitgenix-notice-close" aria-label="' . \esc_attr__( 'Dismiss', 'kitgenix-affiliate-link-manager' ) . '">&times;</button>' : '' )
            . '</div>';
    }

    private static function render_admin_notices(): void {
        \settings_errors();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $saved = isset( $_GET['kitgenix_saved'] ) ? (int) $_GET['kitgenix_saved'] : 0;
        if ( $saved ) {
            self::notice( __( 'Link saved.', 'kitgenix-affiliate-link-manager' ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $deleted = isset( $_GET['kitgenix_deleted'] ) ? (int) $_GET['kitgenix_deleted'] : 0;
        if ( $deleted ) {
            self::notice( __( 'Link deleted.', 'kitgenix-affiliate-link-manager' ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $duplicated = isset( $_GET['kitgenix_duplicated'] ) ? (int) $_GET['kitgenix_duplicated'] : 0;
        if ( $duplicated ) {
            self::notice( __( 'Link duplicated.', 'kitgenix-affiliate-link-manager' ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $reset = isset( $_GET['kitgenix_reset'] ) ? (int) $_GET['kitgenix_reset'] : 0;
        if ( $reset ) {
            self::notice( __( 'Click count reset.', 'kitgenix-affiliate-link-manager' ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bulk_deleted = isset( $_GET['kitgenix_bulk_deleted'] ) ? (int) $_GET['kitgenix_bulk_deleted'] : 0;
        if ( $bulk_deleted > 0 ) {
            /* translators: %d: number of affiliate links deleted by the bulk action. */
            $bulk_deleted_message = sprintf( \_n( '%d link deleted.', '%d links deleted.', $bulk_deleted, 'kitgenix-affiliate-link-manager' ), $bulk_deleted );
            self::notice( $bulk_deleted_message, 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bulk_reset = isset( $_GET['kitgenix_bulk_reset'] ) ? (int) $_GET['kitgenix_bulk_reset'] : 0;
        if ( $bulk_reset > 0 ) {
            /* translators: %d: number of affiliate link click counts reset by the bulk action. */
            $bulk_reset_message = sprintf( \_n( '%d click count reset.', '%d click counts reset.', $bulk_reset, 'kitgenix-affiliate-link-manager' ), $bulk_reset );
            self::notice( $bulk_reset_message, 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bulk_enabled = isset( $_GET['kitgenix_bulk_enabled'] ) ? (int) $_GET['kitgenix_bulk_enabled'] : 0;
        if ( $bulk_enabled > 0 ) {
            /* translators: %d: number of affiliate links enabled by the bulk action. */
            self::notice( sprintf( \_n( '%d link enabled.', '%d links enabled.', $bulk_enabled, 'kitgenix-affiliate-link-manager' ), $bulk_enabled ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bulk_disabled = isset( $_GET['kitgenix_bulk_disabled'] ) ? (int) $_GET['kitgenix_bulk_disabled'] : 0;
        if ( $bulk_disabled > 0 ) {
            /* translators: %d: number of affiliate links disabled by the bulk action. */
            self::notice( sprintf( \_n( '%d link disabled.', '%d links disabled.', $bulk_disabled, 'kitgenix-affiliate-link-manager' ), $bulk_disabled ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bulk_group = isset( $_GET['kitgenix_bulk_group'] ) ? (int) $_GET['kitgenix_bulk_group'] : 0;
        if ( $bulk_group > 0 ) {
            /* translators: %d: number of affiliate links moved into a group by the bulk action. */
            self::notice( sprintf( \_n( '%d link moved to the group.', '%d links moved to the group.', $bulk_group, 'kitgenix-affiliate-link-manager' ), $bulk_group ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bulk_tags = isset( $_GET['kitgenix_bulk_tags'] ) ? (int) $_GET['kitgenix_bulk_tags'] : 0;
        if ( $bulk_tags > 0 ) {
            /* translators: %d: number of affiliate links tagged by the bulk action. */
            self::notice( sprintf( \_n( '%d link tagged.', '%d links tagged.', $bulk_tags, 'kitgenix-affiliate-link-manager' ), $bulk_tags ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $bulk_checked = isset( $_GET['kitgenix_bulk_checked'] ) ? (int) $_GET['kitgenix_bulk_checked'] : 0;
        if ( $bulk_checked > 0 ) {
            /* translators: %d: number of affiliate links health-checked by the bulk action. */
            self::notice( sprintf( \_n( '%d link checked.', '%d links checked.', $bulk_checked, 'kitgenix-affiliate-link-manager' ), $bulk_checked ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['kitgenix_health_checked'] ) ) {
            self::notice( __( 'Destination health check complete.', 'kitgenix-affiliate-link-manager' ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $health_check_all = isset( $_GET['kitgenix_health_check_all'] ) ? (int) $_GET['kitgenix_health_check_all'] : -1;
        if ( $health_check_all >= 0 ) {
            /* translators: %d: number of links checked. */
            self::notice( sprintf( \_n( 'Health check complete for %d link.', 'Health check complete for %d links.', $health_check_all, 'kitgenix-affiliate-link-manager' ), $health_check_all ), 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['kitgenix_import_created'] ) || isset( $_GET['kitgenix_import_updated'] ) || isset( $_GET['kitgenix_import_failed'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $import_created = isset( $_GET['kitgenix_import_created'] ) ? (int) $_GET['kitgenix_import_created'] : 0;
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $import_updated = isset( $_GET['kitgenix_import_updated'] ) ? (int) $_GET['kitgenix_import_updated'] : 0;
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $import_failed  = isset( $_GET['kitgenix_import_failed'] ) ? (int) $_GET['kitgenix_import_failed'] : 0;
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $import_skipped = isset( $_GET['kitgenix_import_skipped'] ) ? (int) $_GET['kitgenix_import_skipped'] : 0;

            $import_message = sprintf(
                /* translators: 1: created count, 2: updated count, 3: failed count. */
                __( 'Import finished: %1$d created, %2$d updated, %3$d failed.', 'kitgenix-affiliate-link-manager' ),
                $import_created,
                $import_updated,
                $import_failed
            );
            if ( $import_skipped > 0 ) {
                $import_message .= ' ' . sprintf(
                    /* translators: %d: number of rows skipped because the file exceeded the per-import row limit. */
                    __( '%d additional row(s) were not processed because the file exceeded the per-import limit – split the file and import the rest separately.', 'kitgenix-affiliate-link-manager' ),
                    $import_skipped
                );
            }
            self::notice( $import_message, ( $import_failed > 0 || $import_skipped > 0 ) ? 'warning' : 'success' );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $error = isset( $_GET['kitgenix_error'] ) ? (string) \sanitize_text_field( \wp_unslash( $_GET['kitgenix_error'] ) ) : '';
        if ( $error !== '' ) {
            self::notice( $error, 'error' );
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
            self::notice( __( 'Destination health checks are queued. The first automated results will appear here shortly.', 'kitgenix-affiliate-link-manager' ), 'info' );
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

        $notice_type = $critical > 0 ? 'error' : 'warning';

        echo '<div class="kitgenix-notice kitgenix-notice-' . \esc_attr( $notice_type ) . '" role="status">'
            . '<div class="kitgenix-notice-body">'
            . '<p class="kitgenix-notice-text">' . \esc_html( implode( ' ', $messages ) ) . '</p>';

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
                echo '<p class="kitgenix-notice-text">' . \wp_kses_post( implode( ' | ', $issue_links ) ) . '</p>';
            }
        }

        if ( $checked_at > 0 ) {
            $recheck_url = \wp_nonce_url(
                \admin_url( 'admin-post.php?action=kitgenix_affiliate_run_health_checks_now' ),
                'kitgenix_affiliate_run_health_checks_now',
                'nonce'
            );

            echo '<p class="kitgenix-notice-text"><small>'
                . \esc_html(
                    sprintf(
                        /* translators: %s: local date and time of last health summary refresh. */
                        __( 'Last checked %s.', 'kitgenix-affiliate-link-manager' ),
                        self::format_schedule_timestamp( $checked_at )
                    )
                )
                . ' <a href="' . \esc_url( $recheck_url ) . '">' . \esc_html__( 'Run health check now', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '</small></p>';
        }

        echo '</div>'
            . '<button type="button" class="kitgenix-notice-close" aria-label="' . \esc_attr__( 'Dismiss', 'kitgenix-affiliate-link-manager' ) . '">&times;</button>'
            . '</div>';
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

        $ver = defined( 'KITGENIX_AFFILIATE_LINK_MANAGER_VERSION' ) ? (string) KITGENIX_AFFILIATE_LINK_MANAGER_VERSION : '2.0.0';

        self::render_admin_notices();

        $logo        = \plugins_url( 'assets/images/logos/kitgenix-primary-favicon.svg', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE );
        $base_tab_url = \admin_url( 'admin.php?page=' . rawurlencode( 'kitgenix-affiliate-link-manager' ) );

        echo '<div class="wrap kitgenix-admin-app kitgenix-affiliate-link-manager-use-top-tabs" data-kitgenix-tabs data-kitgenix-default-tab="' . \esc_attr( $default_tab ) . '" id="kitgenix-affiliate-link-manager-admin-app">';

        echo '<div class="kitgenix-topbar">'
            . '<div class="kitgenix-topbar-left">'
            . '<a class="kitgenix-topbar-brand" href="' . \esc_url( \admin_url( 'admin.php?page=kitgenix' ) ) . '" title="Kitgenix">'
            . '<img class="kitgenix-topbar-logo" src="' . \esc_url( $logo ) . '" alt="Kitgenix" width="28" height="28" />'
            . '</a>'
            . '<span class="kitgenix-topbar-divider" aria-hidden="true"></span>'
            . '<div class="kitgenix-topbar-plugin-info">'
            . '<span class="kitgenix-topbar-title">' . \esc_html__( 'Affiliate Link Manager', 'kitgenix-affiliate-link-manager' ) . '</span>'
            . '<span class="kitgenix-topbar-version">v' . \esc_html( $ver ) . '</span>'
            . '</div>'
            . '</div>'
            . '<div class="kitgenix-topbar-center">'
            . '<ul class="kitgenix-topbar-menu" role="tablist">'
            . '<li class="kitgenix-menu-item ' . ( $default_tab === 'links' ? 'kitgenix-active' : '' ) . '"><a class="kitgenix-menu-link kitgenix-tab-trigger ' . ( $default_tab === 'links' ? 'kitgenix-active' : '' ) . '" href="' . \esc_url( $base_tab_url . '&tab=links#kitgenix-tab-links' ) . '" data-kitgenix-tab="links">' . \esc_html__( 'Links', 'kitgenix-affiliate-link-manager' ) . '</a></li>'
            . '<li class="kitgenix-menu-item ' . ( $default_tab === 'settings' ? 'kitgenix-active' : '' ) . '"><a class="kitgenix-menu-link kitgenix-tab-trigger ' . ( $default_tab === 'settings' ? 'kitgenix-active' : '' ) . '" href="' . \esc_url( $base_tab_url . '&tab=settings#kitgenix-tab-settings' ) . '" data-kitgenix-tab="settings">' . \esc_html__( 'Settings', 'kitgenix-affiliate-link-manager' ) . '</a></li>'
            . '<li class="kitgenix-menu-item ' . ( $default_tab === 'log' ? 'kitgenix-active' : '' ) . '"><a class="kitgenix-menu-link kitgenix-tab-trigger ' . ( $default_tab === 'log' ? 'kitgenix-active' : '' ) . '" href="' . \esc_url( $base_tab_url . '&tab=log#kitgenix-tab-log' ) . '" data-kitgenix-tab="log">' . \esc_html__( 'Log', 'kitgenix-affiliate-link-manager' ) . '</a></li>'
            . '<li class="kitgenix-menu-item ' . ( $default_tab === 'support' ? 'kitgenix-active' : '' ) . '"><a class="kitgenix-menu-link kitgenix-tab-trigger ' . ( $default_tab === 'support' ? 'kitgenix-active' : '' ) . '" href="' . \esc_url( $base_tab_url . '&tab=support#kitgenix-tab-support' ) . '" data-kitgenix-tab="support">' . \esc_html__( 'Support', 'kitgenix-affiliate-link-manager' ) . '</a></li>'
            . '</ul>'
            . '</div>'
            . '<div class="kitgenix-topbar-right" aria-label="Topbar actions">'
            . '<div class="kitgenix-topbar-search">'
            . '<button type="button" class="kitgenix-topbar-icon-btn kitgenix-search-toggle" aria-label="' . \esc_attr__( 'Search settings', 'kitgenix-affiliate-link-manager' ) . '" aria-expanded="false"><svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.5232 13.4627L17.7355 16.6742L16.6742 17.7355L13.4627 14.5232C12.2678 15.4812 10.7815 16.0022 9.25 16C5.524 16 2.5 12.976 2.5 9.25C2.5 5.524 5.524 2.5 9.25 2.5C12.976 2.5 16 5.524 16 9.25C16.0022 10.7815 15.4812 12.2678 14.5232 13.4627ZM13.0187 12.9062C13.9706 11.9274 14.5021 10.6153 14.5 9.25C14.5 6.349 12.1502 4 9.25 4C6.349 4 4 6.349 4 9.25C4 12.1502 6.349 14.5 9.25 14.5C10.6153 14.5021 11.9274 13.9706 12.9062 13.0187L13.0187 12.9062V12.9062Z" fill="currentColor"></path></svg></button>'
            . '<div class="kitgenix-search-panel">'
            . '<span class="kitgenix-search-icon" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.5232 13.4627L17.7355 16.6742L16.6742 17.7355L13.4627 14.5232C12.2678 15.4812 10.7815 16.0022 9.25 16C5.524 16 2.5 12.976 2.5 9.25C2.5 5.524 5.524 2.5 9.25 2.5C12.976 2.5 16 5.524 16 9.25C16.0022 10.7815 15.4812 12.2678 14.5232 13.4627ZM13.0187 12.9062C13.9706 11.9274 14.5021 10.6153 14.5 9.25C14.5 6.349 12.1502 4 9.25 4C6.349 4 4 6.349 4 9.25C4 12.1502 6.349 14.5 9.25 14.5C10.6153 14.5021 11.9274 13.9706 12.9062 13.0187L13.0187 12.9062V12.9062Z" fill="currentColor"></path></svg></span>'
            . '<input type="search" class="kitgenix-topbar-search-input" placeholder="' . \esc_attr__( 'Search settings…', 'kitgenix-affiliate-link-manager' ) . '" aria-label="' . \esc_attr__( 'Search settings', 'kitgenix-affiliate-link-manager' ) . '" autocomplete="off" />'
            . '<kbd class="kitgenix-search-kbd" title="Press / or ⌘K to search">/</kbd>'
            . '<button type="button" class="kitgenix-search-clear" aria-label="' . \esc_attr__( 'Clear search', 'kitgenix-affiliate-link-manager' ) . '" style="display:none;">&times;</button>'
            . '</div>'
            . '</div>'
            . '<button type="button" class="kitgenix-topbar-icon-btn kitgenix-theme-toggle" aria-label="' . \esc_attr__( 'Toggle color theme', 'kitgenix-affiliate-link-manager' ) . '" title="' . \esc_attr__( 'Toggle color theme', 'kitgenix-affiliate-link-manager' ) . '">'
            . '<svg class="kitgenix-theme-icon-light" width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="3.25" stroke="currentColor" stroke-width="1.3"></circle><path d="M10 2.5v2M10 15.5v2M17.5 10h-2M4.5 10h-2M15.3 4.7l-1.4 1.4M6.1 13.9l-1.4 1.4M15.3 15.3l-1.4-1.4M6.1 6.1 4.7 4.7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"></path></svg>'
            . '<svg class="kitgenix-theme-icon-dark" width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.5 12.3A6.5 6.5 0 0 1 7.7 3.5a6.5 6.5 0 1 0 8.8 8.8Z" fill="currentColor"></path></svg>'
            . '</button>'
            . '<a class="kitgenix-topbar-icon-btn" href="' . \esc_url( \admin_url( 'admin.php?page=kitgenix' ) ) . '" title="' . \esc_attr__( 'Kitgenix Hub', 'kitgenix-affiliate-link-manager' ) . '" aria-label="Kitgenix Hub"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.25 3.25H9.25V9.25H3.25V3.25ZM10.75 3.25H16.75V9.25H10.75V3.25ZM3.25 10.75H9.25V16.75H3.25V10.75ZM10.75 10.75H16.75V16.75H10.75V10.75Z" fill="currentColor"></path></svg></a>'
            . '<button type="button" class="kitgenix-topbar-hamburger" aria-label="' . \esc_attr__( 'Toggle navigation', 'kitgenix-affiliate-link-manager' ) . '"><span></span><span></span><span></span></button>'
            . '</div>'
            . '</div>';

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
        echo '</div>'; // .wrap.kitgenix-admin-app
    }

    private static function render_sidebar(): void {
        $social_base = \plugins_url( 'assets/images/social-media/', \KITGENIX_AFFILIATE_LINK_MANAGER_FILE );

        echo '<aside class="kitgenix-settings-sidebar" aria-label="' . \esc_attr__( 'Help and links', 'kitgenix-affiliate-link-manager' ) . '">';

        echo '<div class="kitgenix-card"><div class="kitgenix-card-body">'
            . '<h2>' . \esc_html__( 'Need Help?', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<p>' . \esc_html__( 'Open the documentation for setup guidance or send us a support request if you need help configuring the plugin.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '<div class="kitgenix-button-group kitgenix-button-group-stack">'
            . '<a class="button button-secondary" href="' . \esc_url( 'https://kitgenix.com/plugins/kitgenix-affiliate-link-manager/documentation/' ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Documentation', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '<a class="button button-primary" href="' . \esc_url( 'https://wordpress.org/support/plugin/kitgenix-affiliate-link-manager/' ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Request Support', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '</div>'
            . '</div></div>';

        echo '<div class="kitgenix-card"><div class="kitgenix-card-body">'
            . '<h2>' . \esc_html__( 'Visit Our Official Facebook Group', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<p>' . \esc_html__( 'Join the Kitgenix community to ask questions, share feedback, and keep up with product updates.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '<div class="kitgenix-button-group kitgenix-button-group-stack">'
            . '<a class="button button-secondary" href="' . \esc_url( 'https://www.facebook.com/groups/kitgenix' ) . '" target="_blank" rel="noopener noreferrer">' . \esc_html__( 'Join Group', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '</div>'
            . '</div></div>';

        echo '<div class="kitgenix-card"><div class="kitgenix-card-body">'
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
            . '</div></div>';

        echo '</aside>';
    }

    private static function render_import_card(): void {
        echo '<div class="kitgenix-card" data-kitgenix-collapsible>'
            . '<button type="button" class="kitgenix-collapsible-trigger" aria-expanded="false">'
            . '<span class="kitgenix-card-head-main">'
            . '<span class="kitgenix-card-head-text">'
            . '<span class="kitgenix-collapsible-title">' . \esc_html__( 'Import Links', 'kitgenix-affiliate-link-manager' ) . '</span>'
            . '<span class="kitgenix-collapsible-desc">' . \esc_html__( 'Bulk-create or update links from a CSV or JSON file using the same columns as CSV export.', 'kitgenix-affiliate-link-manager' ) . '</span>'
            . '</span>'
            . '</span>'
            . '<svg class="kitgenix-collapsible-chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            . '</button>';

        echo '<div class="kitgenix-collapsible-panel" hidden><div class="kitgenix-card-body">';

        echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data">'
            . '<input type="hidden" name="action" value="kitgenix_affiliate_link_import" />';

        \wp_nonce_field( 'kitgenix_affiliate_link_import', 'kitgenix_affiliate_link_import_nonce' );

        echo '<div class="kitgenix-settings-group">';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_import_file">' . \esc_html__( 'File', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Accepts .csv or .json files, including files exported from this plugin, the REST API, or WP-CLI.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="file" id="kitgenix_affiliate_import_file" name="import_file" accept=".csv,.json" required /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_import_match">' . \esc_html__( 'Match existing links by', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Controls whether a matching row updates an existing link or always creates a new one.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><select id="kitgenix_affiliate_import_match" name="match_existing">'
            . '<option value="slug">' . \esc_html__( 'Slug (recommended)', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="id">' . \esc_html__( 'ID', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="none">' . \esc_html__( 'Always create new', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select></div>'
            . '</div>';

        echo '</div>'; // .kitgenix-settings-group

        echo '<div class="kitgenix-button-group">';
        \submit_button( \__( 'Import Links', 'kitgenix-affiliate-link-manager' ), 'primary', 'submit', false );
        echo '</div>';

        echo '</form>';

        echo '</div></div>'; // .kitgenix-card-body, .kitgenix-collapsible-panel
        echo '</div>'; // .kitgenix-card
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

        echo '<div class="kitgenix-links-grid">';

        echo '<div class="kitgenix-card">'
            . '<div class="kitgenix-card-head"><div class="kitgenix-card-head-main"><div class="kitgenix-card-head-text">'
            . '<h2>' . ( $is_edit ? \esc_html__( 'Edit Link', 'kitgenix-affiliate-link-manager' ) : \esc_html__( 'Add Link', 'kitgenix-affiliate-link-manager' ) ) . '</h2>'
            . '</div></div></div>'
            . '<div class="kitgenix-card-body">'
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

        echo '<div class="kitgenix-settings-group">';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_title">' . \esc_html__( 'Name', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><input type="text" id="kitgenix_affiliate_title" name="title" value="' . \esc_attr( $title ) . '" class="regular-text" required /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_slug">' . \esc_html__( 'Slug', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Leave blank to auto-generate. You can change it later.', 'kitgenix-affiliate-link-manager' ) . ' '
            . \esc_html( \home_url( '/' . $prefix . '/' ) ) . '<span class="kitgenix-code">{slug}</span></p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="text" id="kitgenix_affiliate_slug" name="slug" value="' . \esc_attr( $slug ) . '" class="regular-text" placeholder="' . \esc_attr__( 'Auto-generated from name', 'kitgenix-affiliate-link-manager' ) . '" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_destination">' . \esc_html__( 'Destination URL', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><input type="url" id="kitgenix_affiliate_destination" name="destination_url" value="' . \esc_attr( $dest ) . '" class="regular-text" required /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_starts_at">' . \esc_html__( 'Start date and time', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Leave blank to make the link active immediately. Times use the site timezone.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="datetime-local" id="kitgenix_affiliate_starts_at" name="starts_at" value="' . \esc_attr( $starts_at ) . '" class="regular-text" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_expires_at">' . \esc_html__( 'Expiry date and time', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Leave blank to keep the active destination live until you change it.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="datetime-local" id="kitgenix_affiliate_expires_at" name="expires_at" value="' . \esc_attr( $expires_at ) . '" class="regular-text" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_fallback_url">' . \esc_html__( 'Fallback URL', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Optional. When the link is scheduled but not live yet, or after it expires, visitors are redirected here instead of seeing a 404.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="url" id="kitgenix_affiliate_fallback_url" name="fallback_url" value="' . \esc_attr( $fallback_url ) . '" class="regular-text" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_rotation_mode">' . \esc_html__( 'Rotation mode', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Rotation uses the primary destination plus any additional destinations below while the link is active.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><select id="kitgenix_affiliate_rotation_mode" name="rotation_mode">'
            . '<option value="none"' . \selected( $rotation_mode, 'none', false ) . '>' . \esc_html__( 'No rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="sequential"' . \selected( $rotation_mode, 'sequential', false ) . '>' . \esc_html__( 'Sequential rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="random"' . \selected( $rotation_mode, 'random', false ) . '>' . \esc_html__( 'Random rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_rotation_destinations">' . \esc_html__( 'Additional rotation destinations', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Add one URL per line. The primary destination stays in the rotation pool automatically.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><textarea id="kitgenix_affiliate_rotation_destinations" name="rotation_destinations" rows="4" class="large-text" placeholder="' . \esc_attr__( "https://example.com/offer-a\nhttps://example.com/offer-b", 'kitgenix-affiliate-link-manager' ) . '">' . \esc_textarea( $rotation_destinations ) . '</textarea></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_group_name">' . \esc_html__( 'Link group', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Assign one primary group so larger link libraries can be split into reusable campaign views.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="text" id="kitgenix_affiliate_group_name" name="group_name" value="' . \esc_attr( $group_name ) . '" class="regular-text" list="kitgenix-affiliate-group-options" placeholder="' . \esc_attr__( 'Example: Summer Campaigns', 'kitgenix-affiliate-link-manager' ) . '" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_tags">' . \esc_html__( 'Tags', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Separate tags with commas to label vendors, traffic sources, or campaign types.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="text" id="kitgenix_affiliate_tags" name="tag_names" value="' . \esc_attr( $tags ) . '" class="regular-text" placeholder="' . \esc_attr__( 'Example: email, q3, review-post', 'kitgenix-affiliate-link-manager' ) . '" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_campaign_note">' . \esc_html__( 'Campaign note', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Store quick context for the link, such as placement, partner terms, or campaign reminders.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><textarea id="kitgenix_affiliate_campaign_note" name="campaign_note" rows="4" class="large-text">' . \esc_textarea( $campaign_note ) . '</textarea></div>'
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

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_rel">' . \esc_html__( 'Link rel', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Use sponsored/nofollow for affiliate links to follow search engine guidelines.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><select id="kitgenix_affiliate_rel" name="rel">'
            . '<option value="nofollow"' . \selected( $rel, 'nofollow', false ) . '>' . \esc_html__( 'nofollow', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="sponsored"' . \selected( $rel, 'sponsored', false ) . '>' . \esc_html__( 'sponsored', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="nofollow sponsored"' . \selected( $rel, 'nofollow sponsored', false ) . '>' . \esc_html__( 'nofollow sponsored', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_enabled">' . \esc_html__( 'Enabled', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Disable to temporarily stop redirects without deleting the link (disabled links return 404).', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control">'
            . '<label class="kitgenix-toggle">'
            . '<input type="checkbox" id="kitgenix_affiliate_enabled" class="kitgenix-toggle-input" name="enabled" value="1"' . ( $enabled ? ' checked="checked"' : '' ) . ' />'
            . '<span class="kitgenix-toggle-track"><span class="kitgenix-toggle-thumb"></span></span>'
            . '<span class="kitgenix-toggle-label">' . \esc_html__( 'This link is active and will redirect', 'kitgenix-affiliate-link-manager' ) . '</span>'
            . '</label>'
            . '</div>'
            . '</div>';

        if ( $is_edit ) {
            echo '<div class="kitgenix-setting-row">'
                . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_clicks">' . \esc_html__( 'Click count', 'kitgenix-affiliate-link-manager' ) . '</label>'
                . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Set an exact starting total, e.g. when migrating from another shortlink tool. Use Reset clicks instead to zero it out.', 'kitgenix-affiliate-link-manager' ) . '</p>'
                . '</div>'
                . '<div class="kitgenix-setting-row-control"><input type="number" id="kitgenix_affiliate_clicks" name="clicks" value="' . \esc_attr( (string) Affiliate_Link_Post_Type::get_click_count( $edit_id ) ) . '" min="0" step="1" class="small-text" /></div>'
                . '</div>';
        }

        echo '</div>'; // .kitgenix-settings-group

        echo '<div class="kitgenix-button-group">';
        \submit_button( $is_edit ? \__( 'Update Link', 'kitgenix-affiliate-link-manager' ) : \__( 'Add Link', 'kitgenix-affiliate-link-manager' ), 'primary', 'submit', false );
        if ( $is_edit ) {
            echo '<a class="button" href="' . \esc_url( self::get_links_tab_url( $current_view_args ) ) . '">' . \esc_html__( 'Cancel', 'kitgenix-affiliate-link-manager' ) . '</a>';
        }
        echo '</div>';

        echo '</form>';
        echo '</div>'; // .kitgenix-card-body

        if ( $is_edit ) {
            self::render_link_analytics_panel( $edit_id );
        }

        echo '</div>'; // .kitgenix-card

        self::render_import_card();

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

        echo '<div class="kitgenix-card"><div class="kitgenix-card-body">';
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
        echo '</div></div>'; // .kitgenix-card-body, .kitgenix-card

        self::render_analytics_overview_panel();

        $run_health_checks_url = \wp_nonce_url(
            \admin_url( 'admin-post.php?action=kitgenix_affiliate_run_health_checks_now' ),
            'kitgenix_affiliate_run_health_checks_now',
            'nonce'
        );

        echo '<div class="kitgenix-card">';
        echo '<div class="kitgenix-card-head"><div class="kitgenix-card-head-main"><div class="kitgenix-card-head-text">'
            . '<h2>' . \esc_html__( 'All Links', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<p>' . \esc_html__( 'Search, filter, and review links by campaign group, tags, notes, and recent analytics context.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div></div>'
            . '<div class="kitgenix-card-head-actions">'
            . '<a href="' . \esc_url( $run_health_checks_url ) . '" class="button button-secondary">' . \esc_html__( 'Run health check now', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '</div>'
            . '</div>';

        echo '<div class="kitgenix-card-body">';

        echo '<form class="kitgenix-search-bar kitgenix-links-toolbar" role="search" method="get" action="' . \esc_url( \admin_url( 'admin.php' ) ) . '">'
            . '<input type="hidden" name="page" value="kitgenix-affiliate-link-manager" />'
            . '<input type="hidden" name="tab" value="links" />'
            . '<input type="hidden" name="orderby" value="' . \esc_attr( $orderby ) . '" />'
            . '<input type="hidden" name="order" value="' . \esc_attr( $order ) . '" />'
            . '<div class="kitgenix-search-bar-input">'
            . '<span class="kitgenix-search-bar-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.5232 13.4627L17.7355 16.6742L16.6742 17.7355L13.4627 14.5232C12.2678 15.4812 10.7815 16.0022 9.25 16C5.524 16 2.5 12.976 2.5 9.25C2.5 5.524 5.524 2.5 9.25 2.5C12.976 2.5 16 5.524 16 9.25C16.0022 10.7815 15.4812 12.2678 14.5232 13.4627ZM13.0187 12.9062C13.9706 11.9274 14.5021 10.6153 14.5 9.25C14.5 6.349 12.1502 4 9.25 4C6.349 4 4 6.349 4 9.25C4 12.1502 6.349 14.5 9.25 14.5C10.6153 14.5021 11.9274 13.9706 12.9062 13.0187L13.0187 12.9062V12.9062Z" fill="currentColor"></path></svg></span>'
            . '<label class="screen-reader-text" for="kitgenix-affiliate-links-search">' . \esc_html__( 'Search links', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="search" id="kitgenix-affiliate-links-search" name="s" value="' . \esc_attr( $search ) . '" data-kitgenix-server-search="1" placeholder="' . \esc_attr__( 'Search links, groups, tags, or notes...', 'kitgenix-affiliate-link-manager' ) . '" autocomplete="off" />'
            . '</div>'
            . '<div class="kitgenix-search-bar-filters">'
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
            . '</div>'
            . '<div class="kitgenix-search-bar-actions">'
            . '<button type="submit" class="button">' . \esc_html__( 'Filter', 'kitgenix-affiliate-link-manager' ) . '</button>'
            . ( $search !== '' || $group_filter !== '' || $tag_filter !== ''
                ? '<a class="button" id="kitgenix-affiliate-links-clear" href="' . \esc_url( self::get_links_tab_url( $clear_args ) ) . '">' . \esc_html__( 'Clear', 'kitgenix-affiliate-link-manager' ) . '</a>'
                : '' )
            . '<span class="kitgenix-muted kitgenix-links-count" id="kitgenix-affiliate-links-count" aria-live="polite"></span>'
            . '</div>'
            . '</form>';

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
            $empty_message = ( $search !== '' || $group_filter !== '' || $tag_filter !== '' )
                ? \esc_html__( 'No links match the current campaign filters.', 'kitgenix-affiliate-link-manager' )
                : \esc_html__( 'No links yet. Add your first one on the left.', 'kitgenix-affiliate-link-manager' );

            echo '<div class="kitgenix-empty-state">'
                . '<span class="kitgenix-empty-state-icon" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18.84 12.25l1.72-1.71h-.02a5.004 5.004 0 0 0-.12-7.07 5.006 5.006 0 0 0-6.95 0l-1.72 1.71" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.17 11.75l-1.71 1.71a5.004 5.004 0 0 0 .12 7.07 5.006 5.006 0 0 0 6.95 0l1.71-1.71" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="8" y1="2" x2="8" y2="5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="2" y1="8" x2="5" y2="8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="16" y1="19" x2="16" y2="22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="19" y1="16" x2="22" y2="16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>'
                . '<p class="kitgenix-empty-state-title">' . \esc_html( $empty_message ) . '</p>'
                . '</div>';

            echo '</div></div></div>'; // .kitgenix-card-body, .kitgenix-card, .kitgenix-links-grid
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

        $sort_class = static function ( string $col ) use ( $orderby, $order ): string {
            if ( $orderby !== $col ) {
                return '';
            }

            return $order === 'ASC' ? ' kitgenix-sort-asc' : ' kitgenix-sort-desc';
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

        echo '<div class="kitgenix-button-group">'
            . '<label class="screen-reader-text" for="kitgenix_bulk_action">' . \esc_html__( 'Select bulk action', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<select name="bulk_action" id="kitgenix_bulk_action">'
            . '<option value="">' . \esc_html__( 'Bulk actions', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="enable">' . \esc_html__( 'Enable', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="disable">' . \esc_html__( 'Disable', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="add_group" data-kitgenix-bulk-needs-value="' . \esc_attr__( 'Group name…', 'kitgenix-affiliate-link-manager' ) . '">' . \esc_html__( 'Set group…', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="add_tags" data-kitgenix-bulk-needs-value="' . \esc_attr__( 'Tags, comma separated…', 'kitgenix-affiliate-link-manager' ) . '">' . \esc_html__( 'Add tags…', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="check_health">' . \esc_html__( 'Check destination health now', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="reset_clicks">' . \esc_html__( 'Reset clicks', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="export_csv">' . \esc_html__( 'Export CSV', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="delete">' . \esc_html__( 'Delete', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select>'
            . '<label class="screen-reader-text" for="kitgenix_bulk_value">' . \esc_html__( 'Bulk action value', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<input type="text" name="bulk_value" id="kitgenix_bulk_value" class="regular-text" style="display:none;" />'
            . '<button type="submit" class="button">' . \esc_html__( 'Apply', 'kitgenix-affiliate-link-manager' ) . '</button>'
            . '</div>';

        echo '<div class="kitgenix-table-wrap">'
            . '<table class="kitgenix-table kitgenix-links-table" id="kitgenix-affiliate-links-table">'
            . '<thead><tr>'
            . '<th class="kitgenix-col-select"><input type="checkbox" id="kitgenix-select-all-links" aria-label="' . \esc_attr__( 'Select all links', 'kitgenix-affiliate-link-manager' ) . '" /></th>'
            . '<th class="kitgenix-col-name kitgenix-table-sortable' . \esc_attr( $sort_class( 'title' ) ) . '" aria-sort="' . \esc_attr( $aria_sort( 'title' ) ) . '"><a href="' . \esc_url( $build_sort_url( 'title' ) ) . '"><span>' . \esc_html__( 'Name', 'kitgenix-affiliate-link-manager' ) . '</span></a></th>'
            . '<th class="kitgenix-col-destination">' . \esc_html__( 'Destination', 'kitgenix-affiliate-link-manager' ) . '</th>'
            . '<th class="kitgenix-col-short">' . \esc_html__( 'Short URL', 'kitgenix-affiliate-link-manager' ) . '</th>'
            . '<th class="kitgenix-col-clicks kitgenix-table-sortable' . \esc_attr( $sort_class( 'clicks' ) ) . '" aria-sort="' . \esc_attr( $aria_sort( 'clicks' ) ) . '"><a href="' . \esc_url( $build_sort_url( 'clicks' ) ) . '"><span>' . \esc_html__( 'Clicks', 'kitgenix-affiliate-link-manager' ) . '</span></a></th>'
            . '<th class="kitgenix-table-actions-col">' . \esc_html__( 'Actions', 'kitgenix-affiliate-link-manager' ) . '</th>'
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
            $short_id        = 'kitgenix-affiliate-short-' . $link_id;

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

            $check_health_url = \add_query_arg(
                array_merge(
                    [
                        'action'  => 'kitgenix_affiliate_link_check_health',
                        'link_id' => $link_id,
                    ],
                    $action_return_args
                ),
                \admin_url( 'admin-post.php' )
            );
            $check_health_url = \wp_nonce_url( $check_health_url, 'kitgenix_affiliate_link_check_health', 'nonce' );

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

            echo '<tr' . ( ! $row_enabled ? ' class="kitgenix-table-row-muted"' : '' ) . ' data-kitgenix-table-row>'
                . '<td class="kitgenix-col-select"><input type="checkbox" name="link_ids[]" value="' . \esc_attr( (string) $link_id ) . '" /></td>'
                . '<td class="kitgenix-col-name"><strong>' . \esc_html( $row_title ) . '</strong>'
                . ( $row_enabled ? '' : ' <span class="kitgenix-muted">' . \esc_html__( '(Disabled)', 'kitgenix-affiliate-link-manager' ) . '</span>' )
                . ( ! empty( $meta_bits ) ? '<div class="kitgenix-affiliate-meta">' . \wp_kses_post( \implode( '', $meta_bits ) ) . '</div>' : '' )
                . ( $row_rules_summary !== '' ? '<div class="kitgenix-muted">' . \esc_html( $row_rules_summary ) . '</div>' : '' )
                . ( $row_health_note !== '' ? '<div class="kitgenix-muted">' . \esc_html( $row_health_note ) . '</div>' : '' )
                . ( $row_note_short !== '' ? '<div class="kitgenix-affiliate-note">' . \esc_html( $row_note_short ) . '</div>' : '' )
                . '</td>'
                . '<td class="kitgenix-col-destination"><a href="' . \esc_url( $row_dest ) . '" target="_blank" rel="noopener noreferrer ' . \esc_attr( $rel_value ) . '">' . \esc_html( $row_dest ) . '</a></td>'
                . '<td class="kitgenix-col-short"><span class="kitgenix-code" id="' . \esc_attr( $short_id ) . '">' . \esc_html( $short ) . '</span> '
                . '<a href="#" class="button button-small" data-default-label="' . \esc_attr__( 'Copy', 'kitgenix-affiliate-link-manager' ) . '" data-copied-label="' . \esc_attr__( 'Copied', 'kitgenix-affiliate-link-manager' ) . '" data-kitgenix-copy-text="' . \esc_attr( $short ) . '">' . \esc_html__( 'Copy', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '</td>'
                . '<td class="kitgenix-col-clicks">' . \esc_html( (string) $clicks ) . '</td>'
                . '<td class="kitgenix-table-actions-col"><div class="kitgenix-table-actions">'
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
                . ' data-clicks="' . \esc_attr( (string) $clicks ) . '"'
                . ' data-delete-url="' . \esc_attr( $delete_url ) . '"'
                . '>' . \esc_html__( 'Edit', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '<a href="' . \esc_url( $duplicate_url ) . '">' . \esc_html__( 'Duplicate', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '<a href="' . \esc_url( $check_health_url ) . '">' . \esc_html__( 'Check now', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '<a href="' . \esc_url( $reset_url ) . '" onclick="return confirm(\'' . \esc_js( \__( 'Reset click count to 0?', 'kitgenix-affiliate-link-manager' ) ) . '\');">' . \esc_html__( 'Reset clicks', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '<a class="kitgenix-action-danger" href="' . \esc_url( $delete_url ) . '" onclick="return confirm(\'' . \esc_js( \__( 'Move this link to the Trash?', 'kitgenix-affiliate-link-manager' ) ) . '\');">' . \esc_html__( 'Delete', 'kitgenix-affiliate-link-manager' ) . '</a>'
                . '</div></td>'
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
                echo '<div class="kitgenix-pagination"><div class="kitgenix-pagination-controls">'
                    . \wp_kses_post( $pagination_links )
                    . '</div></div>';
            }
        }

        echo '</form>';

        echo '<div class="kitgenix-modal" id="kitgenix-affiliate-edit-modal" hidden role="dialog" aria-modal="true" aria-labelledby="kitgenix-affiliate-modal-title" aria-describedby="kitgenix-affiliate-modal-desc">'
            . '<div class="kitgenix-modal-backdrop" data-kitgenix-modal-close></div>'
            . '<div class="kitgenix-modal-dialog" role="document">';

        echo '<form method="post" action="' . \esc_url( \admin_url( 'admin-post.php' ) ) . '" id="kitgenix-affiliate-edit-form">'
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

        echo '<div class="kitgenix-modal-header">'
            . '<h2 class="kitgenix-modal-title" id="kitgenix-affiliate-modal-title">' . \esc_html__( 'Edit Link', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<button type="button" class="kitgenix-modal-close" data-kitgenix-modal-close aria-label="' . \esc_attr__( 'Close', 'kitgenix-affiliate-link-manager' ) . '">&times;</button>'
            . '</div>';

        echo '<div class="kitgenix-modal-body">';
        echo '<p id="kitgenix-affiliate-modal-desc" class="kitgenix-sr-only">' . \esc_html__( 'Edit affiliate link details.', 'kitgenix-affiliate-link-manager' ) . '</p>';

        echo '<div class="kitgenix-settings-group">';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_title">' . \esc_html__( 'Name', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><input type="text" id="kitgenix_affiliate_modal_title" name="title" value="" class="regular-text" required /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_slug">' . \esc_html__( 'Slug', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Leave blank to auto-generate. You can change it later.', 'kitgenix-affiliate-link-manager' ) . ' '
            . \esc_html( \home_url( '/' . $prefix . '/' ) ) . '<span class="kitgenix-code">{slug}</span></p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="text" id="kitgenix_affiliate_modal_slug" name="slug" value="" class="regular-text" placeholder="' . \esc_attr__( 'Auto-generated from name', 'kitgenix-affiliate-link-manager' ) . '" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_destination">' . \esc_html__( 'Destination URL', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><input type="url" id="kitgenix_affiliate_modal_destination" name="destination_url" value="" class="regular-text" required /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_starts_at">' . \esc_html__( 'Start date and time', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><input type="datetime-local" id="kitgenix_affiliate_modal_starts_at" name="starts_at" value="" class="regular-text" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_expires_at">' . \esc_html__( 'Expiry date and time', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><input type="datetime-local" id="kitgenix_affiliate_modal_expires_at" name="expires_at" value="" class="regular-text" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_fallback_url">' . \esc_html__( 'Fallback URL', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><input type="url" id="kitgenix_affiliate_modal_fallback_url" name="fallback_url" value="" class="regular-text" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_rotation_mode">' . \esc_html__( 'Rotation mode', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><select id="kitgenix_affiliate_modal_rotation_mode" name="rotation_mode">'
            . '<option value="none">' . \esc_html__( 'No rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="sequential">' . \esc_html__( 'Sequential rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="random">' . \esc_html__( 'Random rotation', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_rotation_destinations">' . \esc_html__( 'Additional rotation destinations', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><textarea id="kitgenix_affiliate_modal_rotation_destinations" name="rotation_destinations" rows="4" class="large-text"></textarea></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_group_name">' . \esc_html__( 'Link group', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><input type="text" id="kitgenix_affiliate_modal_group_name" name="group_name" value="" class="regular-text" list="kitgenix-affiliate-group-options" placeholder="' . \esc_attr__( 'Example: Summer Campaigns', 'kitgenix-affiliate-link-manager' ) . '" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_tags">' . \esc_html__( 'Tags', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><input type="text" id="kitgenix_affiliate_modal_tags" name="tag_names" value="" class="regular-text" placeholder="' . \esc_attr__( 'Example: email, q3, review-post', 'kitgenix-affiliate-link-manager' ) . '" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_campaign_note">' . \esc_html__( 'Campaign note', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><textarea id="kitgenix_affiliate_modal_campaign_note" name="campaign_note" rows="4" class="large-text"></textarea></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_rel">' . \esc_html__( 'Link rel', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Use sponsored/nofollow for affiliate links to follow search engine guidelines.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><select id="kitgenix_affiliate_modal_rel" name="rel">'
            . '<option value="nofollow">' . \esc_html__( 'nofollow', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="sponsored">' . \esc_html__( 'sponsored', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="nofollow sponsored">' . \esc_html__( 'nofollow sponsored', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_enabled">' . \esc_html__( 'Enabled', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control">'
            . '<label class="kitgenix-toggle">'
            . '<input type="checkbox" id="kitgenix_affiliate_modal_enabled" class="kitgenix-toggle-input" name="enabled" value="1" checked="checked" />'
            . '<span class="kitgenix-toggle-track"><span class="kitgenix-toggle-thumb"></span></span>'
            . '<span class="kitgenix-toggle-label">' . \esc_html__( 'This link is active and will redirect', 'kitgenix-affiliate-link-manager' ) . '</span>'
            . '</label>'
            . '</div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_modal_clicks">' . \esc_html__( 'Click count', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Set an exact starting total, e.g. when migrating from another shortlink tool.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="number" id="kitgenix_affiliate_modal_clicks" name="clicks" value="0" min="0" step="1" class="small-text" /></div>'
            . '</div>';

        echo '</div>'; // .kitgenix-settings-group
        echo '</div>'; // .kitgenix-modal-body

        echo '<div class="kitgenix-modal-footer">';
        echo '<a href="#" class="button" data-kitgenix-modal-close>' . \esc_html__( 'Cancel', 'kitgenix-affiliate-link-manager' ) . '</a>';
        echo ' <a href="#" class="button button-link-delete" id="kitgenix-affiliate-modal-delete">' . \esc_html__( 'Delete', 'kitgenix-affiliate-link-manager' ) . '</a>';
        echo ' ';
        \submit_button( \__( 'Save Changes', 'kitgenix-affiliate-link-manager' ), 'primary', 'submit', false );
        echo '</div>'; // .kitgenix-modal-footer

        echo '</form>'
            . '</div>' // .kitgenix-modal-dialog
            . '</div>'; // .kitgenix-modal

        echo '</div></div></div>'; // .kitgenix-card-body, .kitgenix-card (All Links), .kitgenix-links-grid
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

        $health_frequency = isset( $settings['health_check_frequency'] ) ? \sanitize_key( (string) $settings['health_check_frequency'] ) : 'hourly';
        if ( ! \in_array( $health_frequency, [ 'thirty_minutes', 'hourly', 'twicedaily', 'daily' ], true ) ) {
            $health_frequency = 'hourly';
        }

        $health_timeout = isset( $settings['health_check_timeout'] ) ? (int) $settings['health_check_timeout'] : 8;
        if ( $health_timeout < 3 ) {
            $health_timeout = 3;
        }
        if ( $health_timeout > 30 ) {
            $health_timeout = 30;
        }

        echo '<div class="kitgenix-card">';
        echo '<div class="kitgenix-card-head"><div class="kitgenix-card-head-main"><div class="kitgenix-card-head-text">'
            . '<h2>' . \esc_html__( 'Redirect Settings', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<p>' . \esc_html__( 'Control the short link prefix, redirect type, list size, and uninstall behaviour.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div></div></div>';

        echo '<div class="kitgenix-card-body">';
        echo '<form method="post" action="options.php">';

        \settings_fields( 'kitgenix_affiliate_link_manager_settings_group' );
        \wp_nonce_field( 'kitgenix_affiliate_link_manager_settings_save', 'kitgenix_affiliate_link_manager_settings_nonce' );

        echo '<div class="kitgenix-settings-group">';

        echo '<div class="kitgenix-setting-row">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_prefix">' . \esc_html__( 'Link prefix', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Example: go → https://example.com/go/my-link', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="text" id="kitgenix_affiliate_prefix" name="' . \esc_attr( Settings::OPTION_NAME ) . '[prefix]" value="' . \esc_attr( $prefix ) . '" class="regular-text" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_redirect_status">' . \esc_html__( 'Redirect type', 'kitgenix-affiliate-link-manager' ) . '</label></div>'
            . '<div class="kitgenix-setting-row-control"><select id="kitgenix_affiliate_redirect_status" name="' . \esc_attr( Settings::OPTION_NAME ) . '[redirect_status]">'
            . '<option value="307"' . \selected( $status, 307, false ) . '>' . \esc_html__( '307 (Temporary – recommended)', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="302"' . \selected( $status, 302, false ) . '>' . \esc_html__( '302 (Temporary)', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="301"' . \selected( $status, 301, false ) . '>' . \esc_html__( '301 (Permanent)', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_links_per_page">' . \esc_html__( 'Links per page', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Controls how many links are shown per page in the Links tab (10–200).', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="number" id="kitgenix_affiliate_links_per_page" name="' . \esc_attr( Settings::OPTION_NAME ) . '[links_per_page]" value="' . \esc_attr( (string) $links_per_page ) . '" min="10" max="200" step="1" class="small-text" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_health_check_frequency">' . \esc_html__( 'Health check frequency', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'How often the automated destination health check cron runs.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><select id="kitgenix_affiliate_health_check_frequency" name="' . \esc_attr( Settings::OPTION_NAME ) . '[health_check_frequency]">'
            . '<option value="thirty_minutes"' . \selected( $health_frequency, 'thirty_minutes', false ) . '>' . \esc_html__( 'Every 30 minutes', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="hourly"' . \selected( $health_frequency, 'hourly', false ) . '>' . \esc_html__( 'Hourly (recommended)', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="twicedaily"' . \selected( $health_frequency, 'twicedaily', false ) . '>' . \esc_html__( 'Twice daily', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '<option value="daily"' . \selected( $health_frequency, 'daily', false ) . '>' . \esc_html__( 'Daily', 'kitgenix-affiliate-link-manager' ) . '</option>'
            . '</select></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_health_check_timeout">' . \esc_html__( 'Health check timeout', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'Seconds to wait for a destination to respond before treating it as unreachable. Raise this if you have slow merchant sites.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control"><input type="number" id="kitgenix_affiliate_health_check_timeout" name="' . \esc_attr( Settings::OPTION_NAME ) . '[health_check_timeout]" value="' . \esc_attr( (string) $health_timeout ) . '" min="3" max="30" step="1" class="small-text" /></div>'
            . '</div>';

        echo '<div class="kitgenix-setting-row">'
            . '<div class="kitgenix-setting-row-label"><label for="kitgenix_affiliate_delete_data_on_uninstall">' . \esc_html__( 'Data removal', 'kitgenix-affiliate-link-manager' ) . '</label>'
            . '<p class="kitgenix-setting-row-desc">' . \esc_html__( 'By default, uninstall keeps your links/click data. Enable this only if you want a clean uninstall.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>'
            . '<div class="kitgenix-setting-row-control">'
            . '<label class="kitgenix-toggle">'
            . '<input type="checkbox" id="kitgenix_affiliate_delete_data_on_uninstall" class="kitgenix-toggle-input" name="' . \esc_attr( Settings::OPTION_NAME ) . '[delete_data_on_uninstall]" value="1"' . ( $delete_on_uninstall ? ' checked="checked"' : '' ) . ' />'
            . '<span class="kitgenix-toggle-track"><span class="kitgenix-toggle-thumb"></span></span>'
            . '<span class="kitgenix-toggle-label">' . \esc_html__( 'Delete all affiliate links and click data when the plugin is uninstalled', 'kitgenix-affiliate-link-manager' ) . '</span>'
            . '</label>'
            . '</div>'
            . '</div>';

        echo '</div>'; // .kitgenix-settings-group

        echo '<div class="kitgenix-button-group">';
        \submit_button( \__( 'Save Settings', 'kitgenix-affiliate-link-manager' ), 'primary', 'submit', false );
        echo '</div>';

        echo '</form>';
        echo '</div>'; // .kitgenix-card-body
        echo '</div>'; // .kitgenix-card
    }

    private static function render_support_tab( array $settings, string $prefix ): void {
        $paypal_donate_url = 'https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2';
        $plugin_page_url   = 'https://kitgenix.com/plugins/kitgenix-affiliate-link-manager/';
        $docs_url          = 'https://kitgenix.com/plugins/kitgenix-affiliate-link-manager/documentation/';
        $review_url        = 'https://wordpress.org/support/plugin/kitgenix-affiliate-link-manager/reviews/#new-post';
        $copy_onclick      = "if(window.navigator&&navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(" . \wp_json_encode( $plugin_page_url ) . ");}else{window.prompt(" . \wp_json_encode( \__( 'Copy plugin link:', 'kitgenix-affiliate-link-manager' ) ) . ", " . \wp_json_encode( $plugin_page_url ) . ");}return false;";
        ?>
        <div class="kitgenix-affiliate-link-manager-support-page kitgenix-support-page">
            <div class="kitgenix-card kitgenix-support-hero">
                <div class="kitgenix-support-hero-inner">
                    <span class="kitgenix-support-hero-icon" aria-hidden="true"><?php echo self::icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG markup, no user input. ?></span>
                    <p class="kitgenix-support-hero-eyebrow"><?php echo \esc_html__( 'Support Kitgenix', 'kitgenix-affiliate-link-manager' ); ?></p>
                    <h2><?php echo \esc_html__( 'Help us keep building', 'kitgenix-affiliate-link-manager' ); ?></h2>
                    <p class="kitgenix-support-hero-body"><?php echo \esc_html__( 'Affiliate Link Manager cloaks your affiliate URLs into clean, branded short links, tracks every click, and checks destinations on a schedule so broken redirects get caught before they cost you a sale. Keeping that running smoothly is unpaid work between WordPress and plugin updates, and a donation is what keeps it worth doing.', 'kitgenix-affiliate-link-manager' ); ?></p>
                    <a class="kitgenix-support-hero-button" href="<?php echo \esc_url( $paypal_donate_url ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo self::icon( 'paypal' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG markup, no user input. ?>
                        <?php echo \esc_html__( 'Donate with PayPal', 'kitgenix-affiliate-link-manager' ); ?>
                    </a>
                    <p class="kitgenix-support-hero-caption">
                        <?php echo self::icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG markup, no user input. ?>
                        <?php echo \esc_html__( 'Donations are entirely optional. Kitgenix plugins keep working whether you donate or not.', 'kitgenix-affiliate-link-manager' ); ?>
                    </p>
                </div>

                <div class="kitgenix-support-links">
                    <a class="kitgenix-support-link" href="<?php echo \esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo self::icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG markup, no user input. ?>
                        <?php echo \esc_html__( 'Leave a review', 'kitgenix-affiliate-link-manager' ); ?>
                    </a>
                    <a class="kitgenix-support-link" href="<?php echo \esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo self::icon( 'logs' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG markup, no user input. ?>
                        <?php echo \esc_html__( 'Read the docs', 'kitgenix-affiliate-link-manager' ); ?>
                    </a>
                    <button type="button" class="kitgenix-support-link" onclick="<?php echo \esc_attr( $copy_onclick ); ?>">
                        <?php echo self::icon( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG markup, no user input. ?>
                        <?php echo \esc_html__( 'Copy plugin link', 'kitgenix-affiliate-link-manager' ); ?>
                    </button>
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
            self::log_nonce_invalid( 'link-save' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_save' ) ) {
            self::log_nonce_invalid( 'link-save' );
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
        $clicks  = isset( $_POST['clicks'] ) && $_POST['clicks'] !== '' ? max( 0, (int) $_POST['clicks'] ) : null;
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
                'clicks'          => $clicks,
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
            self::log_nonce_invalid( 'link-delete' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_delete' ) ) {
            self::log_nonce_invalid( 'link-delete' );
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
            self::log_nonce_invalid( 'link-duplicate' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_duplicate' ) ) {
            self::log_nonce_invalid( 'link-duplicate' );
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
            self::log_nonce_invalid( 'link-reset-clicks' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_reset_clicks' ) ) {
            self::log_nonce_invalid( 'link-reset-clicks' );
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
            self::log_nonce_invalid( 'link-bulk' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_bulk' ) ) {
            self::log_nonce_invalid( 'link-bulk' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }

        $action = isset( $_POST['bulk_action'] ) ? \sanitize_key( (string) \wp_unslash( $_POST['bulk_action'] ) ) : '';
        $ids    = isset( $_POST['link_ids'] ) && is_array( $_POST['link_ids'] )
            ? array_map( 'absint', \wp_unslash( $_POST['link_ids'] ) )
            : [];
        $bulk_value = isset( $_POST['bulk_value'] ) ? (string) \sanitize_text_field( \wp_unslash( $_POST['bulk_value'] ) ) : '';

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

        if ( \in_array( $action, [ 'add_group', 'add_tags' ], true ) && $bulk_value === '' ) {
            $message = $action === 'add_group'
                ? \__( 'Please enter a group name.', 'kitgenix-affiliate-link-manager' )
                : \__( 'Please enter at least one tag.', 'kitgenix-affiliate-link-manager' );
            $url  = \add_query_arg( $return_args + [ 'kitgenix_error' => $message ], $base_url );
            $url .= '#kitgenix-tab-links';
            \wp_safe_redirect( $url );
            exit;
        }

        if ( $action === 'add_group' ) {
            $updated = Affiliate_Link_Post_Type::bulk_set_group( $link_ids, $bulk_value );
            $args    = $updated > 0
                ? [ 'kitgenix_bulk_group' => $updated ]
                : [ 'kitgenix_error' => \__( 'No changes were made.', 'kitgenix-affiliate-link-manager' ) ];
            $url  = \add_query_arg( $return_args + $args, $base_url ) . '#kitgenix-tab-links';
            \wp_safe_redirect( $url );
            exit;
        }

        if ( $action === 'add_tags' ) {
            $updated = Affiliate_Link_Post_Type::bulk_add_tags( $link_ids, $bulk_value );
            $args    = $updated > 0
                ? [ 'kitgenix_bulk_tags' => $updated ]
                : [ 'kitgenix_error' => \__( 'No changes were made.', 'kitgenix-affiliate-link-manager' ) ];
            $url  = \add_query_arg( $return_args + $args, $base_url ) . '#kitgenix-tab-links';
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

        $deleted  = 0;
        $reset    = 0;
        $enabled  = 0;
        $disabled = 0;
        $checked  = 0;

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
            } elseif ( $action === 'enable' ) {
                if ( Affiliate_Link_Post_Type::set_enabled( $id, true ) ) {
                    $enabled++;
                }
            } elseif ( $action === 'disable' ) {
                if ( Affiliate_Link_Post_Type::set_enabled( $id, false ) ) {
                    $disabled++;
                }
            } elseif ( $action === 'check_health' ) {
                Health_Checks::run_single_check( $id );
                $checked++;
            }
        }

        $args     = [];
        if ( $deleted > 0 ) {
            $args['kitgenix_bulk_deleted'] = $deleted;
        }
        if ( $reset > 0 ) {
            $args['kitgenix_bulk_reset'] = $reset;
        }
        if ( $enabled > 0 ) {
            $args['kitgenix_bulk_enabled'] = $enabled;
        }
        if ( $disabled > 0 ) {
            $args['kitgenix_bulk_disabled'] = $disabled;
        }
        if ( $checked > 0 ) {
            $args['kitgenix_bulk_checked'] = $checked;
        }

        if ( empty( $args ) ) {
            $args['kitgenix_error'] = \__( 'No changes were made.', 'kitgenix-affiliate-link-manager' );
        }

        $url  = \add_query_arg( $return_args + $args, $base_url );
        $url .= '#kitgenix-tab-links';
        \wp_safe_redirect( $url );
        exit;
    }

    public static function handle_link_check_health(): void {
        if ( ! self::user_can_manage_links() ) {
            \wp_die( \esc_html__( 'Forbidden', 'kitgenix-affiliate-link-manager' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = isset( $_GET['nonce'] ) ? \sanitize_text_field( \wp_unslash( $_GET['nonce'] ) ) : '';
        if ( '' === $nonce ) {
            self::log_nonce_invalid( 'link-check-health' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_check_health' ) ) {
            self::log_nonce_invalid( 'link-check-health' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $link_id = isset( $_GET['link_id'] ) ? (int) $_GET['link_id'] : 0;
        if ( $link_id > 0 ) {
            $post = \get_post( $link_id );
            if ( $post instanceof \WP_Post && $post->post_type === Affiliate_Link_Post_Type::POST_TYPE ) {
                Health_Checks::run_single_check( $link_id );
            }
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $return_state = self::get_links_list_state( $_GET, 'return_' );
        $return_args  = [
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

        $url = \add_query_arg( [ 'kitgenix_health_checked' => 1 ], self::get_links_tab_url( $return_args ) );
        \wp_safe_redirect( $url );
        exit;
    }

    public static function handle_run_health_checks_now(): void {
        if ( ! self::user_can_manage_links() ) {
            \wp_die( \esc_html__( 'Forbidden', 'kitgenix-affiliate-link-manager' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = isset( $_GET['nonce'] ) ? \sanitize_text_field( \wp_unslash( $_GET['nonce'] ) ) : '';
        if ( '' === $nonce ) {
            self::log_nonce_invalid( 'link-check-health-all' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_run_health_checks_now' ) ) {
            self::log_nonce_invalid( 'link-check-health-all' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }

        $checked = Health_Checks::run_check_for_all();

        Event_Log::record(
            'health-check',
            'success',
            sprintf(
                /* translators: %d: number of links checked. */
                \__( 'An admin manually ran destination health checks for %d links.', 'kitgenix-affiliate-link-manager' ),
                $checked
            )
        );

        $url = \add_query_arg( [ 'kitgenix_health_check_all' => $checked ], self::get_links_tab_url() );
        \wp_safe_redirect( $url );
        exit;
    }

    /**
     * Parse an uploaded CSV or JSON import file into row arrays, mirroring the
     * WP-CLI import reader so the admin UI, REST API, and WP-CLI all end up
     * feeding the same Affiliate_Link_Post_Type::import_links() normalization.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function parse_uploaded_import_rows( string $tmp_path, string $format ): array {
        if ( ! \is_readable( $tmp_path ) ) {
            return [];
        }

        if ( $format === 'json' ) {
            $decoded = \json_decode( (string) \file_get_contents( $tmp_path ), true );
            if ( isset( $decoded['rows'] ) && is_array( $decoded['rows'] ) ) {
                return $decoded['rows'];
            }
            if ( isset( $decoded['links'] ) && is_array( $decoded['links'] ) ) {
                return $decoded['links'];
            }
            if ( is_array( $decoded ) ) {
                return $decoded;
            }

            return [];
        }

        try {
            $reader = new \SplFileObject( $tmp_path, 'rb' );
        } catch ( \RuntimeException $exception ) {
            return [];
        }

        $reader->setFlags( \SplFileObject::READ_CSV );
        $reader->setCsvControl( ',', '"', '\\' );
        $headers = $reader->fgetcsv();
        if ( ! is_array( $headers ) || $headers === [ null ] ) {
            return [];
        }

        // Deliberately NOT a foreach() here: SplFileObject's Iterator::rewind() resets the
        // file pointer to the very first line, which would re-read the header row above as
        // a data row on the first iteration. Reading the rest of the file via fgetcsv() in a
        // while loop keeps the pointer where it already is, immediately after the header.
        $rows = [];
        while ( ! $reader->eof() ) {
            $row = $reader->fgetcsv();
            if ( ! is_array( $row ) || $row === [ null ] ) {
                continue;
            }

            // A row with more (or fewer) fields than the header row is possible with a
            // hand-edited or malformed CSV; array_combine() requires equal counts, so pad
            // and truncate to the header width rather than letting a mismatch throw.
            $normalized_row = array_slice( array_pad( $row, count( $headers ), '' ), 0, count( $headers ) );
            $rows[]         = array_combine( $headers, $normalized_row );
        }

        return $rows;
    }

    public static function handle_link_import(): void {
        if ( ! self::user_can_manage_links() ) {
            \wp_die( \esc_html__( 'Forbidden', 'kitgenix-affiliate-link-manager' ) );
        }

        $nonce = isset( $_POST['kitgenix_affiliate_link_import_nonce'] )
            ? \sanitize_text_field( \wp_unslash( $_POST['kitgenix_affiliate_link_import_nonce'] ) )
            : '';
        if ( '' === $nonce ) {
            self::log_nonce_invalid( 'link-import' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_import' ) ) {
            self::log_nonce_invalid( 'link-import' );
            \wp_die( \esc_html__( 'Invalid nonce', 'kitgenix-affiliate-link-manager' ) );
        }

        $match_existing = isset( $_POST['match_existing'] ) ? \sanitize_key( \wp_unslash( (string) $_POST['match_existing'] ) ) : 'slug';
        if ( ! \in_array( $match_existing, [ 'slug', 'id', 'none' ], true ) ) {
            $match_existing = 'slug';
        }

        $base_url = self::get_links_tab_url() . '#kitgenix-tab-links';

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $_FILES is validated below (upload error, extension, is_uploaded_file()), not used directly.
        $file = $_FILES['import_file'] ?? null;
        if ( ! is_array( $file ) || (int) ( $file['error'] ?? \UPLOAD_ERR_NO_FILE ) === \UPLOAD_ERR_NO_FILE ) {
            \wp_safe_redirect( \add_query_arg( [ 'kitgenix_error' => \__( 'Please choose a CSV or JSON file to import.', 'kitgenix-affiliate-link-manager' ) ], $base_url ) );
            exit;
        }

        if ( (int) $file['error'] !== \UPLOAD_ERR_OK ) {
            \wp_safe_redirect( \add_query_arg( [ 'kitgenix_error' => \__( 'The uploaded file could not be read.', 'kitgenix-affiliate-link-manager' ) ], $base_url ) );
            exit;
        }

        // tmp_name is a server-generated path (validated below via is_uploaded_file()), not raw user input.
        $tmp_name      = (string) $file['tmp_name'];
        $original_name = \sanitize_file_name( \wp_unslash( (string) ( $file['name'] ?? '' ) ) );
        $format        = \strtolower( (string) \pathinfo( $original_name, PATHINFO_EXTENSION ) );

        if ( ! \in_array( $format, [ 'csv', 'json' ], true ) || ! \is_uploaded_file( $tmp_name ) ) {
            \wp_safe_redirect( \add_query_arg( [ 'kitgenix_error' => \__( 'Please upload a .csv or .json file.', 'kitgenix-affiliate-link-manager' ) ], $base_url ) );
            exit;
        }

        $rows = self::parse_uploaded_import_rows( $tmp_name, $format );
        if ( empty( $rows ) ) {
            \wp_safe_redirect( \add_query_arg( [ 'kitgenix_error' => \__( 'No rows were found in the uploaded file.', 'kitgenix-affiliate-link-manager' ) ], $base_url ) );
            exit;
        }

        $result = Affiliate_Link_Post_Type::import_links( $rows, $match_existing );

        Event_Log::record(
            'link-import',
            (int) $result['failed'] > 0 ? 'warning' : 'success',
            sprintf(
                /* translators: 1: created count, 2: updated count, 3: failed count. */
                \__( 'Admin UI import finished: %1$d created, %2$d updated, %3$d failed.', 'kitgenix-affiliate-link-manager' ),
                (int) $result['created'],
                (int) $result['updated'],
                (int) $result['failed']
            )
        );

        $url = \add_query_arg(
            [
                'kitgenix_import_created' => (int) $result['created'],
                'kitgenix_import_updated' => (int) $result['updated'],
                'kitgenix_import_failed'  => (int) $result['failed'],
                'kitgenix_import_skipped' => isset( $result['skipped'] ) ? (int) $result['skipped'] : 0,
            ],
            $base_url
        );
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
            self::notice( __( 'Event log cleared.', 'kitgenix-affiliate-link-manager' ), 'success' );
        }

        $clear_url = \wp_nonce_url(
            \admin_url( 'admin-post.php?action=kitgenix_affiliate_link_manager_clear_event_log' ),
            'kitgenix_affiliate_link_manager_clear_event_log'
        );

        $log_entries = Event_Log::get_raw_log();
        $time_format = (string) \get_option( 'date_format' ) . ' ' . (string) \get_option( 'time_format' );

        echo '<div class="kitgenix-card">';
        echo '<div class="kitgenix-card-head"><div>'
            . '<h2 class="kitgenix-support-subheading">' . \esc_html__( 'Activity Log', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<p class="description">' . \esc_html__( 'A record of recent plugin events. Entries show the timestamp, context, outcome, and a plain-English note. IP addresses and sensitive data are never stored here.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div>';
        echo '<div class="kitgenix-card-head-actions">'
            . '<a href="' . \esc_url( $clear_url ) . '" class="button button-secondary" onclick="return confirm(\'' . \esc_js( \__( 'Clear all log entries?', 'kitgenix-affiliate-link-manager' ) ) . '\')">' . \esc_html__( 'Clear log', 'kitgenix-affiliate-link-manager' ) . '</a>'
            . '</div>';
        echo '</div>'; // .kitgenix-card-head

        echo '<div class="kitgenix-card-body">';

        if ( empty( $log_entries ) ) {
            echo '<div class="kitgenix-empty-state">'
                . '<span class="kitgenix-empty-state-icon" aria-hidden="true">' . self::icon( 'logs' ) . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG markup, no user input.
                . '<h3 class="kitgenix-empty-state-title">' . \esc_html__( 'No events logged yet', 'kitgenix-affiliate-link-manager' ) . '</h3>'
                . '<p class="kitgenix-empty-state-desc">' . \esc_html__( 'Activity will appear here as it happens.', 'kitgenix-affiliate-link-manager' ) . '</p>'
                . '</div>';
        } else {
            echo '<div class="kitgenix-search-bar"><div class="kitgenix-search-bar-input">'
                . '<span class="kitgenix-search-bar-icon" aria-hidden="true">' . self::icon( 'search' ) . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static trusted SVG markup, no user input.
                . '<input type="search" placeholder="' . \esc_attr__( 'Search log…', 'kitgenix-affiliate-link-manager' ) . '" data-kitgenix-table-search data-kitgenix-table-search-target="#kalm-event-log-table" />'
                . '</div></div>';

            echo '<div class="kitgenix-table-wrap" id="kalm-event-log-table" data-kitgenix-table-paginate="25">';
            echo '<table class="kitgenix-table"><thead><tr>'
                . '<th>' . \esc_html__( 'Time', 'kitgenix-affiliate-link-manager' ) . '</th>'
                . '<th>' . \esc_html__( 'Context', 'kitgenix-affiliate-link-manager' ) . '</th>'
                . '<th>' . \esc_html__( 'Outcome', 'kitgenix-affiliate-link-manager' ) . '</th>'
                . '<th>' . \esc_html__( 'Note', 'kitgenix-affiliate-link-manager' ) . '</th>'
                . '</tr></thead><tbody>';

            foreach ( array_reverse( $log_entries ) as $entry ) {
                $time    = isset( $entry['time'] ) ? (int) $entry['time'] : 0;
                $when    = $time ? (string) \wp_date( $time_format, $time ) : \esc_html__( 'Unknown time', 'kitgenix-affiliate-link-manager' );
                $context = (string) ( $entry['context'] ?? '' );
                $outcome = (string) ( $entry['outcome'] ?? '' );
                $note    = (string) ( $entry['note'] ?? '' );

                $outcome_l = \strtolower( $outcome );
                if ( in_array( $outcome_l, [ 'success', 'ok' ], true ) ) {
                    $badge_class = 'success';
                } elseif ( in_array( $outcome_l, [ 'error', 'failed', 'failure' ], true ) ) {
                    $badge_class = 'danger';
                } else {
                    $badge_class = 'neutral';
                }

                echo '<tr data-kitgenix-table-row>'
                    . '<td>' . \esc_html( $when ) . '</td>'
                    . '<td><code>' . \esc_html( $context ) . '</code></td>'
                    . '<td><span class="kitgenix-badge ' . \esc_attr( $badge_class ) . '">' . \esc_html( $outcome ) . '</span></td>'
                    . '<td>' . \esc_html( $note ) . '</td>'
                    . '</tr>';
            }

            echo '</tbody></table>';
            echo '<div class="kitgenix-empty-state" data-kitgenix-table-empty style="display:none;">'
                . '<p class="kitgenix-empty-state-title">' . \esc_html__( 'No matching log entries', 'kitgenix-affiliate-link-manager' ) . '</p>'
                . '</div>';
            echo '</div>'; // .kitgenix-table-wrap
        }

        echo '</div>'; // .kitgenix-card-body
        echo '</div>'; // .kitgenix-card

        self::render_log_code_reference_table();
    }

    /**
     * Reference table explaining each structured event code – lets a non-technical site
     * owner tell a genuine problem (needs action) apart from a likely false positive.
     */
    private static function render_log_code_reference_table(): void {
        $rows = [
            [ 'settings-saved', __( 'Plugin settings were changed by an admin.', 'kitgenix-affiliate-link-manager' ), __( 'None – informational only.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'cached-or-expired-page', __( 'A security token on an admin action (save, delete, duplicate, etc.) was missing or stale. Most common on a cached admin page or a form left open a long time.', 'kitgenix-affiliate-link-manager' ), __( 'Usually not a problem – NOT necessarily an attack. If it repeats for one user, ask them to refresh the page before retrying.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'link-healthy', __( 'A link\'s destination responded successfully during the hourly health check.', 'kitgenix-affiliate-link-manager' ), __( 'None.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'link-redirect', __( 'A link\'s destination is reachable but responded via one or more redirects before loading.', 'kitgenix-affiliate-link-manager' ), __( 'Optional: point the link directly at the final destination URL to skip the redirect hop.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'link-broken', __( 'A link\'s destination returned a real error response (e.g. 404/410) or actively refused the connection.', 'kitgenix-affiliate-link-manager' ), __( 'Update or remove the destination URL – the merchant likely moved, removed, or renamed the page.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'link-redirect-error', __( 'A link\'s destination entered a redirect loop, exceeded the redirect limit, or redirected to an invalid target.', 'kitgenix-affiliate-link-manager' ), __( 'Check the destination and fallback URLs for a misconfigured redirect and update them.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'network-error', __( 'The health check could not reach a destination (DNS lookup failure, timeout, or another generic connectivity error).', 'kitgenix-affiliate-link-manager' ), __( 'Usually not a problem – often a transient hiccup. Only investigate if it keeps repeating across several hourly checks.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'request-blocked', __( 'WordPress\'s own outbound request safety check refused to contact the URL (for example, it resolves to a private or loopback address).', 'kitgenix-affiliate-link-manager' ), __( 'Usually not a problem with the destination itself. Only investigate if it is meant to be a normal public URL.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'no-response', __( 'No response was recorded during a link\'s health check.', 'kitgenix-affiliate-link-manager' ), __( 'None – will retry on the next hourly check.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'no-destination', __( 'A link has no destination URL configured to check.', 'kitgenix-affiliate-link-manager' ), __( 'Add a destination URL to the link.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'validation-error', __( 'A link was saved with a missing or invalid field (name, URL, dates, or rotation destinations).', 'kitgenix-affiliate-link-manager' ), __( 'Fix the highlighted field and save again.', 'kitgenix-affiliate-link-manager' ) ],
            [ 'database-error', __( 'A link could not be saved because of a database issue (often emoji/special characters, or a database insert error).', 'kitgenix-affiliate-link-manager' ), __( 'Remove special characters from the name, or check your database error log / contact your host if this repeats.', 'kitgenix-affiliate-link-manager' ) ],
        ];

        echo '<div class="kitgenix-card">';
        echo '<div class="kitgenix-card-head"><div>'
            . '<h2 class="kitgenix-support-subheading">' . \esc_html__( 'Event code reference', 'kitgenix-affiliate-link-manager' ) . '</h2>'
            . '<p class="description">' . \esc_html__( 'What each logged category means, and whether it needs your attention.', 'kitgenix-affiliate-link-manager' ) . '</p>'
            . '</div></div>';

        echo '<div class="kitgenix-card-body">';
        echo '<div class="kitgenix-table-wrap"><table class="kitgenix-table"><thead><tr>'
            . '<th>' . \esc_html__( 'Category', 'kitgenix-affiliate-link-manager' ) . '</th>'
            . '<th>' . \esc_html__( 'What it means', 'kitgenix-affiliate-link-manager' ) . '</th>'
            . '<th>' . \esc_html__( 'Action needed?', 'kitgenix-affiliate-link-manager' ) . '</th>'
            . '</tr></thead><tbody>';

        foreach ( $rows as $row ) {
            echo '<tr>'
                . '<td><code>' . \esc_html( $row[0] ) . '</code></td>'
                . '<td>' . \esc_html( $row[1] ) . '</td>'
                . '<td>' . \esc_html( $row[2] ) . '</td>'
                . '</tr>';
        }

        echo '</tbody></table></div>'; // .kitgenix-table-wrap
        echo '</div>'; // .kitgenix-card-body
        echo '</div>'; // .kitgenix-card
    }

    public static function handle_clear_event_log(): void {
        if ( ! self::user_can_manage_links() ) {
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

    /**
     * Neutralize CSV/spreadsheet formula injection: a cell that opens with =, +, -, @,
     * or a tab/CR is treated as a formula by Excel/Sheets when the file is opened. Prefixing
     * with a literal apostrophe keeps the visible value intact while forcing text interpretation.
     */
    private static function neutralize_csv_formula( string $value ): string {
        if ( $value !== '' && false !== strpbrk( $value[0], "=+-@\t\r" ) ) {
            return "'" . $value;
        }

        return $value;
    }

    private static function build_csv_row( array $fields ): string {
        $escaped_fields = [];

        foreach ( $fields as $field ) {
            $value = self::neutralize_csv_formula( (string) $field );
            $value = str_replace( '"', '""', $value );
            if ( false !== strpbrk( $value, ",\r\n\"" ) ) {
                $value = '"' . $value . '"';
            }
            $escaped_fields[] = $value;
        }

        return implode( ',', $escaped_fields ) . "\r\n";
    }
}
