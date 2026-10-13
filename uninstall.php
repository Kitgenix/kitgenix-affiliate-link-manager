<?php
/**
 * Uninstall handler.
 *
 * @package KitgenixAffiliateLinkManager
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Remove plugin-only settings and short-lived transients.
// By default, we deliberately do NOT delete affiliate link posts/redirect data.

$kitgenix_affiliate_should_delete_data = false;

$kitgenix_affiliate_settings = get_option( 'kitgenix_affiliate_link_manager_settings', [] );
if ( is_array( $kitgenix_affiliate_settings ) && ! empty( $kitgenix_affiliate_settings['delete_data_on_uninstall'] ) ) {
	$kitgenix_affiliate_should_delete_data = true;
}

if ( ! $kitgenix_affiliate_should_delete_data && function_exists( 'get_site_option' ) ) {
	$kitgenix_affiliate_network_settings = get_site_option( 'kitgenix_affiliate_link_manager_settings', [] );
	if ( is_array( $kitgenix_affiliate_network_settings ) && ! empty( $kitgenix_affiliate_network_settings['delete_data_on_uninstall'] ) ) {
		$kitgenix_affiliate_should_delete_data = true;
	}
}

if ( $kitgenix_affiliate_should_delete_data ) {
	$kitgenix_affiliate_delete_links_for_current_site = static function (): void {
		$post_type = 'kitgenix_aff_link';

		do {
			$kitgenix_affiliate_ids = get_posts(
				[
					'post_type'        => $post_type,
					'post_status'      => 'any',
					'fields'           => 'ids',
					'posts_per_page'   => 100,
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'no_found_rows'    => true,
					'suppress_filters' => false,
				]
			);

			if ( empty( $kitgenix_affiliate_ids ) || ! is_array( $kitgenix_affiliate_ids ) ) {
				break;
			}

			foreach ( $kitgenix_affiliate_ids as $id ) {
				wp_delete_post( (int) $id, true );
			}
		} while ( true );
	};

	if ( is_multisite() && function_exists( 'get_sites' ) && function_exists( 'switch_to_blog' ) ) {
		$kitgenix_affiliate_sites = get_sites( [ 'fields' => 'ids' ] );
		if ( is_array( $kitgenix_affiliate_sites ) ) {
			foreach ( $kitgenix_affiliate_sites as $blog_id ) {
				switch_to_blog( (int) $blog_id );
				$kitgenix_affiliate_delete_links_for_current_site();
				global $wpdb;
				if ( isset( $wpdb ) && is_object( $wpdb ) && isset( $wpdb->prefix ) ) {
					$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kitgenix_aff_click_stats" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				}
				delete_option( 'kitgenix_affiliate_link_manager_total_clicks' );
				restore_current_blog();
			}
		}
	} else {
		$kitgenix_affiliate_delete_links_for_current_site();
		global $wpdb;
		if ( isset( $wpdb ) && is_object( $wpdb ) && isset( $wpdb->prefix ) ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kitgenix_aff_click_stats" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		}
	}
}

// Plugin-only settings/log/transients are per-site data, so on multisite this must run once
// per subsite (not just whichever site uninstall.php happens to execute in) or every other
// subsite is left with orphaned rows in its own wp_options table.
$kitgenix_affiliate_delete_site_data_for_current_site = static function (): void {
	delete_option( 'kitgenix_affiliate_link_manager_settings' );
	delete_option( 'kitgenix_affiliate_link_manager_total_clicks' );
	delete_option( 'kitgenix_affiliate_link_manager_analytics_db_version' );
	delete_option( 'kitgenix_affiliate_link_manager_health_summary' );
	delete_option( 'kitgenix_affiliate_link_manager_health_cursor' );
	delete_option( 'kitgenix_affiliate_link_manager_event_log' );
	delete_option( 'kitgenix_affiliate_link_manager_log_retention_days' );

	delete_transient( 'kitgenix_affiliate_link_manager_do_activation_redirect' );
	delete_transient( 'kitgenix_affiliate_link_manager_do_rewrite_flush' );
	delete_transient( 'kitgenix_affiliate_link_manager_overview_snapshot' );
};

if ( is_multisite() && function_exists( 'get_sites' ) && function_exists( 'switch_to_blog' ) ) {
	$kitgenix_affiliate_all_sites = get_sites( [ 'fields' => 'ids' ] );
	if ( is_array( $kitgenix_affiliate_all_sites ) ) {
		foreach ( $kitgenix_affiliate_all_sites as $kitgenix_affiliate_blog_id ) {
			switch_to_blog( (int) $kitgenix_affiliate_blog_id );
			$kitgenix_affiliate_delete_site_data_for_current_site();
			restore_current_blog();
		}
	}
} else {
	$kitgenix_affiliate_delete_site_data_for_current_site();
}

// Network-wide fallbacks: the plugin never writes these via update_site_option(), but clean
// them up defensively in case a network admin ever set them directly (e.g. via WP-CLI).
delete_site_option( 'kitgenix_affiliate_link_manager_settings' );
delete_site_option( 'kitgenix_affiliate_link_manager_analytics_db_version' );
delete_site_option( 'kitgenix_affiliate_link_manager_health_summary' );
delete_site_option( 'kitgenix_affiliate_link_manager_health_cursor' );
