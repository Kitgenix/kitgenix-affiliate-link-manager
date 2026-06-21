<?php
namespace KitgenixAffiliateLinkManager\Core;

defined( 'ABSPATH' ) || exit;

class Affiliate_Link_Post_Type {

    // IMPORTANT: WordPress stores post_type in wp_posts.post_type (VARCHAR(20)).
    // Keep this key <= 20 characters to avoid DB insert failures.
    public const POST_TYPE = 'kitgenix_aff_link';

    public const META_DESTINATION = '_kitgenix_affiliate_destination_url';
    public const META_CLICKS      = '_kitgenix_affiliate_clicks';
    public const META_REL         = '_kitgenix_affiliate_rel';
    public const META_ENABLED     = '_kitgenix_affiliate_enabled';
    public const META_CAMPAIGN_NOTE = '_kitgenix_affiliate_campaign_note';
    public const META_STARTS_AT   = '_kitgenix_affiliate_starts_at';
    public const META_EXPIRES_AT  = '_kitgenix_affiliate_expires_at';
    public const META_FALLBACK_URL = '_kitgenix_affiliate_fallback_url';
    public const META_ROTATION_MODE = '_kitgenix_affiliate_rotation_mode';
    public const META_ROTATION_DESTINATIONS = '_kitgenix_affiliate_rotation_destinations';
    public const TAXONOMY_GROUP   = 'kitgenix_aff_group';
    public const TAXONOMY_TAG     = 'kitgenix_aff_tag';
    public const OPTION_TOTAL_CLICKS = 'kitgenix_affiliate_link_manager_total_clicks';

    /**
     * Allowed rel values for affiliate links.
     *
     * @return string[]
     */
    public static function get_allowed_rel_values(): array {
        return [
            'nofollow',
            'sponsored',
            'nofollow sponsored',
        ];
    }

    public static function sanitize_rel_value( string $rel ): string {
        $rel = \trim( \preg_replace( '/\s+/', ' ', (string) $rel ) );
        return \in_array( $rel, self::get_allowed_rel_values(), true ) ? $rel : 'nofollow sponsored';
    }

    public static function init(): void {
        \add_action( 'init', [ __CLASS__, 'register_post_type' ] );
        \add_action( 'init', [ __CLASS__, 'register_taxonomies' ] );
    }

    public static function register_post_type(): void {
        $labels = [
            'name'               => \__( 'Affiliate Links', 'kitgenix-affiliate-link-manager' ),
            'singular_name'      => \__( 'Affiliate Link', 'kitgenix-affiliate-link-manager' ),
            'add_new'            => \__( 'Add New', 'kitgenix-affiliate-link-manager' ),
            'add_new_item'       => \__( 'Add New Affiliate Link', 'kitgenix-affiliate-link-manager' ),
            'edit_item'          => \__( 'Edit Affiliate Link', 'kitgenix-affiliate-link-manager' ),
            'new_item'           => \__( 'New Affiliate Link', 'kitgenix-affiliate-link-manager' ),
            'view_item'          => \__( 'View Affiliate Link', 'kitgenix-affiliate-link-manager' ),
            'search_items'       => \__( 'Search Affiliate Links', 'kitgenix-affiliate-link-manager' ),
            'not_found'          => \__( 'No affiliate links found.', 'kitgenix-affiliate-link-manager' ),
            'not_found_in_trash' => \__( 'No affiliate links found in Trash.', 'kitgenix-affiliate-link-manager' ),
            'menu_name'          => \__( 'Affiliate Links', 'kitgenix-affiliate-link-manager' ),
        ];

        \register_post_type(
            self::POST_TYPE,
            [
                'labels'              => $labels,
                'public'              => false,
                'show_ui'             => false,
                'show_in_menu'        => false,
                'show_in_nav_menus'   => false,
                'show_in_admin_bar'   => false,
                'supports'            => [ 'title' ],
                'capability_type'     => 'post',
                'map_meta_cap'        => true,
                'rewrite'             => false,
                'query_var'           => false,
                'exclude_from_search' => true,
            ]
        );
    }

    public static function register_taxonomies(): void {
        \register_taxonomy(
            self::TAXONOMY_GROUP,
            self::POST_TYPE,
            [
                'labels'            => [
                    'name'          => \__( 'Link Groups', 'kitgenix-affiliate-link-manager' ),
                    'singular_name' => \__( 'Link Group', 'kitgenix-affiliate-link-manager' ),
                    'search_items'  => \__( 'Search Link Groups', 'kitgenix-affiliate-link-manager' ),
                    'all_items'     => \__( 'All Link Groups', 'kitgenix-affiliate-link-manager' ),
                    'edit_item'     => \__( 'Edit Link Group', 'kitgenix-affiliate-link-manager' ),
                    'update_item'   => \__( 'Update Link Group', 'kitgenix-affiliate-link-manager' ),
                    'add_new_item'  => \__( 'Add New Link Group', 'kitgenix-affiliate-link-manager' ),
                    'new_item_name' => \__( 'New Link Group Name', 'kitgenix-affiliate-link-manager' ),
                    'menu_name'     => \__( 'Link Groups', 'kitgenix-affiliate-link-manager' ),
                ],
                'public'            => false,
                'show_ui'           => false,
                'show_admin_column' => false,
                'show_in_nav_menus' => false,
                'show_tagcloud'     => false,
                'show_in_quick_edit'=> false,
                'meta_box_cb'       => false,
                'rewrite'           => false,
                'query_var'         => false,
                'hierarchical'      => true,
            ]
        );

        \register_taxonomy(
            self::TAXONOMY_TAG,
            self::POST_TYPE,
            [
                'labels'            => [
                    'name'                       => \__( 'Link Tags', 'kitgenix-affiliate-link-manager' ),
                    'singular_name'              => \__( 'Link Tag', 'kitgenix-affiliate-link-manager' ),
                    'search_items'               => \__( 'Search Link Tags', 'kitgenix-affiliate-link-manager' ),
                    'popular_items'              => \__( 'Popular Link Tags', 'kitgenix-affiliate-link-manager' ),
                    'all_items'                  => \__( 'All Link Tags', 'kitgenix-affiliate-link-manager' ),
                    'edit_item'                  => \__( 'Edit Link Tag', 'kitgenix-affiliate-link-manager' ),
                    'update_item'                => \__( 'Update Link Tag', 'kitgenix-affiliate-link-manager' ),
                    'add_new_item'               => \__( 'Add New Link Tag', 'kitgenix-affiliate-link-manager' ),
                    'new_item_name'              => \__( 'New Link Tag Name', 'kitgenix-affiliate-link-manager' ),
                    'separate_items_with_commas' => \__( 'Separate link tags with commas', 'kitgenix-affiliate-link-manager' ),
                    'add_or_remove_items'        => \__( 'Add or remove link tags', 'kitgenix-affiliate-link-manager' ),
                    'choose_from_most_used'      => \__( 'Choose from the most used link tags', 'kitgenix-affiliate-link-manager' ),
                    'menu_name'                  => \__( 'Link Tags', 'kitgenix-affiliate-link-manager' ),
                ],
                'public'            => false,
                'show_ui'           => false,
                'show_admin_column' => false,
                'show_in_nav_menus' => false,
                'show_tagcloud'     => false,
                'show_in_quick_edit'=> false,
                'meta_box_cb'       => false,
                'rewrite'           => false,
                'query_var'         => false,
                'hierarchical'      => false,
            ]
        );
    }

