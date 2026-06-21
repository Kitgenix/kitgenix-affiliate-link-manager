<?php
namespace KitgenixAffiliateLinkManager\Admin;

defined( 'ABSPATH' ) || exit;

use KitgenixAffiliateLinkManager\Core\Settings;

class Admin_Options {

    public static function init(): void {
        \add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_settings(): void {
        \register_setting(
            'kitgenix_affiliate_link_manager_settings_group',
            Settings::OPTION_NAME,
            [
                'type'              => 'array',
                'description'       => \__( 'Kitgenix Affiliate Link Manager Settings', 'kitgenix-affiliate-link-manager' ),
                'sanitize_callback' => [ __CLASS__, 'sanitize' ],
                'show_in_rest'      => false,
                'default'           => [],
            ]
        );
    }

    public static function sanitize( $settings ): array {
        $nonce = '';
        if ( isset( $_POST['kitgenix_affiliate_link_manager_settings_nonce'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below
            $nonce = \sanitize_text_field( \wp_unslash( $_POST['kitgenix_affiliate_link_manager_settings_nonce'] ) );
        }
        if ( empty( $nonce ) || ! \wp_verify_nonce( $nonce, 'kitgenix_affiliate_link_manager_settings_save' ) ) {
            return (array) \get_option( Settings::OPTION_NAME, [] );
        }

        $settings = \is_array( $settings ) ? $settings : [];
        $clean    = [];

        $prefix = isset( $settings['prefix'] ) ? (string) $settings['prefix'] : 'go';
        $prefix = trim( $prefix );
        $prefix = trim( $prefix, "/" );
        $prefix = \sanitize_title_with_dashes( $prefix );
        if ( $prefix === '' ) {
            $prefix = 'go';
        }
        $clean['prefix'] = $prefix;

        // Warn for prefixes that commonly conflict with WordPress routes or existing pages.
        $reserved = [
            'wp-admin',
            'wp-json',
            'wp-login',
            'xmlrpc',
            'feed',
        ];
        if ( \in_array( $prefix, $reserved, true ) ) {
            \add_settings_error(
                Settings::OPTION_NAME,
                'kitgenix_affiliate_prefix_reserved',
                \__( 'Warning: that prefix may conflict with WordPress core URLs. If redirects do not work, choose a different prefix and re-save Permalinks.', 'kitgenix-affiliate-link-manager' ),
                'warning'
            );
        }

        $page_conflict = \get_page_by_path( $prefix, OBJECT, 'page' );
        if ( $page_conflict instanceof \WP_Post ) {
            \add_settings_error(
                Settings::OPTION_NAME,
                'kitgenix_affiliate_prefix_page_conflict',
                \__( 'Warning: a Page already uses that slug. The redirect prefix may conflict with existing site URLs.', 'kitgenix-affiliate-link-manager' ),
                'warning'
            );
        }

        $status = isset( $settings['redirect_status'] ) ? (int) $settings['redirect_status'] : 307;
        $clean['redirect_status'] = \in_array( $status, [ 301, 302, 307 ], true ) ? $status : 307;

        $per_page = isset( $settings['links_per_page'] ) ? (int) $settings['links_per_page'] : 50;
        if ( $per_page < 10 ) {
            $per_page = 10;
        }
        if ( $per_page > 200 ) {
            $per_page = 200;
        }
        $clean['links_per_page'] = $per_page;

        $clean['delete_data_on_uninstall'] = ! empty( $settings['delete_data_on_uninstall'] ) ? 1 : 0;

        return $clean;
    }
}
