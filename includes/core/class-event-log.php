<?php
namespace KitgenixAffiliateLinkManager\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Simple per-plugin activity log.
 *
 * Records admin actions (settings saves, link CRUD, health-check results) to a
 * capped WP option so they are visible in the admin Log tab without any external
 * logging infrastructure.
 *
 * Storage format (per entry):
 *   [ 'time' => int, 'context' => string, 'outcome' => string, 'note' => string, 'code' => string ]
 */
final class Event_Log {

    private const OPTION_KEY  = 'kitgenix_affiliate_link_manager_event_log';
    private const MAX_ENTRIES = 100;

    /**
     * Record an event to the log.
     *
     * @param string $context  Short slug describing what happened (e.g. 'settings-saved', 'link-created').
     * @param string $outcome  'success', 'error', or any other short outcome label.
     * @param string $note     Optional plain-English detail.
     * @param string $code     Optional structured error/status code, mapped via get_category_and_note().
     */
    public static function record( string $context, string $outcome, string $note = '', string $code = '' ): void {
        $log   = self::get_raw_log();
        $log[] = [
            'time'    => time(),
            'context' => sanitize_text_field( $context ),
            'outcome' => sanitize_text_field( $outcome ),
            'note'    => sanitize_text_field( $note ),
            'code'    => sanitize_key( $code ),
        ];

        if ( count( $log ) > self::MAX_ENTRIES ) {
            $log = array_slice( $log, -self::MAX_ENTRIES );
        }

        update_option( self::OPTION_KEY, $log, false );
    }

    /**
     * Return all stored entries (oldest first).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function get_raw_log(): array {
        $log = get_option( self::OPTION_KEY, [] );
        return is_array( $log ) ? $log : [];
    }

    /** Delete all stored entries. */
    public static function clear(): void {
        delete_option( self::OPTION_KEY );
    }