    private static function normalize_term_names( $raw_terms, bool $single = false ): array {
        if ( is_string( $raw_terms ) ) {
            $raw_terms = \preg_split( '/[\r\n,]+/', $raw_terms );
        } elseif ( ! is_array( $raw_terms ) ) {
            $raw_terms = [];
        }

        $terms = [];

        foreach ( $raw_terms as $raw_term ) {
            $term = \sanitize_text_field( (string) $raw_term );
            $term = \preg_replace( '/\s+/', ' ', $term );
            $term = \trim( (string) $term );

            if ( $term === '' ) {
                continue;
            }

            $slug = \sanitize_title( $term );
            if ( $slug === '' || isset( $terms[ $slug ] ) ) {
                continue;
            }

            $terms[ $slug ] = $term;

            if ( $single ) {
                break;
            }
        }

        return array_values( $terms );
    }

    private static function sanitize_campaign_note( string $note ): string {
        return \trim( (string) \sanitize_textarea_field( $note ) );
    }

    private static function normalize_schedule_value( $value, ?bool &$valid = null ): ?int {
        $valid = true;

        if ( $value === null ) {
            return null;
        }

        if ( is_int( $value ) ) {
            return $value > 0 ? $value : null;
        }

        if ( is_numeric( $value ) && ! is_bool( $value ) ) {
            $timestamp = (int) $value;
            return $timestamp > 0 ? $timestamp : null;
        }

        $raw = \trim( (string) $value );
        if ( $raw === '' ) {
            return null;
        }

        try {
            $timezone = function_exists( 'wp_timezone' ) ? \wp_timezone() : new \DateTimeZone( 'UTC' );
            if ( \preg_match( '/(?:[zZ]|[+\-]\d{2}:\d{2})$/', $raw ) ) {
                $date = new \DateTimeImmutable( $raw );
            } else {
                $date = new \DateTimeImmutable( $raw, $timezone );
            }

            $timestamp = $date->getTimestamp();
            return $timestamp > 0 ? $timestamp : null;
        } catch ( \Exception $exception ) {
            $valid = false;
            return null;
        }
    }

    private static function format_timestamp_for_storage( int $timestamp ): string {
        return $timestamp > 0 ? (string) $timestamp : '';
    }

    private static function format_timestamp_for_api( int $timestamp ): string {
        if ( $timestamp <= 0 ) {
            return '';
        }

        return \gmdate( 'c', $timestamp );
    }

    public static function format_timestamp_for_input( int $timestamp ): string {
        if ( $timestamp <= 0 ) {
            return '';
        }

        $timezone = function_exists( 'wp_timezone' ) ? \wp_timezone() : new \DateTimeZone( 'UTC' );
        return \wp_date( 'Y-m-d\\TH:i', $timestamp, $timezone );
    }

    private static function normalize_optional_url( $value ): string {
        if ( $value === null ) {
            return '';
        }

        return \esc_url_raw( \trim( (string) $value ) );
    }

    private static function normalize_rotation_mode( $value ): string {
        $mode = \sanitize_key( (string) $value );
        if ( ! \in_array( $mode, [ 'none', 'sequential', 'random' ], true ) ) {
            $mode = 'none';
        }

        return $mode;
    }

    /**
     * @return string[]
     */
    private static function normalize_rotation_destinations( $raw_destinations, ?bool &$valid = null ): array {
        $valid = true;

        if ( is_string( $raw_destinations ) ) {
            $trimmed = \trim( $raw_destinations );

            if ( $trimmed === '' ) {
                $raw_destinations = [];
            } else {
                $decoded = null;
                if ( $trimmed[0] === '[' ) {
                    $decoded = \json_decode( $trimmed, true );
                }

                if ( is_array( $decoded ) ) {
                    $raw_destinations = $decoded;
                } else {
                    $raw_destinations = \preg_split( '/[\r\n|]+/', $trimmed );
                }
            }
        } elseif ( ! is_array( $raw_destinations ) ) {
            $raw_destinations = [];
        }

        $destinations = [];

        foreach ( $raw_destinations as $raw_destination ) {
            $destination = self::normalize_optional_url( $raw_destination );
            if ( $destination === '' ) {
                continue;
            }

            if ( ! \wp_http_validate_url( $destination ) ) {
                $valid = false;
                return [];
            }

            if ( isset( $destinations[ $destination ] ) ) {
                continue;
            }

            $destinations[ $destination ] = $destination;
        }

        return array_values( $destinations );
    }

    /**
     * @return string[]
     */
    private static function get_rotation_pool( int $post_id ): array {
        $primary_destination = self::get_destination_url( $post_id );
        $pool                = [];

        if ( $primary_destination !== '' ) {
            $pool[ $primary_destination ] = $primary_destination;
        }

        foreach ( self::get_rotation_destinations( $post_id ) as $destination ) {
            if ( $destination === '' ) {
                continue;
            }

            $pool[ $destination ] = $destination;
        }

        return array_values( $pool );
    }

    public static function get_starts_at_timestamp( int $post_id ): int {
        return max( 0, (int) \get_post_meta( $post_id, self::META_STARTS_AT, true ) );
    }

    public static function get_expires_at_timestamp( int $post_id ): int {
        return max( 0, (int) \get_post_meta( $post_id, self::META_EXPIRES_AT, true ) );
    }

    public static function get_starts_at( int $post_id ): string {
        return self::format_timestamp_for_api( self::get_starts_at_timestamp( $post_id ) );
    }

    public static function get_expires_at( int $post_id ): string {
        return self::format_timestamp_for_api( self::get_expires_at_timestamp( $post_id ) );
    }

    public static function get_fallback_url( int $post_id ): string {
        return self::normalize_optional_url( \get_post_meta( $post_id, self::META_FALLBACK_URL, true ) );
    }

    public static function get_rotation_mode( int $post_id ): string {
        return self::normalize_rotation_mode( \get_post_meta( $post_id, self::META_ROTATION_MODE, true ) );
    }

    /**
     * @return string[]
     */
    public static function get_rotation_destinations( int $post_id ): array {
        $stored = \get_post_meta( $post_id, self::META_ROTATION_DESTINATIONS, true );
        $valid  = true;

        return self::normalize_rotation_destinations( $stored, $valid );
    }

    public static function get_rotation_destinations_text( int $post_id ): string {
        return \implode( "\n", self::get_rotation_destinations( $post_id ) );
    }

