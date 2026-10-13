<?php
namespace KitgenixAffiliateLinkManager\Core;

defined( 'ABSPATH' ) || exit;

class Loader {

    public static function init(): void {
        Affiliate_Link_Post_Type::init();
        Analytics::init();
        Redirector::init();
        Health_Checks::init();
        Event_Log::init();
        Rest_API::init();

        if ( class_exists( __NAMESPACE__ . '\\CLI_Command' ) ) {
            CLI_Command::init();
        }

        // Flush rewrites when prefix changes.
        \add_action( 'update_option_' . Settings::OPTION_NAME, [ __CLASS__, 'maybe_flush_rewrites' ], 10, 2 );
    }

    public static function maybe_flush_rewrites( $old_value, $value ): void {
        $old = \is_array( $old_value ) ? $old_value : [];
        $new = \is_array( $value ) ? $value : [];

        $old_prefix = isset( $old['prefix'] ) ? \sanitize_title_with_dashes( (string) $old['prefix'] ) : 'go';
        $new_prefix = isset( $new['prefix'] ) ? \sanitize_title_with_dashes( (string) $new['prefix'] ) : 'go';

        if ( $old_prefix !== $new_prefix ) {
            \flush_rewrite_rules();
        }
    }
}
