<?php
namespace KitgenixAffiliateLinkManager\Core;

defined( 'ABSPATH' ) || exit;

final class Rest_API {

    private const NAMESPACE = 'kitgenix-affiliate-link-manager/v1';

    public static function init(): void {
        \add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes(): void {
        \register_rest_route(
            self::NAMESPACE,
            '/links',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'list_links' ],
                    'permission_callback' => [ __CLASS__, 'permission_callback' ],
                ],
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ __CLASS__, 'create_link' ],
                    'permission_callback' => [ __CLASS__, 'permission_callback' ],
                ],
            ]
        );

        \register_rest_route(
            self::NAMESPACE,
            '/links/export',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ __CLASS__, 'export_links' ],
                'permission_callback' => [ __CLASS__, 'permission_callback' ],
            ]
        );

        \register_rest_route(
            self::NAMESPACE,
            '/links/import',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ __CLASS__, 'import_links' ],
                'permission_callback' => [ __CLASS__, 'permission_callback' ],
            ]
        );

        \register_rest_route(
            self::NAMESPACE,
            '/links/(?P<id>\d+)',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ __CLASS__, 'get_link' ],
                    'permission_callback' => [ __CLASS__, 'permission_callback' ],
                ],
                [
                    'methods'             => 'PUT,PATCH',
                    'callback'            => [ __CLASS__, 'update_link' ],
                    'permission_callback' => [ __CLASS__, 'permission_callback' ],
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [ __CLASS__, 'delete_link' ],
                    'permission_callback' => [ __CLASS__, 'permission_callback' ],
                ],
            ]
        );

        \register_rest_route(
            self::NAMESPACE,
            '/links/(?P<id>\d+)/disable',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ __CLASS__, 'disable_link' ],
                'permission_callback' => [ __CLASS__, 'permission_callback' ],
            ]
        );

        \register_rest_route(
            self::NAMESPACE,
            '/links/(?P<id>\d+)/enable',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ __CLASS__, 'enable_link' ],
                'permission_callback' => [ __CLASS__, 'permission_callback' ],
            ]
        );
    }

    private static function get_manage_capability(): string {
        $capability = class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options';
        if ( ! \current_user_can( $capability ) && \current_user_can( 'manage_options' ) ) {
            $capability = 'manage_options';
        }

        $capability = (string) \apply_filters( 'kitgenix_affiliate_manage_capability', $capability );
        return $capability !== '' ? $capability : 'manage_options';
    }

    public static function permission_callback() {
        if ( \current_user_can( self::get_manage_capability() ) ) {
            return true;
        }

        $status = function_exists( 'rest_authorization_required_code' ) ? \rest_authorization_required_code() : 403;
        return new \WP_Error(
            'kitgenix_affiliate_rest_forbidden',
            \__( 'Sorry, you are not allowed to manage affiliate links.', 'kitgenix-affiliate-link-manager' ),
            [ 'status' => $status ]
        );
    }

    private static function get_collection_filters( \WP_REST_Request $request ): array {
        $per_page = (int) $request->get_param( 'per_page' );
        if ( $per_page < 1 ) {
            $per_page = 50;
        }
        if ( $per_page > 200 ) {
            $per_page = 200;
        }

        $page = (int) $request->get_param( 'page' );
        if ( $page < 1 ) {
            $page = 1;
        }

        return [
            'search'   => (string) $request->get_param( 'search' ),
            'group'    => (string) $request->get_param( 'group' ),
            'tag'      => (string) $request->get_param( 'tag' ),
            'enabled'  => $request->get_param( 'enabled' ),
            'orderby'  => (string) $request->get_param( 'orderby' ),
            'order'    => (string) $request->get_param( 'order' ),
            'per_page' => $per_page,
            'page'     => $page,
        ];
    }

    public static function list_links( \WP_REST_Request $request ) {
        $filters = self::get_collection_filters( $request );
        $records = Affiliate_Link_Post_Type::list_link_records( $filters );
        $total   = count( $records );
        $per_page = (int) $filters['per_page'];
        $page     = (int) $filters['page'];
        $total_pages = max( 1, (int) ceil( $total / $per_page ) );

        $items = array_values( array_slice( $records, ( $page - 1 ) * $per_page, $per_page ) );
        $response = new \WP_REST_Response(
            [
                'items'      => $items,
                'pagination' => [
                    'page'        => $page,
                    'per_page'    => $per_page,
                    'total_items' => $total,
                    'total_pages' => $total_pages,
                ],
            ],
            200
        );

        $response->header( 'X-WP-Total', (string) $total );
        $response->header( 'X-WP-TotalPages', (string) $total_pages );

        return $response;
    }

    public static function export_links( \WP_REST_Request $request ) {
        $records = Affiliate_Link_Post_Type::list_link_records( self::get_collection_filters( $request ) );
        $rows    = [];

        foreach ( $records as $record ) {
            $rows[] = Affiliate_Link_Post_Type::flatten_link_record_for_export( $record );
        }

        return new \WP_REST_Response(
            [
                'exported' => count( $rows ),
                'headers'  => Affiliate_Link_Post_Type::get_export_headers(),
                'rows'     => $rows,
            ],
            200
        );
    }

    private static function build_upsert_data( \WP_REST_Request $request, ?\WP_Post $existing_post = null ): array {
        $post_id = $existing_post instanceof \WP_Post ? (int) $existing_post->ID : 0;
        $data = [
            'post_id'         => $post_id,
            'title'           => $existing_post instanceof \WP_Post ? (string) $existing_post->post_title : '',
            'slug'            => $existing_post instanceof \WP_Post ? (string) $existing_post->post_name : '',
            'destination_url' => $post_id > 0 ? Affiliate_Link_Post_Type::get_destination_url( $post_id ) : '',
            'starts_at'       => $post_id > 0 ? Affiliate_Link_Post_Type::get_starts_at( $post_id ) : '',
            'expires_at'      => $post_id > 0 ? Affiliate_Link_Post_Type::get_expires_at( $post_id ) : '',
            'fallback_url'    => $post_id > 0 ? Affiliate_Link_Post_Type::get_fallback_url( $post_id ) : '',
            'rotation_mode'   => $post_id > 0 ? Affiliate_Link_Post_Type::get_rotation_mode( $post_id ) : 'none',
            'rotation_destinations' => $post_id > 0 ? Affiliate_Link_Post_Type::get_rotation_destinations( $post_id ) : [],
            'group_name'      => $post_id > 0 ? Affiliate_Link_Post_Type::get_primary_group_name( $post_id ) : '',
            'tag_names'       => $post_id > 0 ? Affiliate_Link_Post_Type::get_tag_names( $post_id ) : [],
            'campaign_note'   => $post_id > 0 ? Affiliate_Link_Post_Type::get_campaign_note( $post_id ) : '',
            'rel'             => $post_id > 0 ? Affiliate_Link_Post_Type::get_rel_value( $post_id ) : 'nofollow sponsored',
            'enabled'         => $post_id > 0 ? Affiliate_Link_Post_Type::is_enabled( $post_id ) : true,
        ];

        if ( $request->has_param( 'title' ) ) {
            $data['title'] = (string) $request->get_param( 'title' );
        }
        if ( $request->has_param( 'slug' ) ) {
            $data['slug'] = (string) $request->get_param( 'slug' );
        }
        if ( $request->has_param( 'destination_url' ) ) {
            $data['destination_url'] = (string) $request->get_param( 'destination_url' );
        }
        if ( $request->has_param( 'starts_at' ) ) {
            $data['starts_at'] = $request->get_param( 'starts_at' );
        }
        if ( $request->has_param( 'expires_at' ) ) {
            $data['expires_at'] = $request->get_param( 'expires_at' );
        }
        if ( $request->has_param( 'fallback_url' ) ) {
            $data['fallback_url'] = (string) $request->get_param( 'fallback_url' );
        }
        if ( $request->has_param( 'rotation_mode' ) ) {
            $data['rotation_mode'] = (string) $request->get_param( 'rotation_mode' );
        }
        if ( $request->has_param( 'rotation_destinations' ) ) {
            $data['rotation_destinations'] = $request->get_param( 'rotation_destinations' );
        } elseif ( $request->has_param( 'rotation_urls' ) ) {
            $data['rotation_destinations'] = $request->get_param( 'rotation_urls' );
        }
        if ( $request->has_param( 'group_name' ) ) {
            $data['group_name'] = (string) $request->get_param( 'group_name' );
        } elseif ( $request->has_param( 'group' ) ) {
            $data['group_name'] = (string) $request->get_param( 'group' );
        }
        if ( $request->has_param( 'tag_names' ) ) {
            $data['tag_names'] = $request->get_param( 'tag_names' );
        } elseif ( $request->has_param( 'tags' ) ) {
            $data['tag_names'] = $request->get_param( 'tags' );
        }
        if ( $request->has_param( 'campaign_note' ) ) {
            $data['campaign_note'] = (string) $request->get_param( 'campaign_note' );
        }
        if ( $request->has_param( 'rel' ) ) {
            $data['rel'] = (string) $request->get_param( 'rel' );
        }
        if ( $request->has_param( 'enabled' ) ) {
            $data['enabled'] = $request->get_param( 'enabled' );
        }

        return $data;
    }

    public static function create_link( \WP_REST_Request $request ) {
        $result = Affiliate_Link_Post_Type::upsert_link( self::build_upsert_data( $request ) );
        if ( $result instanceof \WP_Error ) {
            return new \WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 400 ] );
        }

        return new \WP_REST_Response( Affiliate_Link_Post_Type::get_link_record( (int) $result ), 201 );
    }

    private static function get_link_from_request( \WP_REST_Request $request ) {
        $link = Affiliate_Link_Post_Type::get_link_post( (int) $request['id'] );
        if ( $link instanceof \WP_Post ) {
            return $link;
        }

        return new \WP_Error( 'kitgenix_affiliate_rest_not_found', \__( 'Affiliate link not found.', 'kitgenix-affiliate-link-manager' ), [ 'status' => 404 ] );
    }

    public static function get_link( \WP_REST_Request $request ) {
        $link = self::get_link_from_request( $request );
        if ( $link instanceof \WP_Error ) {
            return $link;
        }

        return new \WP_REST_Response( Affiliate_Link_Post_Type::get_link_record_from_post( $link ), 200 );
    }

    public static function update_link( \WP_REST_Request $request ) {
        $link = self::get_link_from_request( $request );
        if ( $link instanceof \WP_Error ) {
            return $link;
        }

        $result = Affiliate_Link_Post_Type::upsert_link( self::build_upsert_data( $request, $link ) );
        if ( $result instanceof \WP_Error ) {
            return new \WP_Error( $result->get_error_code(), $result->get_error_message(), [ 'status' => 400 ] );
        }

        return new \WP_REST_Response( Affiliate_Link_Post_Type::get_link_record( (int) $result ), 200 );
    }

    public static function delete_link( \WP_REST_Request $request ) {
        $link = self::get_link_from_request( $request );
        if ( $link instanceof \WP_Error ) {
            return $link;
        }

        Affiliate_Link_Post_Type::delete_link( (int) $link->ID );

        return new \WP_REST_Response( [ 'deleted' => true, 'id' => (int) $link->ID ], 200 );
    }

    public static function disable_link( \WP_REST_Request $request ) {
        $link = self::get_link_from_request( $request );
        if ( $link instanceof \WP_Error ) {
            return $link;
        }

        Affiliate_Link_Post_Type::set_enabled( (int) $link->ID, false );

        return new \WP_REST_Response( Affiliate_Link_Post_Type::get_link_record( (int) $link->ID ), 200 );
    }

    public static function enable_link( \WP_REST_Request $request ) {
        $link = self::get_link_from_request( $request );
        if ( $link instanceof \WP_Error ) {
            return $link;
        }

        Affiliate_Link_Post_Type::set_enabled( (int) $link->ID, true );

        return new \WP_REST_Response( Affiliate_Link_Post_Type::get_link_record( (int) $link->ID ), 200 );
    }

    public static function import_links( \WP_REST_Request $request ) {
        $items = $request->get_param( 'links' );
        if ( ! is_array( $items ) ) {
            return new \WP_Error(
                'kitgenix_affiliate_rest_invalid_import',
                \__( 'The import request must include a links array.', 'kitgenix-affiliate-link-manager' ),
                [ 'status' => 400 ]
            );
        }

        $match_existing = (string) $request->get_param( 'match_existing' );
        if ( $match_existing === '' ) {
            $match_existing = 'slug';
        }

        return new \WP_REST_Response( Affiliate_Link_Post_Type::import_links( $items, $match_existing ), 200 );
    }
}