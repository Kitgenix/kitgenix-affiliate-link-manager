<?php
namespace KitgenixAffiliateLinkManager\Core;

defined( 'ABSPATH' ) || exit;

class Redirector {

    public const QUERY_VAR = 'kitgenix_affiliate_slug';

    public static function init(): void {
        \add_action( 'init', [ __CLASS__, 'add_rewrite' ] );
        \add_filter( 'query_vars', [ __CLASS__, 'add_query_vars' ] );
        \add_action( 'template_redirect', [ __CLASS__, 'maybe_redirect' ] );
    }

    public static function add_query_vars( array $vars ): array {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public static function add_rewrite(): void {
        $prefix = Settings::get_prefix();
        if ( $prefix === '' ) {
            $prefix = 'go';
        }

        // Prefix can be changed in settings.
        $prefix = trim( $prefix, "/ \t\n\r\0\x0B" );

        \add_rewrite_tag( '%' . self::QUERY_VAR . '%', '([^&]+)' );
        \add_rewrite_rule(
            '^' . \preg_quote( $prefix, '~' ) . '/([^/]+)/?$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );
    }

    public static function maybe_redirect(): void {
        $slug = \get_query_var( self::QUERY_VAR );
        if ( ! $slug ) {
            return;
        }

        $slug = \sanitize_title( (string) $slug );
        if ( $slug === '' ) {
            self::render_404();
            return;
        }

        $cache_group = 'kitgenix_affiliate_link_manager';
        $cache_key   = 'slug_map:' . $slug;

        $post_id     = 0;

        $found  = false;
        $cached = \wp_cache_get( $cache_key, $cache_group, false, $found );
        if ( $found && \is_array( $cached ) ) {
            $post_id     = isset( $cached['id'] ) ? (int) $cached['id'] : 0;
        }

        if ( $post_id <= 0 ) {
            $post = \get_page_by_path( $slug, 'OBJECT', Affiliate_Link_Post_Type::POST_TYPE );
            if ( ! ( $post instanceof \WP_Post ) ) {
                self::render_404();
                return;
            }

            $post_id     = (int) $post->ID;
        }

        $resolved    = Affiliate_Link_Post_Type::resolve_redirect_destination( $post_id );
        $destination = isset( $resolved['destination_url'] ) ? (string) $resolved['destination_url'] : '';
        $state       = isset( $resolved['state'] ) ? (string) $resolved['state'] : 'active';

        if ( $state === 'disabled' ) {
            self::render_404();
            return;
        }

        // Destination URLs are admin-managed and commonly point to external domains
        // (affiliate programs). Avoid wp_validate_redirect/wp_safe_redirect which
        // block external hosts by default.
        $destination = $destination ? \esc_url_raw( (string) $destination ) : '';

        /**
         * Filter the destination URL for a given affiliate link before redirect.
         *
         * @param string $destination Destination URL.
         * @param int    $post_id      Affiliate link post ID.
         * @param string $slug         Affiliate link slug.
         */
        $destination = (string) \apply_filters( 'kitgenix_affiliate_destination_url', $destination, $post_id, $slug );
        $destination = $destination ? \esc_url_raw( (string) $destination ) : '';

        if ( $destination === '' || ! \wp_http_validate_url( $destination ) ) {
            self::render_404();
            return;
        }

        // Cache successful slug lookups for faster redirects without caching dynamic
        // destination resolution (schedule windows and rotation must stay live).
        if ( ! $found ) {
            $ttl = defined( 'MINUTE_IN_SECONDS' ) ? (int) MINUTE_IN_SECONDS : 60;

            /**
             * Filter the slug lookup cache TTL.
             *
             * @param int    $ttl     Cache TTL in seconds.
             * @param int    $post_id Affiliate link post ID.
             * @param string $slug    Affiliate link slug.
             */
            $cache_ttl = (int) \apply_filters( 'kitgenix_affiliate_slug_cache_ttl', 10 * $ttl, $post_id, $slug );

            \wp_cache_set(
                $cache_key,
                [
                    'id' => $post_id,
                ],
                $cache_group,
                $cache_ttl
            );
        }

        $status = (int) Settings::get_redirect_status();

        /**
         * Filter the redirect status code for a given affiliate link.
         *
         * @param int    $status      Redirect status (301/302/307).
         * @param int    $post_id     Affiliate link post ID.
         * @param string $slug        Affiliate link slug.
         * @param string $destination Final destination URL.
         */
        $status = (int) \apply_filters( 'kitgenix_affiliate_redirect_status', $status, $post_id, $slug, $destination );
        if ( ! \in_array( $status, [ 301, 302, 307 ], true ) ) {
            $status = (int) Settings::get_redirect_status();
        }

        Affiliate_Link_Post_Type::increment_clicks( $post_id );

        /**
         * Action fired just before redirecting for an affiliate link.
         *
         * @param int    $post_id     Affiliate link post ID.
         * @param string $slug        Affiliate link slug.
         * @param string $destination Final destination URL.
         * @param int    $status      Redirect status code.
         */
        \do_action( 'kitgenix_affiliate_redirected', $post_id, $slug, $destination, $status );

        // Allow this external destination host for the safe redirect.
        $host = \wp_parse_url( $destination, PHP_URL_HOST );
        if ( $host ) {
            \add_filter( 'allowed_redirect_hosts', static function ( $hosts ) use ( $host ) {
                $hosts[] = $host;
                return $hosts;
            } );
        }
        \wp_safe_redirect( $destination, $status );
        exit;
    }

    private static function render_404(): void {
        \status_header( 404 );
        \nocache_headers();
        if ( function_exists( 'get_404_template' ) ) {
            $template = (string) \get_404_template();
            if ( $template !== '' ) {
                include $template;
                exit;
            }
        }
        echo '404 Not Found';
        exit;
    }
}
