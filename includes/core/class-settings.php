<?php
namespace KitgenixAffiliateLinkManager\Core;

defined( 'ABSPATH' ) || exit;

class Settings {

    public const OPTION_NAME = 'kitgenix_affiliate_link_manager_settings';

    public static function get_settings(): array {
        $settings = \get_option( self::OPTION_NAME, [] );
        return \is_array( $settings ) ? $settings : [];
    }

    public static function get_prefix(): string {
        $settings = self::get_settings();
        $prefix   = isset( $settings['prefix'] ) ? (string) $settings['prefix'] : 'go';
        $prefix   = trim( $prefix );
        $prefix   = trim( $prefix, "/" );

        $prefix = \sanitize_title_with_dashes( $prefix );
        if ( $prefix === '' ) {
            $prefix = 'go';
        }

        return $prefix;
    }

    public static function get_redirect_status(): int {
        $settings = self::get_settings();
        $status   = isset( $settings['redirect_status'] ) ? (int) $settings['redirect_status'] : 307;
        return \in_array( $status, [ 301, 302, 307 ], true ) ? $status : 307;
    }

    public static function get_delete_data_on_uninstall(): bool {
        $settings = self::get_settings();
        return ! empty( $settings['delete_data_on_uninstall'] );
    }

    public static function get_links_per_page(): int {
        $settings = self::get_settings();
        $per_page = isset( $settings['links_per_page'] ) ? (int) $settings['links_per_page'] : 50;

        if ( $per_page < 10 ) {
            $per_page = 10;
        }
        if ( $per_page > 200 ) {
            $per_page = 200;
        }

        return $per_page;
    }

    public static function get_health_check_frequency(): string {
        $settings  = self::get_settings();
        $frequency = isset( $settings['health_check_frequency'] ) ? \sanitize_key( (string) $settings['health_check_frequency'] ) : 'hourly';

        return \in_array( $frequency, [ 'thirty_minutes', 'hourly', 'twicedaily', 'daily' ], true ) ? $frequency : 'hourly';
    }

    public static function get_health_check_timeout(): int {
        $settings = self::get_settings();
        $timeout  = isset( $settings['health_check_timeout'] ) ? (int) $settings['health_check_timeout'] : 8;

        if ( $timeout < 3 ) {
            $timeout = 3;
        }
        if ( $timeout > 30 ) {
            $timeout = 30;
        }

        return $timeout;
    }

    public static function build_short_url( string $slug ): string {
        $slug   = \sanitize_title( $slug );
        $prefix = self::get_prefix();
        $path = $prefix . '/' . $slug;
        if ( function_exists( 'user_trailingslashit' ) ) {
            $path = \user_trailingslashit( $path );
        }
        return \home_url( '/' . ltrim( $path, '/' ) );
    }
}
