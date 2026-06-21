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

delete_option( 'kitgenix_affiliate_link_manager_settings' );
delete_site_option( 'kitgenix_affiliate_link_manager_settings' );
delete_option( 'kitgenix_affiliate_link_manager_total_clicks' );
delete_option( 'kitgenix_affiliate_link_manager_analytics_db_version' );
delete_site_option( 'kitgenix_affiliate_link_manager_analytics_db_version' );
delete_option( 'kitgenix_affiliate_link_manager_health_summary' );
delete_option( 'kitgenix_affiliate_link_manager_health_cursor' );
delete_site_option( 'kitgenix_affiliate_link_manager_health_summary' );
delete_site_option( 'kitgenix_affiliate_link_manager_health_cursor' );
delete_option( 'kitgenix_affiliate_link_manager_event_log' );

delete_transient( 'kitgenix_affiliate_link_manager_do_activation_redirect' );
delete_transient( 'kitgenix_affiliate_link_manager_do_rewrite_flush' );