    /**
     * Return the log as a formatted multi-line string for the admin textarea.
     */
    public static function get_log_text(): string {
        $entries = self::get_raw_log();
        if ( empty( $entries ) ) {
            return __( 'No recent events recorded yet.', 'kitgenix-affiliate-link-manager' );
        }

        $format = (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' );
        $lines  = [ '# Columns: timestamp | context | outcome | code | note' ];

        foreach ( array_reverse( $entries ) as $entry ) {
            $time = isset( $entry['time'] ) ? (int) $entry['time'] : 0;
            if ( function_exists( 'wp_date' ) ) {
                $when = $time ? (string) wp_date( $format, $time ) : '';
            } else {
                $when = $time ? (string) date_i18n( $format, $time ) : '';
            }

            $lines[] = sprintf(
                '%1$s | %2$s | %3$s | %4$s | %5$s',
                $when ?: __( 'Unknown time', 'kitgenix-affiliate-link-manager' ),
                (string) ( $entry['context'] ?? '' ),
                (string) ( $entry['outcome'] ?? '' ),
                (string) ( $entry['code']    ?? '' ),
                (string) ( $entry['note']    ?? '' )
            );
        }

        return implode( "\n", $lines );
    }

    /**
     * Map a structured event code to a short category slug and a plain-English hint note.
     *
     * Distinguishes genuine problems (need action) from likely false positives, e.g. a stale
     * nonce on a cached admin page or a transient network hiccup during a link health check.
     *
     * @return array{category: string, note: string, action: string}
     */
    public static function get_category_and_note( string $code ): array {
        $map = [
            'settings-saved' => [
                'category' => 'settings-saved',
                'note'     => __( 'Plugin settings were changed by an admin.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'None – informational only.', 'kitgenix-affiliate-link-manager' ),
            ],

            // Likely false positive – cached/stale admin page
            'nonce_invalid' => [
                'category' => 'cached-or-expired-page',
                'note'     => __( 'A security token on an admin action was missing or stale. Most common on a cached admin page, a form left open a long time, or a double submit.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Usually not a problem – NOT necessarily an attack. If it repeats for the same user, ask them to refresh the page before retrying.', 'kitgenix-affiliate-link-manager' ),
            ],

            // Link health checks
            'health_link_ok' => [
                'category' => 'link-healthy',
                'note'     => __( 'The destination responded successfully.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'None.', 'kitgenix-affiliate-link-manager' ),
            ],
            'health_link_redirect' => [
                'category' => 'link-redirect',
                'note'     => __( 'The destination is reachable but responded via one or more redirects before loading.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Optional: point the link directly at the final destination URL to skip the redirect hop.', 'kitgenix-affiliate-link-manager' ),
            ],
            'health_link_dead' => [
                'category' => 'link-broken',
                'note'     => __( 'The destination returned a real error response (e.g. 404/410) or actively refused the connection.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Update or remove the destination URL – the merchant likely moved, removed, or renamed the page.', 'kitgenix-affiliate-link-manager' ),
            ],
            'health_redirect_error' => [
                'category' => 'link-redirect-error',
                'note'     => __( 'The destination entered a redirect loop, exceeded the redirect limit, or redirected to an invalid target.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Check the destination and fallback URLs for a misconfigured redirect and update them.', 'kitgenix-affiliate-link-manager' ),
            ],
            'health_network_error' => [
                'category' => 'network-error',
                'note'     => __( 'The health check could not reach the destination (DNS lookup failure, timeout, or another generic connectivity error).', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Usually not a problem – often a transient hiccup on this server or the destination host. Only investigate if it keeps repeating across several hourly checks.', 'kitgenix-affiliate-link-manager' ),
            ],
            'health_check_blocked' => [
                'category' => 'request-blocked',
                'note'     => __( 'WordPress\'s own outbound request safety check refused to contact this URL (for example, it resolves to a private or loopback address).', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Usually not a problem with the destination itself. Only investigate if the destination is meant to be a normal public URL.', 'kitgenix-affiliate-link-manager' ),
            ],
            'health_check_unknown' => [
                'category' => 'no-response',
                'note'     => __( 'No response was recorded during the health check.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'None – will retry on the next hourly check.', 'kitgenix-affiliate-link-manager' ),
            ],
            'health_check_no_destinations' => [
                'category' => 'no-destination',
                'note'     => __( 'The link has no destination URL configured to check.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Add a destination URL to this link.', 'kitgenix-affiliate-link-manager' ),
            ],

            // Link save/validation errors
            'kitgenix_affiliate_missing_title' => [
                'category' => 'validation-error',
                'note'     => __( 'A link was submitted without a name.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Enter a name for the link and save again.', 'kitgenix-affiliate-link-manager' ),
            ],
            'kitgenix_affiliate_invalid_url' => [
                'category' => 'validation-error',
                'note'     => __( 'A link was submitted with a missing or invalid destination URL.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Enter a valid destination URL and save again.', 'kitgenix-affiliate-link-manager' ),
            ],
            'kitgenix_affiliate_invalid_start' => [
                'category' => 'validation-error',
                'note'     => __( 'A link was submitted with an invalid start date/time.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Enter a valid start date and time and save again.', 'kitgenix-affiliate-link-manager' ),
            ],
            'kitgenix_affiliate_invalid_expiry' => [
                'category' => 'validation-error',
                'note'     => __( 'A link was submitted with an invalid expiry date/time.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Enter a valid expiry date and time and save again.', 'kitgenix-affiliate-link-manager' ),
            ],
            'kitgenix_affiliate_invalid_schedule' => [
                'category' => 'validation-error',
                'note'     => __( 'A link was submitted with an expiry date that is not later than its start date.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Set the expiry date later than the start date and save again.', 'kitgenix-affiliate-link-manager' ),
            ],
            'kitgenix_affiliate_invalid_fallback_url' => [
                'category' => 'validation-error',
                'note'     => __( 'A link was submitted with an invalid fallback URL.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Enter a valid fallback URL and save again.', 'kitgenix-affiliate-link-manager' ),
            ],
            'kitgenix_affiliate_invalid_rotation_destination' => [
                'category' => 'validation-error',
                'note'     => __( 'A link was submitted with one or more invalid rotation destination URLs.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Check each rotation destination is a valid URL and save again.', 'kitgenix-affiliate-link-manager' ),
            ],
            'kitgenix_affiliate_slug_exists' => [
                'category' => 'validation-error',
                'note'     => __( 'A link was submitted with a slug that is already used by another link.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Choose a different slug and save again.', 'kitgenix-affiliate-link-manager' ),
            ],
            'kitgenix_affiliate_db_charset' => [
                'category' => 'database-error',
                'note'     => __( 'The database rejected characters in the link name (usually emoji), likely because the database is not using utf8mb4.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Remove emoji/special characters from the name, or ask your host to enable utf8mb4 on the database.', 'kitgenix-affiliate-link-manager' ),
            ],
            'kitgenix_affiliate_db_insert_error' => [
                'category' => 'database-error',
                'note'     => __( 'The link could not be saved because of a database insert error.', 'kitgenix-affiliate-link-manager' ),
                'action'   => __( 'Check your site\'s database error log, or contact your host if this repeats.', 'kitgenix-affiliate-link-manager' ),
            ],
        ];

        if ( isset( $map[ $code ] ) ) {
            return $map[ $code ];
        }

        return [
            'category' => $code !== '' ? 'other' : 'general',
            'note'     => __( 'No further detail is mapped for this event.', 'kitgenix-affiliate-link-manager' ),
            'action'   => __( 'Review the note column for context.', 'kitgenix-affiliate-link-manager' ),
        ];
    }
}
