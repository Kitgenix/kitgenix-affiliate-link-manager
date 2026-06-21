<?php
namespace KitgenixAffiliateLinkManager\Core;

defined( 'ABSPATH' ) || exit;

use WP_CLI;
use WP_CLI\Utils;

class CLI_Command extends \WP_CLI_Command {

    public static function init(): void {
        WP_CLI::add_command( 'kitgenix-affiliate link', __CLASS__ );
    }

    private function get_link_or_error( string $identifier, string $lookup = 'auto' ): \WP_Post {
        $post = Affiliate_Link_Post_Type::get_link_post_from_identifier( $identifier, $lookup );
        if ( $post instanceof \WP_Post ) {
            return $post;
        }

        WP_CLI::error( __( 'Affiliate link not found.', 'kitgenix-affiliate-link-manager' ) );
    }

    private function build_upsert_data_from_assoc_args( array $assoc_args, ?\WP_Post $existing_post = null ): array {
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

        if ( array_key_exists( 'title', $assoc_args ) ) {
            $data['title'] = (string) $assoc_args['title'];
        }
        if ( array_key_exists( 'slug', $assoc_args ) ) {
            $data['slug'] = (string) $assoc_args['slug'];
        }
        if ( array_key_exists( 'destination-url', $assoc_args ) ) {
            $data['destination_url'] = (string) $assoc_args['destination-url'];
        }
        if ( array_key_exists( 'starts-at', $assoc_args ) ) {
            $data['starts_at'] = (string) $assoc_args['starts-at'];
        }
        if ( array_key_exists( 'expires-at', $assoc_args ) ) {
            $data['expires_at'] = (string) $assoc_args['expires-at'];
        }
        if ( array_key_exists( 'fallback-url', $assoc_args ) ) {
            $data['fallback_url'] = (string) $assoc_args['fallback-url'];
        }
        if ( array_key_exists( 'rotation-mode', $assoc_args ) ) {
            $data['rotation_mode'] = (string) $assoc_args['rotation-mode'];
        }
        if ( array_key_exists( 'rotation-destinations', $assoc_args ) ) {
            $data['rotation_destinations'] = (string) $assoc_args['rotation-destinations'];
        } elseif ( array_key_exists( 'rotation-urls', $assoc_args ) ) {
            $data['rotation_destinations'] = (string) $assoc_args['rotation-urls'];
        }
        if ( array_key_exists( 'group', $assoc_args ) ) {
            $data['group_name'] = (string) $assoc_args['group'];
        }
        if ( array_key_exists( 'tags', $assoc_args ) ) {
            $data['tag_names'] = (string) $assoc_args['tags'];
        }
        if ( array_key_exists( 'campaign-note', $assoc_args ) ) {
            $data['campaign_note'] = (string) $assoc_args['campaign-note'];
        }
        if ( array_key_exists( 'rel', $assoc_args ) ) {
            $data['rel'] = (string) $assoc_args['rel'];
        }
        if ( array_key_exists( 'enabled', $assoc_args ) ) {
            $data['enabled'] = (string) $assoc_args['enabled'];
        }

        return $data;
    }