    public static function get_redirect_state( int $post_id, ?int $current_timestamp = null ): string {
        if ( ! self::is_enabled( $post_id ) ) {
            return 'disabled';
        }

        $now        = $current_timestamp ?? (int) \current_time( 'timestamp', true );
        $starts_at  = self::get_starts_at_timestamp( $post_id );
        $expires_at = self::get_expires_at_timestamp( $post_id );

        if ( $starts_at > 0 && $now < $starts_at ) {
            return 'scheduled';
        }

        if ( $expires_at > 0 && $now >= $expires_at ) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * @return array<string,mixed>
     */
    public static function resolve_redirect_destination( int $post_id, ?int $current_timestamp = null ): array {
        $post = self::get_link_post( $post_id );
        if ( ! ( $post instanceof \WP_Post ) ) {
            return [
                'destination_url'    => '',
                'state'              => 'missing',
                'using_fallback'     => false,
                'rotation_mode'      => 'none',
                'rotation_pool_size' => 0,
            ];
        }

        $state              = self::get_redirect_state( $post_id, $current_timestamp );
        $primary_destination = self::get_destination_url( $post_id );
        $fallback_url       = self::get_fallback_url( $post_id );
        $rotation_mode      = self::get_rotation_mode( $post_id );
        $rotation_pool      = $rotation_mode === 'none' ? [] : self::get_rotation_pool( $post_id );

        if ( $state !== 'active' ) {
            return [
                'destination_url'   => $fallback_url,
                'state'             => $state,
                'using_fallback'    => $fallback_url !== '',
                'rotation_mode'     => $rotation_mode,
                'rotation_pool_size'=> count( $rotation_pool ),
            ];
        }

        $resolved_destination = $primary_destination;
        if ( count( $rotation_pool ) > 1 ) {
            if ( $rotation_mode === 'random' ) {
                $resolved_destination = $rotation_pool[ \wp_rand( 0, count( $rotation_pool ) - 1 ) ];
            } elseif ( $rotation_mode === 'sequential' ) {
                $resolved_destination = $rotation_pool[ self::get_click_count( $post_id ) % count( $rotation_pool ) ];
            }
        }

        return [
            'destination_url'   => $resolved_destination,
            'state'             => 'active',
            'using_fallback'    => false,
            'rotation_mode'     => $rotation_mode,
            'rotation_pool_size'=> count( $rotation_pool ),
        ];
    }

    private static function normalize_enabled_value( $value ): int {
        if ( is_bool( $value ) ) {
            return $value ? 1 : 0;
        }

        if ( is_numeric( $value ) ) {
            return (int) $value === 1 ? 1 : 0;
        }

        $normalized = \strtolower( \trim( (string) $value ) );
        if ( in_array( $normalized, [ '1', 'true', 'yes', 'enabled', 'on' ], true ) ) {
            return 1;
        }
        if ( in_array( $normalized, [ '0', 'false', 'no', 'disabled', 'off', '' ], true ) ) {
            return 0;
        }

        return ! empty( $value ) ? 1 : 0;
    }

    /**
     * @return \WP_Term[]
     */
    private static function get_terms_for_taxonomy( int $post_id, string $taxonomy ): array {
        if ( $post_id <= 0 || $taxonomy === '' ) {
            return [];
        }

        $terms = \wp_get_object_terms(
            $post_id,
            $taxonomy,
            [
                'orderby' => 'name',
                'order'   => 'ASC',
            ]
        );

        if ( \is_wp_error( $terms ) || ! is_array( $terms ) ) {
            return [];
        }

        $clean_terms = [];
        foreach ( $terms as $term ) {
            if ( $term instanceof \WP_Term ) {
                $clean_terms[] = $term;
            }
        }

        return $clean_terms;
    }

    /**
     * @return string[]
     */
    private static function get_term_names_for_taxonomy( int $post_id, string $taxonomy ): array {
        $terms = self::get_terms_for_taxonomy( $post_id, $taxonomy );
        if ( empty( $terms ) ) {
            return [];
        }

        $names = [];
        foreach ( $terms as $term ) {
            $term_name = \sanitize_text_field( (string) $term->name );
            $term_name = \trim( $term_name );
            if ( $term_name !== '' ) {
                $names[] = $term_name;
            }
        }

        return array_values( array_unique( $names ) );
    }

    public static function get_primary_group_name( int $post_id ): string {
        $group_names = self::get_term_names_for_taxonomy( $post_id, self::TAXONOMY_GROUP );
        return isset( $group_names[0] ) ? (string) $group_names[0] : '';
    }

    public static function get_primary_group_term( int $post_id ): ?\WP_Term {
        $group_terms = self::get_terms_for_taxonomy( $post_id, self::TAXONOMY_GROUP );
        return isset( $group_terms[0] ) ? $group_terms[0] : null;
    }

    public static function get_primary_group_slug( int $post_id ): string {
        $group_term = self::get_primary_group_term( $post_id );
        return $group_term instanceof \WP_Term ? (string) $group_term->slug : '';
    }

    /**
     * @return string[]
     */
    public static function get_tag_names( int $post_id ): array {
        return self::get_term_names_for_taxonomy( $post_id, self::TAXONOMY_TAG );
    }

    /**
     * @return \WP_Term[]
     */
    public static function get_tag_terms( int $post_id ): array {
        return self::get_terms_for_taxonomy( $post_id, self::TAXONOMY_TAG );
    }

    /**
     * @return string[]
     */
    public static function get_tag_slugs( int $post_id ): array {
        $tag_terms = self::get_tag_terms( $post_id );
        $slugs = [];

        foreach ( $tag_terms as $tag_term ) {
            $slug = \sanitize_title( (string) $tag_term->slug );
            if ( $slug !== '' ) {
                $slugs[] = $slug;
            }
        }

        return array_values( array_unique( $slugs ) );
    }

    public static function get_campaign_note( int $post_id ): string {
        return \trim( (string) \get_post_meta( $post_id, self::META_CAMPAIGN_NOTE, true ) );
    }

    public static function get_link_post( int $post_id ): ?\WP_Post {
        $post = \get_post( $post_id );
        if ( ! ( $post instanceof \WP_Post ) || $post->post_type !== self::POST_TYPE || $post->post_status !== 'publish' ) {
            return null;
        }

        return $post;
    }

    public static function get_link_post_by_slug( string $slug ): ?\WP_Post {
        $slug = \sanitize_title( $slug );
        if ( $slug === '' ) {
            return null;
        }

        $post = \get_page_by_path( $slug, 'OBJECT', self::POST_TYPE );
        if ( ! ( $post instanceof \WP_Post ) || $post->post_status !== 'publish' ) {
            return null;
        }

        return $post;
    }

    public static function get_link_post_from_identifier( string $identifier, string $lookup = 'auto' ): ?\WP_Post {
        $identifier = \trim( $identifier );
        $lookup     = \in_array( $lookup, [ 'auto', 'id', 'slug' ], true ) ? $lookup : 'auto';

        if ( $identifier === '' ) {
            return null;
        }

        if ( $lookup === 'id' || ( $lookup === 'auto' && ctype_digit( $identifier ) ) ) {
            $post = self::get_link_post( (int) $identifier );
            if ( $post instanceof \WP_Post || $lookup === 'id' ) {
                return $post;
            }
        }

        return self::get_link_post_by_slug( $identifier );
    }

    /**
     * Create or update an affiliate link.
     *
     * @return int|\WP_Error Post ID or WP_Error.
     */
    public static function upsert_link( array $data ) {
        $post_id         = isset( $data['post_id'] ) ? (int) $data['post_id'] : 0;

        $old_slug = '';
        if ( $post_id ) {
            $current = \get_post( $post_id );
            if ( $current instanceof \WP_Post && $current->post_type === self::POST_TYPE ) {
                $old_slug = (string) $current->post_name;
            }
        }
        $title           = \sanitize_text_field( (string) ( $data['title'] ?? '' ) );
        // Some WordPress installs still run non-utf8mb4 DBs; non-BMP chars (often emoji)
        // can cause wp_insert_post() to fail with a generic DB insert error.
        $title = \preg_replace( '/[\x{10000}-\x{10FFFF}]/u', '', (string) $title );
        $title = \trim( (string) $title );
        $slug_raw        = (string) ( $data['slug'] ?? '' );
        $slug            = \sanitize_title( $slug_raw );
        $destination_url = isset( $data['destination_url'] ) ? \esc_url_raw( (string) $data['destination_url'] ) : '';
        $rel             = self::sanitize_rel_value( (string) ( $data['rel'] ?? 'nofollow sponsored' ) );
        $group_names     = self::normalize_term_names( $data['group_name'] ?? '', true );
        $tag_names       = self::normalize_term_names( $data['tag_names'] ?? '', false );
        $campaign_note   = self::sanitize_campaign_note( (string) ( $data['campaign_note'] ?? '' ) );
        $fallback_url    = self::normalize_optional_url( $data['fallback_url'] ?? '' );
        $rotation_mode   = self::normalize_rotation_mode( $data['rotation_mode'] ?? 'none' );

        $starts_valid = true;
        $starts_at    = self::normalize_schedule_value( $data['starts_at'] ?? null, $starts_valid );
        $expires_valid = true;
        $expires_at    = self::normalize_schedule_value( $data['expires_at'] ?? null, $expires_valid );
        $rotation_valid = true;
        $rotation_destinations = self::normalize_rotation_destinations( $data['rotation_destinations'] ?? [], $rotation_valid );

        $enabled = null;
        if ( array_key_exists( 'enabled', $data ) ) {
            $enabled = self::normalize_enabled_value( $data['enabled'] );
        }

        if ( $title === '' ) {
            return new \WP_Error( 'kitgenix_affiliate_missing_title', \__( 'Please enter a name for the link.', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( $destination_url === '' || ! \wp_http_validate_url( $destination_url ) ) {
            return new \WP_Error( 'kitgenix_affiliate_invalid_url', \__( 'Please enter a valid destination URL.', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! $starts_valid ) {
            return new \WP_Error( 'kitgenix_affiliate_invalid_start', \__( 'Please enter a valid start date and time.', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! $expires_valid ) {
            return new \WP_Error( 'kitgenix_affiliate_invalid_expiry', \__( 'Please enter a valid expiry date and time.', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( $starts_at !== null && $expires_at !== null && $expires_at <= $starts_at ) {
            return new \WP_Error( 'kitgenix_affiliate_invalid_schedule', \__( 'Expiry must be later than the start date and time.', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( $fallback_url !== '' && ! \wp_http_validate_url( $fallback_url ) ) {
            return new \WP_Error( 'kitgenix_affiliate_invalid_fallback_url', \__( 'Please enter a valid fallback URL.', 'kitgenix-affiliate-link-manager' ) );
        }
        if ( ! $rotation_valid ) {
            return new \WP_Error( 'kitgenix_affiliate_invalid_rotation_destination', \__( 'Each rotation destination must be a valid URL.', 'kitgenix-affiliate-link-manager' ) );
        }

        // When a slug is explicitly provided, prevent duplicates.
        // When blank, we let WordPress generate a unique slug from the title.
        if ( $slug !== '' ) {
            $existing = \get_page_by_path( $slug, 'OBJECT', self::POST_TYPE );
            if ( $existing instanceof \WP_Post && ( ! $post_id || (int) $existing->ID !== $post_id ) ) {
                return new \WP_Error( 'kitgenix_affiliate_slug_exists', \__( 'That slug is already in use.', 'kitgenix-affiliate-link-manager' ) );
            }
        }

        $postarr = [
            'post_type'   => self::POST_TYPE,
            'post_title'  => $title,
            'post_status' => 'publish',
        ];

        // If editing and slug left blank, preserve the current slug.
        // If creating and slug left blank, omit post_name and let WP generate.
        if ( $post_id ) {
            if ( $slug !== '' ) {
                $postarr['post_name'] = $slug;
            } else {
                $current = \get_post( $post_id );
                if ( $current instanceof \WP_Post && $current->post_type === self::POST_TYPE ) {
                    $postarr['post_name'] = (string) $current->post_name;
                }
            }
        } elseif ( $slug !== '' ) {
            $postarr['post_name'] = $slug;
        }

        if ( $post_id ) {
            $postarr['ID'] = $post_id;
            $result = \wp_update_post( $postarr, true );
        } else {
            $result = \wp_insert_post( $postarr, true );
        }

        if ( $result instanceof \WP_Error ) {
            // Improve the generic WordPress DB insert error.
            if ( $result->get_error_code() === 'db_insert_error' ) {
                global $wpdb;
                $last_error = isset( $wpdb ) && isset( $wpdb->last_error ) ? (string) $wpdb->last_error : '';

                if ( $last_error !== '' && \stripos( $last_error, 'Incorrect string value' ) !== false ) {
                    return new \WP_Error(
                        'kitgenix_affiliate_db_charset',
                        \__( 'Could not save the link because your database rejected some characters in the Name (usually emoji). Remove emoji/special characters and try again, or enable utf8mb4 on your database.', 'kitgenix-affiliate-link-manager' )
                    );
                }

                if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $last_error !== '' ) {
                    return new \WP_Error(
                        'kitgenix_affiliate_db_insert_error',
                        \sprintf(
                            /* translators: %s: database error message */
                            \__( 'Could not insert post into the database. Database said: %s', 'kitgenix-affiliate-link-manager' ),
                            $last_error
                        )
                    );
                }
            }

            return $result;
        }

        \update_post_meta( (int) $result, self::META_DESTINATION, $destination_url );
        \update_post_meta( (int) $result, self::META_REL, $rel );

        \wp_set_object_terms( (int) $result, $group_names, self::TAXONOMY_GROUP, false );
        \wp_set_object_terms( (int) $result, $tag_names, self::TAXONOMY_TAG, false );

        if ( $campaign_note !== '' ) {
            \update_post_meta( (int) $result, self::META_CAMPAIGN_NOTE, $campaign_note );
        } else {
            \delete_post_meta( (int) $result, self::META_CAMPAIGN_NOTE );
        }

        if ( $starts_at !== null ) {
            \update_post_meta( (int) $result, self::META_STARTS_AT, self::format_timestamp_for_storage( $starts_at ) );
        } else {
            \delete_post_meta( (int) $result, self::META_STARTS_AT );
        }

        if ( $expires_at !== null ) {
            \update_post_meta( (int) $result, self::META_EXPIRES_AT, self::format_timestamp_for_storage( $expires_at ) );
        } else {
            \delete_post_meta( (int) $result, self::META_EXPIRES_AT );
        }

        if ( $fallback_url !== '' ) {
            \update_post_meta( (int) $result, self::META_FALLBACK_URL, $fallback_url );
        } else {
            \delete_post_meta( (int) $result, self::META_FALLBACK_URL );
        }

        if ( $rotation_mode !== 'none' ) {
            \update_post_meta( (int) $result, self::META_ROTATION_MODE, $rotation_mode );
        } else {
            \delete_post_meta( (int) $result, self::META_ROTATION_MODE );
        }

        if ( ! empty( $rotation_destinations ) ) {
            \update_post_meta( (int) $result, self::META_ROTATION_DESTINATIONS, $rotation_destinations );
        } else {
            \delete_post_meta( (int) $result, self::META_ROTATION_DESTINATIONS );
        }

        // Enabled defaults to on for new links.
        if ( $enabled !== null ) {
            \update_post_meta( (int) $result, self::META_ENABLED, (int) $enabled );
        } elseif ( ! $post_id ) {
            \add_post_meta( (int) $result, self::META_ENABLED, 1, true );
        }

        if ( ! $post_id ) {
            \add_post_meta( (int) $result, self::META_CLICKS, 0, true );
        }

        // Invalidate redirect cache entries for slug changes.
        self::purge_redirect_cache_for_slug( $old_slug );
        $saved = \get_post( (int) $result );
        if ( $saved instanceof \WP_Post && $saved->post_type === self::POST_TYPE ) {
            self::purge_redirect_cache_for_slug( (string) $saved->post_name );
        }

        return (int) $result;
    }

    public static function delete_link( int $post_id ): bool {
        $post = \get_post( $post_id );
        if ( ! ( $post instanceof \WP_Post ) || $post->post_type !== self::POST_TYPE ) {
            return false;
        }

        if ( 'publish' === $post->post_status ) {
            self::adjust_total_clicks( - self::get_click_count( $post_id ) );
        }

        self::purge_redirect_cache_for_slug( (string) $post->post_name );

        $trashed = \wp_trash_post( $post_id );
        return (bool) $trashed;
    }

    /**
     * Reset the click count to 0 for a link.
     */
    public static function reset_clicks( int $post_id ): bool {
        $post = \get_post( $post_id );
        if ( ! ( $post instanceof \WP_Post ) || $post->post_type !== self::POST_TYPE ) {
            return false;
        }

        $current_clicks = self::get_click_count( $post_id );
        $reset = (bool) \update_post_meta( $post_id, self::META_CLICKS, 0 );
        if ( $reset && 'publish' === $post->post_status && $current_clicks > 0 ) {
            self::adjust_total_clicks( - $current_clicks );
        }

        if ( $reset ) {
            Analytics::reset_link_analytics( $post_id );
        }

        return $reset;
    }

    /**
     * Duplicate an existing link (destination + rel) with a fresh click count.
     *
     * @return int|\WP_Error
     */
    public static function duplicate_link( int $post_id ) {
        $post = \get_post( $post_id );
        if ( ! ( $post instanceof \WP_Post ) || $post->post_type !== self::POST_TYPE ) {
            return new \WP_Error( 'kitgenix_affiliate_invalid_link', \__( 'Invalid affiliate link.', 'kitgenix-affiliate-link-manager' ) );
        }

        $destination_url = self::get_destination_url( $post_id );
        $rel             = self::get_rel_value( $post_id );
        $group_name      = self::get_primary_group_name( $post_id );
        $tag_names       = self::get_tag_names( $post_id );
        $campaign_note   = self::get_campaign_note( $post_id );
        $starts_at       = self::get_starts_at( $post_id );
        $expires_at      = self::get_expires_at( $post_id );
        $fallback_url    = self::get_fallback_url( $post_id );
        $rotation_mode   = self::get_rotation_mode( $post_id );
        $rotation_destinations = self::get_rotation_destinations( $post_id );

        $title = \sprintf(
            /* translators: %s: original link name */
            \__( 'Copy of %s', 'kitgenix-affiliate-link-manager' ),
            (string) $post->post_title
        );

        return self::upsert_link(
            [
                'post_id'         => 0,
                'title'           => $title,
                'slug'            => '',
                'destination_url' => $destination_url,
                'rel'             => $rel,
                'group_name'      => $group_name,
                'tag_names'       => $tag_names,
                'campaign_note'   => $campaign_note,
                'starts_at'       => $starts_at,
                'expires_at'      => $expires_at,
                'fallback_url'    => $fallback_url,
                'rotation_mode'   => $rotation_mode,
                'rotation_destinations' => $rotation_destinations,
            ]
        );
    }

    private static function purge_redirect_cache_for_slug( string $slug ): void {
        $slug = \sanitize_title( $slug );
        if ( $slug === '' ) {
            return;
        }

        \wp_cache_delete( 'slug_map:' . $slug, 'kitgenix_affiliate_link_manager' );
    }

    public static function get_destination_url( int $post_id ): string {
        return (string) \get_post_meta( $post_id, self::META_DESTINATION, true );
    }

    public static function get_click_count( int $post_id ): int {
        return (int) \get_post_meta( $post_id, self::META_CLICKS, true );
    }

    public static function get_rel_value( int $post_id ): string {
        $rel = (string) \get_post_meta( $post_id, self::META_REL, true );
        if ( $rel === '' ) {
            $rel = 'nofollow sponsored';
        }
        return self::sanitize_rel_value( $rel );
    }

    /**
     * Whether a link is enabled (defaults to enabled if unset).
     */
    public static function is_enabled( int $post_id ): bool {
        $v = \get_post_meta( $post_id, self::META_ENABLED, true );
        if ( $v === '' ) {
            return true;
        }
        return (int) $v === 1;
    }

    public static function set_enabled( int $post_id, bool $enabled ): bool {
        $post = self::get_link_post( $post_id );
        if ( ! ( $post instanceof \WP_Post ) ) {
            return false;
        }

        if ( self::is_enabled( $post_id ) === $enabled ) {
            self::purge_redirect_cache_for_slug( (string) $post->post_name );
            return true;
        }

        $updated = false !== \update_post_meta( $post_id, self::META_ENABLED, $enabled ? 1 : 0 );
        self::purge_redirect_cache_for_slug( (string) $post->post_name );

        return $updated;
    }

    public static function get_link_record_from_post( \WP_Post $post ): array {
        $post_id     = (int) $post->ID;
        $group_term  = self::get_primary_group_term( $post_id );
        $tag_terms   = self::get_tag_terms( $post_id );
        $analytics_snapshot = Analytics::get_link_snapshot( $post_id );
        $tag_names   = [];
        $tag_slugs   = [];

        foreach ( $tag_terms as $tag_term ) {
            $tag_names[] = (string) $tag_term->name;
            $tag_slugs[] = (string) $tag_term->slug;
        }

        return [
            'id'              => $post_id,
            'title'           => (string) $post->post_title,
            'slug'            => (string) $post->post_name,
            'short_url'       => Settings::build_short_url( (string) $post->post_name ),
            'destination_url' => self::get_destination_url( $post_id ),
            'starts_at'       => self::get_starts_at( $post_id ),
            'expires_at'      => self::get_expires_at( $post_id ),
            'fallback_url'    => self::get_fallback_url( $post_id ),
            'rotation_mode'   => self::get_rotation_mode( $post_id ),
            'rotation_destinations' => self::get_rotation_destinations( $post_id ),
            'redirect_state'  => self::get_redirect_state( $post_id ),
            'health_status'   => Health_Checks::get_health_status( $post_id ),
            'health_message'  => Health_Checks::get_health_message( $post_id ),
            'health_checked_at' => Health_Checks::get_health_checked_at( $post_id ),
            'health_http_code' => Health_Checks::get_health_http_code( $post_id ),
            'health_final_url' => Health_Checks::get_health_final_url( $post_id ),
            'health_redirect_hops' => Health_Checks::get_health_redirect_hops( $post_id ),
            'clicks_last_7_days' => isset( $analytics_snapshot['clicks_last_7_days'] ) ? (int) $analytics_snapshot['clicks_last_7_days'] : 0,
            'clicks_last_30_days' => isset( $analytics_snapshot['clicks_last_30_days'] ) ? (int) $analytics_snapshot['clicks_last_30_days'] : 0,
            'timeline_14_days' => isset( $analytics_snapshot['timeline_14_days'] ) && is_array( $analytics_snapshot['timeline_14_days'] ) ? $analytics_snapshot['timeline_14_days'] : [],
            'top_sources_30_days' => isset( $analytics_snapshot['top_sources_30_days'] ) && is_array( $analytics_snapshot['top_sources_30_days'] ) ? $analytics_snapshot['top_sources_30_days'] : [],
            'top_placements_30_days' => isset( $analytics_snapshot['top_placements_30_days'] ) && is_array( $analytics_snapshot['top_placements_30_days'] ) ? $analytics_snapshot['top_placements_30_days'] : [],
            'top_referrers_30_days' => isset( $analytics_snapshot['top_referrers_30_days'] ) && is_array( $analytics_snapshot['top_referrers_30_days'] ) ? $analytics_snapshot['top_referrers_30_days'] : [],
            'clicks'          => self::get_click_count( $post_id ),
            'rel'             => self::get_rel_value( $post_id ),
            'enabled'         => self::is_enabled( $post_id ),
            'group_name'      => $group_term instanceof \WP_Term ? (string) $group_term->name : '',
            'group_slug'      => $group_term instanceof \WP_Term ? (string) $group_term->slug : '',
            'tag_names'       => array_values( array_unique( $tag_names ) ),
            'tag_slugs'       => array_values( array_unique( $tag_slugs ) ),
            'campaign_note'   => self::get_campaign_note( $post_id ),
            'created'         => (string) \get_post_time( 'c', true, $post ),
            'modified'        => (string) \get_post_modified_time( 'c', true, $post ),
        ];
    }

    public static function get_link_record( int $post_id ): ?array {
        $post = self::get_link_post( $post_id );
        if ( ! ( $post instanceof \WP_Post ) ) {
            return null;
        }

        return self::get_link_record_from_post( $post );
    }

    /**
     * @return array<string,string>
     */
    public static function get_export_headers(): array {
        return [
            'id'              => 'ID',
            'title'           => 'Name',
            'slug'            => 'Slug',
            'group_name'      => 'Link Group',
            'tag_names'       => 'Tags',
            'campaign_note'   => 'Campaign Note',
            'destination_url' => 'Destination URL',
            'starts_at'       => 'Starts At',
            'expires_at'      => 'Expires At',
            'fallback_url'    => 'Fallback URL',
            'rotation_mode'   => 'Rotation Mode',
            'rotation_destinations' => 'Rotation Destinations',
            'health_status'   => 'Health Status',
            'health_message'  => 'Health Message',
            'health_checked_at' => 'Health Checked At',
            'health_http_code' => 'Health HTTP Code',
            'health_final_url' => 'Health Final URL',
            'health_redirect_hops' => 'Health Redirect Hops',
            'clicks_last_7_days' => 'Clicks Last 7 Days',
            'clicks_last_30_days' => 'Clicks Last 30 Days',
            'timeline_14_days' => 'Timeline Last 14 Days',
            'top_sources_30_days' => 'Top Sources Last 30 Days',
            'top_placements_30_days' => 'Top Placements Last 30 Days',
            'top_referrers_30_days' => 'Top Referrers Last 30 Days',
            'clicks'          => 'Clicks',
            'rel'             => 'Rel',
            'enabled'         => 'Enabled',
            'created'         => 'Created',
            'modified'        => 'Modified',
        ];
    }

    public static function flatten_link_record_for_export( array $record ): array {
        return [
            'id'              => isset( $record['id'] ) ? (int) $record['id'] : 0,
            'title'           => isset( $record['title'] ) ? (string) $record['title'] : '',
            'slug'            => isset( $record['slug'] ) ? (string) $record['slug'] : '',
            'group_name'      => isset( $record['group_name'] ) ? (string) $record['group_name'] : '',
            'tag_names'       => isset( $record['tag_names'] ) && is_array( $record['tag_names'] ) ? \implode( ', ', $record['tag_names'] ) : '',
            'campaign_note'   => isset( $record['campaign_note'] ) ? (string) $record['campaign_note'] : '',
            'destination_url' => isset( $record['destination_url'] ) ? (string) $record['destination_url'] : '',
            'starts_at'       => isset( $record['starts_at'] ) ? (string) $record['starts_at'] : '',
            'expires_at'      => isset( $record['expires_at'] ) ? (string) $record['expires_at'] : '',
            'fallback_url'    => isset( $record['fallback_url'] ) ? (string) $record['fallback_url'] : '',
            'rotation_mode'   => isset( $record['rotation_mode'] ) ? (string) $record['rotation_mode'] : 'none',
            'rotation_destinations' => isset( $record['rotation_destinations'] ) && is_array( $record['rotation_destinations'] ) ? \implode( ' | ', $record['rotation_destinations'] ) : '',
            'health_status'   => isset( $record['health_status'] ) ? (string) $record['health_status'] : 'unknown',
            'health_message'  => isset( $record['health_message'] ) ? (string) $record['health_message'] : '',
            'health_checked_at' => isset( $record['health_checked_at'] ) ? (string) $record['health_checked_at'] : '',
            'health_http_code' => isset( $record['health_http_code'] ) ? (int) $record['health_http_code'] : 0,
            'health_final_url' => isset( $record['health_final_url'] ) ? (string) $record['health_final_url'] : '',
            'health_redirect_hops' => isset( $record['health_redirect_hops'] ) ? (int) $record['health_redirect_hops'] : 0,
            'clicks_last_7_days' => isset( $record['clicks_last_7_days'] ) ? (int) $record['clicks_last_7_days'] : 0,
            'clicks_last_30_days' => isset( $record['clicks_last_30_days'] ) ? (int) $record['clicks_last_30_days'] : 0,
            'timeline_14_days' => isset( $record['timeline_14_days'] ) && is_array( $record['timeline_14_days'] ) ? Analytics::format_timeline_for_export( $record['timeline_14_days'] ) : '',
            'top_sources_30_days' => isset( $record['top_sources_30_days'] ) && is_array( $record['top_sources_30_days'] ) ? Analytics::format_breakdown_for_export( $record['top_sources_30_days'] ) : '',
            'top_placements_30_days' => isset( $record['top_placements_30_days'] ) && is_array( $record['top_placements_30_days'] ) ? Analytics::format_breakdown_for_export( $record['top_placements_30_days'] ) : '',
            'top_referrers_30_days' => isset( $record['top_referrers_30_days'] ) && is_array( $record['top_referrers_30_days'] ) ? Analytics::format_breakdown_for_export( $record['top_referrers_30_days'] ) : '',
            'clicks'          => isset( $record['clicks'] ) ? (int) $record['clicks'] : 0,
            'rel'             => isset( $record['rel'] ) ? (string) $record['rel'] : 'nofollow sponsored',
            'enabled'         => ! empty( $record['enabled'] ) ? '1' : '0',
            'created'         => isset( $record['created'] ) ? (string) $record['created'] : '',
            'modified'        => isset( $record['modified'] ) ? (string) $record['modified'] : '',
        ];
    }

    private static function record_matches_search( array $record, string $search ): bool {
        $search = \trim( \strtolower( $search ) );
        if ( $search === '' ) {
            return true;
        }

        $haystack = \strtolower(
            \implode(
                "\n",
                [
                    (string) ( $record['title'] ?? '' ),
                    (string) ( $record['slug'] ?? '' ),
                    (string) ( $record['destination_url'] ?? '' ),
                    (string) ( $record['fallback_url'] ?? '' ),
                    (string) ( $record['group_name'] ?? '' ),
                    \implode( ', ', isset( $record['tag_names'] ) && is_array( $record['tag_names'] ) ? $record['tag_names'] : [] ),
                    (string) ( $record['campaign_note'] ?? '' ),
                    (string) ( $record['starts_at'] ?? '' ),
                    (string) ( $record['expires_at'] ?? '' ),
                    \implode( ' ', isset( $record['rotation_destinations'] ) && is_array( $record['rotation_destinations'] ) ? $record['rotation_destinations'] : [] ),
                    (string) ( $record['health_status'] ?? '' ),
                    (string) ( $record['health_message'] ?? '' ),
                    (string) ( $record['health_checked_at'] ?? '' ),
                    (string) ( $record['health_final_url'] ?? '' ),
                    (string) ( $record['clicks_last_7_days'] ?? '' ),
                    (string) ( $record['clicks_last_30_days'] ?? '' ),
                    Analytics::format_timeline_for_export( isset( $record['timeline_14_days'] ) && is_array( $record['timeline_14_days'] ) ? $record['timeline_14_days'] : [] ),
                    Analytics::format_breakdown_for_export( isset( $record['top_sources_30_days'] ) && is_array( $record['top_sources_30_days'] ) ? $record['top_sources_30_days'] : [] ),
                    Analytics::format_breakdown_for_export( isset( $record['top_placements_30_days'] ) && is_array( $record['top_placements_30_days'] ) ? $record['top_placements_30_days'] : [] ),
                    Analytics::format_breakdown_for_export( isset( $record['top_referrers_30_days'] ) && is_array( $record['top_referrers_30_days'] ) ? $record['top_referrers_30_days'] : [] ),
                ]
            )
        );

        return \strpos( $haystack, $search ) !== false;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function list_link_records( array $args = [] ): array {
        $search = isset( $args['search'] ) ? \sanitize_text_field( (string) $args['search'] ) : '';
        $group  = isset( $args['group'] ) ? \sanitize_title( (string) $args['group'] ) : '';
        $tag    = isset( $args['tag'] ) ? \sanitize_title( (string) $args['tag'] ) : '';
        $orderby = isset( $args['orderby'] ) ? \sanitize_key( (string) $args['orderby'] ) : 'date';
        $order   = isset( $args['order'] ) ? \strtoupper( \sanitize_key( (string) $args['order'] ) ) : 'DESC';

        if ( ! \in_array( $orderby, [ 'date', 'title', 'slug', 'clicks' ], true ) ) {
            $orderby = 'date';
        }
        if ( ! \in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
            $order = 'DESC';
        }

        $enabled_filter = null;
        if ( array_key_exists( 'enabled', $args ) && $args['enabled'] !== '' && $args['enabled'] !== null ) {
            if ( is_bool( $args['enabled'] ) ) {
                $enabled_filter = $args['enabled'];
            } else {
                $enabled_raw = \strtolower( \trim( (string) $args['enabled'] ) );
                if ( \in_array( $enabled_raw, [ '1', 'true', 'yes', 'enabled' ], true ) ) {
                    $enabled_filter = true;
                } elseif ( \in_array( $enabled_raw, [ '0', 'false', 'no', 'disabled' ], true ) ) {
                    $enabled_filter = false;
                }
            }
        }

        $query_args = [
            'post_type'               => self::POST_TYPE,
            'post_status'             => 'publish',
            'numberposts'             => -1,
            'orderby'                 => 'date',
            'order'                   => 'DESC',
            'no_found_rows'           => true,
            'update_post_meta_cache'  => true,
            'update_post_term_cache'  => true,
            'suppress_filters'        => false,
        ];

        $tax_query = [];
        if ( $group === '__ungrouped' ) {
            $tax_query[] = [
                'taxonomy' => self::TAXONOMY_GROUP,
                'operator' => 'NOT EXISTS',
            ];
        } elseif ( $group !== '' ) {
            $tax_query[] = [
                'taxonomy' => self::TAXONOMY_GROUP,
                'field'    => 'slug',
                'terms'    => [ $group ],
            ];
        }

        if ( $tag !== '' ) {
            $tax_query[] = [
                'taxonomy' => self::TAXONOMY_TAG,
                'field'    => 'slug',
                'terms'    => [ $tag ],
            ];
        }

        if ( ! empty( $tax_query ) ) {
            if ( count( $tax_query ) > 1 ) {
                $tax_query['relation'] = 'AND';
            }
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Taxonomy filters are optional constraints needed for export filtering.
            $query_args['tax_query'] = $tax_query;
        }

        $posts = \get_posts( $query_args );
        if ( ! is_array( $posts ) ) {
            return [];
        }

        $post_ids = [];
        foreach ( $posts as $post ) {
            if ( $post instanceof \WP_Post ) {
                $post_ids[] = (int) $post->ID;
            }
        }
        Analytics::prime_link_snapshots( $post_ids );

        $records = [];
        foreach ( $posts as $post ) {
            if ( ! ( $post instanceof \WP_Post ) ) {
                continue;
            }

            $record = self::get_link_record_from_post( $post );
            if ( $enabled_filter !== null && (bool) $record['enabled'] !== $enabled_filter ) {
                continue;
            }

            if ( ! self::record_matches_search( $record, $search ) ) {
                continue;
            }

            $records[] = $record;
        }

        \usort(
            $records,
            static function ( array $left, array $right ) use ( $orderby, $order ): int {
                switch ( $orderby ) {
                    case 'title':
                        $comparison = \strnatcasecmp( (string) $left['title'], (string) $right['title'] );
                        break;
                    case 'slug':
                        $comparison = \strnatcasecmp( (string) $left['slug'], (string) $right['slug'] );
                        break;
                    case 'clicks':
                        $comparison = (int) $left['clicks'] <=> (int) $right['clicks'];
                        break;
                    case 'date':
                    default:
                        $comparison = \strcmp( (string) $left['created'], (string) $right['created'] );
                        break;
                }

                if ( 0 === $comparison ) {
                    $comparison = (int) $left['id'] <=> (int) $right['id'];
                }

                return $order === 'ASC' ? $comparison : -$comparison;
            }
        );

        return $records;
    }

    private static function normalize_import_key( string $key ): string {
        $key = \strtolower( \trim( $key ) );
        $key = (string) \preg_replace( '/[^a-z0-9]+/', '_', $key );
        return \trim( $key, '_' );
    }

    private static function get_import_value( array $row, array $keys ) {
        $normalized = [];
        foreach ( $row as $key => $value ) {
            $normalized[ self::normalize_import_key( (string) $key ) ] = $value;
        }

        foreach ( $keys as $key ) {
            $normalized_key = self::normalize_import_key( (string) $key );
            if ( array_key_exists( $normalized_key, $normalized ) ) {
                return $normalized[ $normalized_key ];
            }
        }

        return null;
    }

    private static function normalize_import_item( array $item ): array {
        $id_value = self::get_import_value( $item, [ 'post_id', 'id' ] );
        $clicks   = self::get_import_value( $item, [ 'clicks', 'click_count' ] );

        return [
            'post_id'         => is_numeric( $id_value ) ? (int) $id_value : 0,
            'title'           => (string) self::get_import_value( $item, [ 'title', 'name' ] ),
            'slug'            => (string) self::get_import_value( $item, [ 'slug' ] ),
            'destination_url' => (string) self::get_import_value( $item, [ 'destination_url', 'destination', 'url' ] ),
            'group_name'      => (string) self::get_import_value( $item, [ 'group_name', 'group', 'link_group' ] ),
            'tag_names'       => self::get_import_value( $item, [ 'tag_names', 'tags', 'link_tags' ] ),
            'campaign_note'   => (string) self::get_import_value( $item, [ 'campaign_note', 'note' ] ),
            'starts_at'       => self::get_import_value( $item, [ 'starts_at', 'start_at', 'start_date', 'starts_on' ] ),
            'expires_at'      => self::get_import_value( $item, [ 'expires_at', 'expiry_at', 'expires_on', 'end_date' ] ),
            'fallback_url'    => (string) self::get_import_value( $item, [ 'fallback_url', 'fallback', 'fallback_destination' ] ),
            'rotation_mode'   => (string) self::get_import_value( $item, [ 'rotation_mode', 'rotation' ] ),
            'rotation_destinations' => self::get_import_value( $item, [ 'rotation_destinations', 'rotation_urls', 'destination_rotation_urls' ] ),
            'rel'             => (string) self::get_import_value( $item, [ 'rel' ] ),
            'enabled'         => self::get_import_value( $item, [ 'enabled' ] ),
            'clicks'          => is_numeric( $clicks ) ? max( 0, (int) $clicks ) : null,
        ];
    }

    public static function import_links( array $items, string $match_existing = 'slug' ): array {
        $match_existing = \in_array( $match_existing, [ 'slug', 'id', 'none' ], true ) ? $match_existing : 'slug';

        $created       = 0;
        $updated       = 0;
        $failed        = 0;
        $results       = [];
        $refresh_clicks = false;

        foreach ( $items as $index => $item ) {
            if ( ! is_array( $item ) ) {
                $failed++;
                $results[] = [
                    'index'   => $index,
                    'status'  => 'error',
                    'message' => \__( 'Import items must be objects or associative arrays.', 'kitgenix-affiliate-link-manager' ),
                ];
                continue;
            }

            $normalized = self::normalize_import_item( $item );
            $target_post = null;

            if ( $normalized['post_id'] > 0 ) {
                $target_post = self::get_link_post( (int) $normalized['post_id'] );
            }

            if ( ! ( $target_post instanceof \WP_Post ) && $match_existing === 'slug' && (string) $normalized['slug'] !== '' ) {
                $target_post = self::get_link_post_by_slug( (string) $normalized['slug'] );
            }

            if ( ! ( $target_post instanceof \WP_Post ) && $match_existing === 'id' && $normalized['post_id'] > 0 ) {
                $target_post = self::get_link_post( (int) $normalized['post_id'] );
            }

            $existing_post_id = $target_post instanceof \WP_Post ? (int) $target_post->ID : 0;
            if ( $existing_post_id > 0 ) {
                $normalized['post_id'] = $existing_post_id;
            } else {
                $normalized['post_id'] = 0;
            }

            $result = self::upsert_link( $normalized );
            if ( $result instanceof \WP_Error ) {
                $failed++;
                $results[] = [
                    'index'   => $index,
                    'status'  => 'error',
                    'message' => $result->get_error_message(),
                ];
                continue;
            }

            $saved_id = (int) $result;
            if ( $normalized['clicks'] !== null ) {
                \update_post_meta( $saved_id, self::META_CLICKS, (int) $normalized['clicks'] );
                $refresh_clicks = true;
            }

            $record = self::get_link_record( $saved_id );
            $status = $existing_post_id > 0 ? 'updated' : 'created';
            if ( $existing_post_id > 0 ) {
                $updated++;
            } else {
                $created++;
            }

            $results[] = [
                'index'  => $index,
                'status' => $status,
                'link'   => $record,
            ];
        }

        if ( $refresh_clicks ) {
            self::refresh_total_clicks();
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed'  => $failed,
            'results' => $results,
        ];
    }

    public static function increment_clicks( int $post_id ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        $counted = false;

        // Atomic update to reduce lost increments under concurrent traffic.
        global $wpdb;
        if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! isset( $wpdb->postmeta ) ) {
            $clicks = (int) \get_post_meta( $post_id, self::META_CLICKS, true );
            $clicks++;
            if ( false !== \update_post_meta( $post_id, self::META_CLICKS, $clicks ) ) {
                self::adjust_total_clicks( 1 );
                $counted = true;
            }

            if ( $counted ) {
                Analytics::record_click( $post_id );
            }
            return;
        }

        $table    = (string) $wpdb->postmeta;
        $meta_key = self::META_CLICKS;

        $updated = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic increment avoids lost updates under concurrent traffic.
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core table name comes from $wpdb.
                "UPDATE {$table} SET meta_value = CAST(meta_value AS UNSIGNED) + 1 WHERE post_id = %d AND meta_key = %s",
                $post_id,
                $meta_key
            )
        );

        if ( $updated === false ) {
            $clicks = (int) \get_post_meta( $post_id, self::META_CLICKS, true );
            $clicks++;
            if ( false !== \update_post_meta( $post_id, self::META_CLICKS, $clicks ) ) {
                self::adjust_total_clicks( 1 );
                $counted = true;
            }

            if ( $counted ) {
                Analytics::record_click( $post_id );
            }
            return;
        }

        if ( (int) $updated === 0 ) {
            // Meta row missing; create it then attempt an increment again.
            $added = \add_post_meta( $post_id, $meta_key, 1, true );
            if ( ! $added ) {
                $updated = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic increment avoids lost updates under concurrent traffic.
                    $wpdb->prepare(
                        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core table name comes from $wpdb.
                        "UPDATE {$table} SET meta_value = CAST(meta_value AS UNSIGNED) + 1 WHERE post_id = %d AND meta_key = %s",
                        $post_id,
                        $meta_key
                    )
                );
                if ( false !== $updated && (int) $updated > 0 ) {
                    self::adjust_total_clicks( 1 );
                    $counted = true;
                }

                if ( $counted ) {
                    Analytics::record_click( $post_id );
                }
                return;
            }

            self::adjust_total_clicks( 1 );
            Analytics::record_click( $post_id );
            return;
        }

        self::adjust_total_clicks( 1 );
        Analytics::record_click( $post_id );
    }

    public static function get_total_clicks(): int {
        $stored_total = \get_option( self::OPTION_TOTAL_CLICKS, null );
        if ( null !== $stored_total && is_numeric( $stored_total ) ) {
            return max( 0, (int) $stored_total );
        }

        return self::recalculate_total_clicks();
    }

    public static function refresh_total_clicks(): int {
        return self::recalculate_total_clicks();
    }

    private static function adjust_total_clicks( int $delta ): void {
        if ( 0 === $delta ) {
            return;
        }

        $new_total = self::get_total_clicks() + $delta;
        self::set_total_clicks( max( 0, $new_total ) );
    }

    private static function set_total_clicks( int $total ): void {
        \update_option( self::OPTION_TOTAL_CLICKS, max( 0, $total ), false );
    }

    private static function recalculate_total_clicks(): int {
        $total_clicks = 0;
        $link_ids = \get_posts(
            [
                'post_type'        => self::POST_TYPE,
                'post_status'      => 'publish',
                'fields'           => 'ids',
                'numberposts'      => -1,
                'no_found_rows'    => true,
                'cache_results'    => false,
                'suppress_filters' => false,
            ]
        );

        if ( is_array( $link_ids ) ) {
            foreach ( $link_ids as $link_id ) {
                $total_clicks += self::get_click_count( (int) $link_id );
            }
        }

        self::set_total_clicks( $total_clicks );

        return $total_clicks;
    }
}