    private function format_record( array $record, string $format = 'json' ): void {
        if ( $format === 'table' ) {
            Utils\format_items(
                'table',
                [ Affiliate_Link_Post_Type::flatten_link_record_for_export( $record ) ],
                array_keys( Affiliate_Link_Post_Type::get_export_headers() )
            );
            return;
        }

        WP_CLI::line( (string) wp_json_encode( $record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
    }

    /**
     * Get an affiliate link by ID or slug.
     *
     * ## OPTIONS
     *
     * <identifier>
     * : The link ID or slug.
     *
     * [--by=<by>]
     * : Lookup strategy: auto, id, or slug.
     * ---
     * default: auto
     * ---
     *
     * [--format=<format>]
     * : Output format.
     * ---
     * options:
     *   - json
     *   - table
     * default: json
     * ---
     */
    public function get( array $args, array $assoc_args ): void {
        $post   = $this->get_link_or_error( (string) $args[0], (string) Utils\get_flag_value( $assoc_args, 'by', 'auto' ) );
        $record = Affiliate_Link_Post_Type::get_link_record_from_post( $post );
        $this->format_record( $record, (string) Utils\get_flag_value( $assoc_args, 'format', 'json' ) );
    }

    /**
     * Create a new affiliate link.
     *
     * ## OPTIONS
     *
     * --title=<title>
     * : Link name.
     *
     * --destination-url=<url>
     * : Destination URL.
     *
     * [--slug=<slug>]
     * : Custom slug.
     *
    * [--starts-at=<datetime>]
    * : Optional start date/time (ISO 8601, site-local datetime, or Unix timestamp).
    *
    * [--expires-at=<datetime>]
    * : Optional expiry date/time (ISO 8601, site-local datetime, or Unix timestamp).
    *
    * [--fallback-url=<url>]
    * : Optional fallback destination used before the start date or after expiry.
    *
    * [--rotation-mode=<mode>]
    * : Rotation mode: none, sequential, or random.
    *
    * [--rotation-destinations=<urls>]
    * : Additional rotation destinations separated by new lines or pipe characters.
    *
     * [--group=<group>]
     * : Primary link group name.
     *
     * [--tags=<tags>]
     * : Comma-separated tags.
     *
     * [--campaign-note=<note>]
     * : Campaign note.
     *
     * [--rel=<rel>]
     * : Link rel value.
     *
     * [--enabled=<enabled>]
     * : 1 to enable, 0 to disable.
     *
     * [--porcelain]
     * : Output only the new link ID.
     */
    public function create( array $args, array $assoc_args ): void {
        $result = Affiliate_Link_Post_Type::upsert_link( $this->build_upsert_data_from_assoc_args( $assoc_args ) );
        if ( $result instanceof \WP_Error ) {
            WP_CLI::error( $result->get_error_message() );
        }

        if ( Utils\get_flag_value( $assoc_args, 'porcelain', false ) ) {
            WP_CLI::line( (string) $result );
            return;
        }

        $record = Affiliate_Link_Post_Type::get_link_record( (int) $result );
        WP_CLI::success(
            sprintf(
                /* translators: 1: link ID, 2: link slug */
                __( 'Created affiliate link #%1$d (%2$s).', 'kitgenix-affiliate-link-manager' ),
                (int) $result,
                isset( $record['slug'] ) ? (string) $record['slug'] : ''
            )
        );
    }

    /**
     * Update an existing affiliate link.
     *
     * ## OPTIONS
     *
     * <identifier>
     * : The link ID or slug.
     *
     * [--by=<by>]
     * : Lookup strategy: auto, id, or slug.
     * ---
     * default: auto
     * ---
     *
     * [--title=<title>]
     * : Updated link name.
     *
     * [--destination-url=<url>]
     * : Updated destination URL.
     *
     * [--slug=<slug>]
     * : Updated slug.
     *
    * [--starts-at=<datetime>]
    * : Updated start date/time.
    *
    * [--expires-at=<datetime>]
    * : Updated expiry date/time.
    *
    * [--fallback-url=<url>]
    * : Updated fallback URL.
    *
    * [--rotation-mode=<mode>]
    * : Updated rotation mode: none, sequential, or random.
    *
    * [--rotation-destinations=<urls>]
    * : Updated additional rotation destinations separated by new lines or pipe characters.
    *
     * [--group=<group>]
     * : Updated primary link group.
     *
     * [--tags=<tags>]
     * : Updated comma-separated tags.
     *
     * [--campaign-note=<note>]
     * : Updated campaign note.
     *
     * [--rel=<rel>]
     * : Updated rel value.
     *
     * [--enabled=<enabled>]
     * : 1 to enable, 0 to disable.
     */
    public function update( array $args, array $assoc_args ): void {
        $identifier = (string) $args[0];
        $lookup     = (string) Utils\get_flag_value( $assoc_args, 'by', 'auto' );
        unset( $assoc_args['by'] );

        $post = $this->get_link_or_error( $identifier, $lookup );
        $result = Affiliate_Link_Post_Type::upsert_link( $this->build_upsert_data_from_assoc_args( $assoc_args, $post ) );
        if ( $result instanceof \WP_Error ) {
            WP_CLI::error( $result->get_error_message() );
        }

        WP_CLI::success(
            sprintf(
                /* translators: %d: link ID */
                __( 'Updated affiliate link #%d.', 'kitgenix-affiliate-link-manager' ),
                (int) $result
            )
        );
    }

    /**
     * Disable an affiliate link without deleting it.
     *
     * ## OPTIONS
     *
     * <identifier>
     * : The link ID or slug.
     *
     * [--by=<by>]
     * : Lookup strategy: auto, id, or slug.
     * ---
     * default: auto
     * ---
     */
    public function disable( array $args, array $assoc_args ): void {
        $post = $this->get_link_or_error( (string) $args[0], (string) Utils\get_flag_value( $assoc_args, 'by', 'auto' ) );
        Affiliate_Link_Post_Type::set_enabled( (int) $post->ID, false );
        WP_CLI::success( __( 'Affiliate link disabled.', 'kitgenix-affiliate-link-manager' ) );
    }

    /**
     * Re-enable a disabled affiliate link.
     *
     * ## OPTIONS
     *
     * <identifier>
     * : The link ID or slug.
     *
     * [--by=<by>]
     * : Lookup strategy: auto, id, or slug.
     * ---
     * default: auto
     * ---
     */
    public function enable( array $args, array $assoc_args ): void {
        $post = $this->get_link_or_error( (string) $args[0], (string) Utils\get_flag_value( $assoc_args, 'by', 'auto' ) );
        Affiliate_Link_Post_Type::set_enabled( (int) $post->ID, true );
        WP_CLI::success( __( 'Affiliate link enabled.', 'kitgenix-affiliate-link-manager' ) );
    }

    /**
     * Move an affiliate link to the Trash.
     *
     * ## OPTIONS
     *
     * <identifier>
     * : The link ID or slug.
     *
     * [--by=<by>]
     * : Lookup strategy: auto, id, or slug.
     * ---
     * default: auto
     * ---
     */
    public function delete( array $args, array $assoc_args ): void {
        $post = $this->get_link_or_error( (string) $args[0], (string) Utils\get_flag_value( $assoc_args, 'by', 'auto' ) );
        Affiliate_Link_Post_Type::delete_link( (int) $post->ID );
        WP_CLI::success( __( 'Affiliate link moved to the Trash.', 'kitgenix-affiliate-link-manager' ) );
    }

    private function detect_format( string $file, string $requested_format = '' ): string {
        if ( $requested_format !== '' ) {
            return $requested_format;
        }

        $extension = \strtolower( (string) \pathinfo( $file, PATHINFO_EXTENSION ) );
        if ( in_array( $extension, [ 'json', 'csv' ], true ) ) {
            return $extension;
        }

        return 'json';
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function read_import_file( string $file, string $format ): array {
        if ( ! \is_readable( $file ) ) {
            WP_CLI::error( __( 'Import file is not readable.', 'kitgenix-affiliate-link-manager' ) );
        }

        $contents = (string) \file_get_contents( $file );
        if ( $format === 'json' ) {
            $decoded = \json_decode( $contents, true );
            if ( isset( $decoded['rows'] ) && is_array( $decoded['rows'] ) ) {
                return $decoded['rows'];
            }
            if ( isset( $decoded['links'] ) && is_array( $decoded['links'] ) ) {
                return $decoded['links'];
            }
            if ( is_array( $decoded ) ) {
                return $decoded;
            }

            WP_CLI::error( __( 'JSON import files must decode to an array, or to an object containing rows or links.', 'kitgenix-affiliate-link-manager' ) );
        }

        try {
            $reader = new \SplFileObject( $file, 'rb' );
        } catch ( \RuntimeException $exception ) {
            WP_CLI::error( __( 'Could not open the CSV import file.', 'kitgenix-affiliate-link-manager' ) );
        }

        $reader->setFlags( \SplFileObject::READ_CSV );
        $headers = $reader->fgetcsv();
        if ( ! is_array( $headers ) || $headers === [ null ] ) {
            return [];
        }

        $rows = [];
        foreach ( $reader as $row ) {
            if ( ! is_array( $row ) || $row === [ null ] ) {
                continue;
            }

            $rows[] = array_combine( $headers, array_pad( $row, count( $headers ), '' ) );
        }

        return $rows;
    }

    private function build_csv_content( array $rows ): string {
        $headers = Affiliate_Link_Post_Type::get_export_headers();
        $content = $this->build_csv_row( array_values( $headers ) );

        foreach ( $rows as $row ) {
            $ordered_row = [];
            foreach ( array_keys( $headers ) as $field ) {
                $ordered_row[] = isset( $row[ $field ] ) ? $row[ $field ] : '';
            }
            $content .= $this->build_csv_row( $ordered_row );
        }

        return $content;
    }

    private function build_csv_row( array $fields ): string {
        $escaped_fields = [];
        foreach ( $fields as $field ) {
            $value = str_replace( '"', '""', (string) $field );
            if ( false !== strpbrk( $value, ",\r\n\"" ) ) {
                $value = '"' . $value . '"';
            }
            $escaped_fields[] = $value;
        }

        return implode( ',', $escaped_fields ) . "\r\n";
    }

    /**
     * Export affiliate links to JSON or CSV.
     *
     * ## OPTIONS
     *
     * [<file>]
     * : Optional output file. When omitted, the export is printed to STDOUT.
     *
     * [--format=<format>]
     * : Export format.
     * ---
     * options:
     *   - json
     *   - csv
     * default: json
     * ---
     *
     * [--search=<search>]
     * : Search term.
     *
     * [--group=<group>]
     * : Group slug.
     *
     * [--tag=<tag>]
     * : Tag slug.
     *
     * [--enabled=<enabled>]
     * : Filter by enabled state.
     *
     * [--orderby=<orderby>]
     * : Sort field.
     *
     * [--order=<order>]
     * : Sort direction.
     */
    public function export( array $args, array $assoc_args ): void {
        $file   = isset( $args[0] ) ? (string) $args[0] : '';
        $format = $this->detect_format( $file, (string) Utils\get_flag_value( $assoc_args, 'format', '' ) );

        $records = Affiliate_Link_Post_Type::list_link_records(
            [
                'search'  => Utils\get_flag_value( $assoc_args, 'search', '' ),
                'group'   => Utils\get_flag_value( $assoc_args, 'group', '' ),
                'tag'     => Utils\get_flag_value( $assoc_args, 'tag', '' ),
                'enabled' => Utils\get_flag_value( $assoc_args, 'enabled', null ),
                'orderby' => Utils\get_flag_value( $assoc_args, 'orderby', 'date' ),
                'order'   => Utils\get_flag_value( $assoc_args, 'order', 'DESC' ),
            ]
        );

        $rows = [];
        foreach ( $records as $record ) {
            $rows[] = Affiliate_Link_Post_Type::flatten_link_record_for_export( $record );
        }

        $content = $format === 'csv'
            ? $this->build_csv_content( $rows )
            : (string) wp_json_encode( [ 'rows' => $rows ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

        if ( $file !== '' ) {
            $written = \file_put_contents( $file, $content );
            if ( $written === false ) {
                WP_CLI::error( __( 'Could not write the export file.', 'kitgenix-affiliate-link-manager' ) );
            }
            WP_CLI::success(
                sprintf(
                    /* translators: 1: export count, 2: file path */
                    __( 'Exported %1$d links to %2$s.', 'kitgenix-affiliate-link-manager' ),
                    count( $rows ),
                    $file
                )
            );
            return;
        }

        WP_CLI::line( $content );
    }

    /**
     * Import affiliate links from a JSON or CSV file.
     *
     * ## OPTIONS
     *
     * <file>
     * : Path to the import file.
     *
     * [--format=<format>]
     * : Import format. Defaults to the file extension.
     * ---
     * options:
     *   - json
     *   - csv
     * ---
     *
     * [--match-existing=<strategy>]
     * : Update strategy for existing links.
     * ---
     * options:
     *   - slug
     *   - id
     *   - none
     * default: slug
     * ---
     */
    public function import( array $args, array $assoc_args ): void {
        $file   = (string) $args[0];
        $format = $this->detect_format( $file, (string) Utils\get_flag_value( $assoc_args, 'format', '' ) );
        $rows   = $this->read_import_file( $file, $format );

        $result = Affiliate_Link_Post_Type::import_links(
            $rows,
            (string) Utils\get_flag_value( $assoc_args, 'match-existing', 'slug' )
        );

        if ( (int) $result['failed'] > 0 ) {
            WP_CLI::warning(
                sprintf(
                    /* translators: 1: created count, 2: updated count, 3: failed count */
                    __( 'Import finished with failures: %1$d created, %2$d updated, %3$d failed.', 'kitgenix-affiliate-link-manager' ),
                    (int) $result['created'],
                    (int) $result['updated'],
                    (int) $result['failed']
                )
            );
            return;
        }

        WP_CLI::success(
            sprintf(
                /* translators: 1: created count, 2: updated count */
                __( 'Imported affiliate links: %1$d created, %2$d updated.', 'kitgenix-affiliate-link-manager' ),
                (int) $result['created'],
                (int) $result['updated']
            )
        );
    }
}