=== Kitgenix Affiliate Link Manager ===
Contributors: kitgenix
Donate link: https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2
Tags: affiliate links, affiliate marketing, link management, click tracking, redirects
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 2.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Manage branded affiliate links, redirects, click analytics, scheduling, rotation and link health from WordPress.

== Description ==

**Kitgenix Affiliate Link Manager** is a self-hosted WordPress affiliate link manager for creating branded short links, controlling redirect destinations, organising campaigns and measuring aggregate click activity without relying on a hosted Kitgenix tracking platform.

It is built for affiliate marketers, publishers, content websites, agencies, review sites, WooCommerce stores and other WordPress sites that publish external campaign or partner links. Instead of scattering long destination URLs throughout your content, you can publish a stable first-party URL such as `example.com/go/product-name/` and manage the destination behind that link from WordPress.

The public short link can remain unchanged even when the affiliate destination changes. That makes the plugin useful for evergreen articles, email campaigns, printed QR codes, documentation, social profiles and any other place where editing every old link later would be difficult.

Learn more about the developer behind the plugin at [Kitgenix](https://kitgenix.com/).

= Affiliate Link Management Features =

* Create branded short URLs on your own WordPress domain.
* Configure the global redirect prefix used before link slugs.
* Change destinations without changing the public short URL.
* Enable or disable individual links without deleting them.
* Choose the redirect status used for managed links.
* Store campaign notes for internal reference.
* Add `nofollow`, `sponsored`, or `nofollow sponsored` relationship guidance to link records.
* Schedule when links become active and when they expire.
* Define a fallback URL for links that are not currently eligible to use their normal destination.
* Rotate one public link across several destination URLs using sequential or random rotation.
* Organise links with groups and tags.
* Search, filter, sort, duplicate and bulk-manage link records.
* Record aggregate click analytics without storing visitor IP addresses in the click-statistics table.
* Review recent activity, source, placement and referrer-host breakdowns.
* Run automatic and manual destination health checks.
* Import and export links using CSV and JSON workflows.
* Manage links programmatically through authenticated REST API routes.
* Manage links from the command line with WP-CLI.
* Keep plugin data local to the WordPress installation unless a destination health check or normal redirect necessarily contacts an external destination.

= Branded Redirects and Stable Short Links =

Each managed link has a WordPress-controlled slug. The plugin combines that slug with the configured redirect prefix to create the public-facing URL. A prefix such as `go` and a slug such as `hosting-offer` produces a route similar to `/go/hosting-offer/`.

When a visitor requests that route, the redirect layer resolves the matching link, checks whether it is enabled and currently active, evaluates any start/expiry rules, chooses the appropriate destination, records the aggregate click event and then issues the configured redirect. The underlying affiliate URL can be changed later while the public URL remains stable.

The managed link records are stored in the private `kitgenix_aff_link` post type. Groups use the `kitgenix_aff_group` taxonomy and tags use `kitgenix_aff_tag`, allowing large collections of campaigns to be organised without exposing those records as normal public posts.

= Scheduling, Expiry and Fallback Destinations =

Campaigns frequently have start dates, expiry dates or time-sensitive offers. Each link can store a start timestamp and expiry timestamp so the redirect state is evaluated automatically at request time.

A fallback URL can be used when the normal destination should not be used. This is useful for expired promotions, not-yet-active campaigns, retired products or other cases where sending a visitor to an alternative page is better than leaving a dead affiliate link in published content.

Because the schedule is evaluated by the redirect resolver, the behaviour is functional rather than a wp-admin label only.

= Destination Rotation =

A single branded link can distribute requests across a pool of destinations. The plugin supports no rotation, sequential rotation and random rotation. The primary destination and additional rotation destinations form the available pool used by the redirect logic.

Rotation can be useful for publishers testing several offers, balancing traffic between equivalent landing pages, or maintaining one memorable public URL while working with multiple destination URLs.

= Link Groups, Tags and Bulk Administration =

Groups and tags provide two layers of organisation. The admin interface also includes search, filtering, sorting and bulk operations, making it practical to manage more than a handful of redirects.

Bulk tools include assigning groups and adding tags to selected links. Existing links can be duplicated to create a new campaign from a known configuration, and links can be enabled or disabled independently of deletion.

= Aggregate Click Analytics =

The analytics subsystem stores daily aggregate statistics in the plugin-owned `{prefix}kitgenix_aff_click_stats` database table. It can report total clicks, recent click trends and breakdowns by source, placement and normalised referrer host.

The click-statistics implementation is intentionally aggregate. It does not store the visitor's IP address, full browser user-agent, full referring URL, tracking cookie or browser fingerprint as part of the analytics record. Referrer data used for reporting is reduced to the host before storage.

The plugin also maintains link-level click totals and cached overview data so the admin interface does not need to reconstruct every summary on every page load.

= Affiliate Destination Health Checks =

Managed destinations can become unavailable, start returning errors or redirect somewhere unexpected. The health-check system can inspect configured destinations and retain the resulting status, message, HTTP code, final URL, redirect-hop count and time of the last check.

Health checks can run automatically using WordPress scheduled events or can be started manually for a link. The automatic checker processes the catalogue in batches and maintains a cursor/summary so a large link library does not need to be tested in one request.

Health checking sends a normal server-side HTTP request to the destination selected by the site administrator. The destination website therefore receives ordinary request/network information. The plugin does not send those checks through a Kitgenix proxy.

= CSV and JSON Portability =

Import/export tools are available for administrators who need to move links between WordPress environments or update them in bulk. CSV output includes protection against spreadsheet-formula injection, and JSON is available where a structured interchange format is more convenient.

Import processing validates and normalises incoming records and uses bounded batch sizes. Existing records can be matched according to the supported import workflow instead of every import being treated as a completely new link.

= REST API for Affiliate Links =

The authenticated REST namespace is:

`kitgenix-affiliate-link-manager/v1`

The codebase implements routes for listing, creating, reading, updating and deleting links, plus import/export, enable/disable, duplication, click-reset and health-check actions. Collection requests support common filters such as search, group, tag, enabled state, ordering and pagination.

REST management is protected by the plugin's management capability checks; the plugin does not expose anonymous link administration.

= WP-CLI =

The command base is:

`wp kitgenix-affiliate link`

Implemented commands include `get`, `create`, `update`, `disable`, `enable`, `duplicate`, `reset-clicks`, `check-health`, `delete`, `export` and `import`.

These commands make the plugin suitable for scripted migrations, deployment workflows and administrators who prefer command-line management.

= Privacy-Focused, WordPress-Native Storage =

Link records, taxonomies, settings, logs and aggregate analytics are stored in WordPress or the site's own database. The plugin uses WordPress posts, metadata, taxonomies, options, scheduled events, the HTTP API, REST API and capability/nonce controls.

There is no remote Kitgenix click-tracking account required to create or use links. External network activity is limited to actions that inherently require it, such as redirecting a visitor to the destination, checking a destination's health, retrieving public Kitgenix plugin information for the shared admin Hub, or loading the disclosed admin font service.

= Typical Use Cases =

* Affiliate marketing links in comparison articles and product reviews.
* Branded `/go/` links in newsletters or social campaigns.
* Stable URLs for printed material and QR codes.
* Time-limited promotions that should start or expire automatically.
* Campaign links that need a safe fallback after an offer ends.
* Testing or distributing traffic across multiple destinations.
* Agencies managing many partner links from one WordPress dashboard.
* Sites that need aggregate click reporting without visitor-level profiling.

== Installation ==

1. Upload the `kitgenix-affiliate-link-manager` folder to `/wp-content/plugins/`, or install the plugin ZIP through **Plugins > Add New > Upload Plugin**.
2. Activate **Kitgenix Affiliate Link Manager**.
3. Open the Kitgenix Affiliate Link Manager admin screen.
4. Review the redirect prefix and redirect behaviour before publishing links.
5. Create an affiliate link, choose its slug, and enter the destination URL.
6. Add optional scheduling, fallback, rotation, group, tag, and campaign-note settings as required.
7. Test the branded redirect before using it in a live campaign.
8. If destination health checking is enabled, review the health-check interval and timeout for your hosting environment.

After changing a redirect prefix, visit **Settings > Permalinks** and save if WordPress rewrite rules need to be refreshed on your environment.

== Configuration ==

= Redirect prefix =

The prefix forms the path before each link slug. For example, a prefix of `go` and slug of `example-offer` produces a public path similar to `/go/example-offer/`.

Choose a prefix that does not conflict with an existing page, post, taxonomy, endpoint, or other rewrite rule.

= Redirect status =

The plugin allows the redirect status to be configured and exposes a developer filter before the final redirect is issued. Select the status that matches how you intend the branded URL to behave and how permanent the destination relationship is.

= Health-check timing =

Health checks create outbound requests to configured destination URLs. A very aggressive schedule on a site with thousands of links can create unnecessary background HTTP traffic. Use a frequency and timeout appropriate for your catalogue size and hosting limits.

= Uninstall cleanup =

The settings include an uninstall-data choice. By default, uninstall is conservative so an accidental plugin deletion does not automatically destroy affiliate link records and analytics. Enable deletion only when you intentionally want plugin-owned data removed during uninstall.

== Frequently Asked Questions ==

= Does this plugin require an external affiliate network account? =

No. The plugin manages the redirect URLs and analytics inside WordPress. The destination URL can point to an affiliate network, merchant, landing page, or another URL chosen by the site administrator.

= Does Kitgenix receive my click analytics? =

No Kitgenix analytics relay is required for the plugin's click tracking. Aggregate click statistics are stored in the WordPress database on the site running the plugin.

= Does the click tracker store visitors' IP addresses? =

The aggregate analytics implementation does not store visitor IP addresses in its click-statistics records. It also does not store a full user-agent string, full referrer URL, tracking cookie, or browser fingerprint there. Referrer data is reduced to the host for aggregate reporting.

= Can I change an affiliate destination without changing the public link? =

Yes. That is one of the main purposes of a branded redirect. Update the link's destination while keeping its slug/public URL in place.

= Can links start or expire automatically? =

Yes. Link records support start and expiry timestamps. A fallback URL can also be configured for states where the normal destination should not be used.

= Can one link rotate between several destinations? =

Yes. The code supports a rotation mode and a pool of rotation destinations on each link.

= Can I organise a large number of links? =

Yes. Use groups and tags, plus the admin search, filters, sorting, and bulk actions.

= Can I import or export links? =

Yes. CSV and JSON workflows are implemented. The CSV exporter also neutralises values that could otherwise be interpreted as formulas by spreadsheet applications.

= Is there a REST API? =

Yes. The plugin provides authenticated management routes under `kitgenix-affiliate-link-manager/v1` for listing, creating, updating, deleting, importing, exporting, enabling/disabling, duplicating, resetting click counts, and checking destination health.

= Is WP-CLI supported? =

Yes. Use `wp kitgenix-affiliate link` and one of the implemented subcommands.

= Will uninstall delete all affiliate links? =

Not by default. The uninstall behaviour is intentionally conservative. If the delete-data-on-uninstall setting is enabled, the plugin can remove its affiliate link records and plugin-owned analytics data during uninstall.

== Screenshots ==

1. Affiliate Links list with branded short URLs, destinations, click counts, health status, search, filters, sorting, and bulk actions.
2. Add/Edit Link interface with destination, slug, schedule, fallback URL, rotation, group, tags, campaign note, rel guidance, and click count.
3. Analytics snapshot with recent totals, timeline, top sources, top referrers, and top placements.
4. Destination health summary with healthy, warning, and critical states plus on-demand checking.
5. Settings screen for redirect prefix, redirect type, list size, health-check timing, and uninstall cleanup.
6. CSV/JSON import panel for moving or updating link records.
7. Searchable activity log with diagnostic context and outcomes.

== Developers ==

= Data model =

Private post type: `kitgenix_aff_link`

Taxonomies:

* `kitgenix_aff_group`
* `kitgenix_aff_tag`

Primary post meta includes destination URL, click count, rel guidance, enabled state, campaign note, schedule timestamps, fallback URL, rotation settings, and destination-health fields.

The plugin-owned aggregate analytics table is:

`{prefix}kitgenix_aff_click_stats`

The main settings option is:

`kitgenix_affiliate_link_manager_settings`

Additional options are used for analytics schema versioning, aggregate totals, destination-health progress/summary, and the event log.

= REST namespace =

`kitgenix-affiliate-link-manager/v1`

See the Description section above for the route list.

= WP-CLI base command =

`wp kitgenix-affiliate link`

= Filters =

* `kitgenix_affiliate_destination_url` - filter the resolved destination before redirect.
* `kitgenix_affiliate_redirect_status` - filter the HTTP redirect status.
* `kitgenix_affiliate_slug_cache_ttl` - adjust the redirect slug-lookup cache TTL.
* `kitgenix_affiliate_manage_capability` - change the capability required to manage affiliate links.
* `kitgenix_affiliate_health_check_timeout` - adjust destination health-check timeout.
* `kitgenix_affiliate_max_import_items` - adjust the maximum number of import rows. Default: 2,000.
* `kitgenix_affiliate_list_records_limit` - adjust the internal maximum records loaded by applicable REST/WP-CLI list, export, and matching operations. Default: 5,000.

= Actions =

* `kitgenix_affiliate_redirected` - fires after click counting and immediately before the redirect exits. The action receives the post ID, slug, destination, and HTTP status.

= Scheduled events =

The health checker uses plugin scheduled events including `kitgenix_affiliate_run_health_checks` and `kitgenix_affiliate_run_health_checks_once`. A custom `kitgenix_affiliate_thirty_minutes` schedule is registered when the 30-minute interval is used.

== Security & Privacy ==

The plugin is designed so that link management remains an authenticated WordPress administration task. Admin operations use WordPress capabilities and nonce validation where appropriate, and REST management endpoints apply permission checks.

Redirect input and stored fields are sanitised/normalised according to their purpose. Health-check and redirect destinations are URL values supplied by authorised site administrators.

Click analytics are aggregate rather than a persistent visitor profile. The analytics table does not store visitor IP addresses, full user-agent strings, full referrer URLs, cookies, or device fingerprints. Source, placement, and normalised referrer host can be stored as aggregate dimensions.

The plugin can make outbound HTTP requests to administrator-configured destination URLs for health checking. Site owners remain responsible for ensuring their use of affiliate links, analytics, and any destination services complies with applicable privacy, disclosure, advertising, and affiliate-marketing requirements.

== External Services ==

= Affiliate destination websites =

When a visitor follows a branded affiliate link, their browser is redirected to the destination URL configured by the site administrator. That destination is an external service/site selected by the administrator and is not controlled by this plugin. The destination will receive the request after redirect according to normal web behaviour and its own privacy terms.

= Destination health checking =

When destination health checks run, WordPress sends a server-side HTTP request to the destination URL configured for the affiliate link. This is required to determine response status, redirects, and final destination health. The remote destination may receive the requesting server's IP address and standard HTTP request information.

= WordPress.org plugin information =

The shared Kitgenix admin interface can request public plugin-directory information from WordPress.org, such as active-install or rating/media information used in Kitgenix screens. This request is administrative and is not required for public affiliate-link redirects.

WordPress.org terms/privacy: https://wordpress.org/about/privacy/

The plugin does not require a Kitgenix cloud service to process affiliate redirects or aggregate click tracking.

== Uninstall ==

By default, uninstall keeps the affiliate-link records and analytics so deleting the plugin does not unexpectedly destroy campaign history.

If the plugin's delete-data-on-uninstall option is enabled before uninstalling, the cleanup routine can remove plugin-owned affiliate link posts, related metadata/terms, options, transients, and the aggregate analytics table. Review backups and retention requirements before enabling destructive cleanup on a production site.

== Support ==

For product information and Kitgenix resources, visit [Kitgenix](https://kitgenix.com/).

When reporting an issue, include the plugin version, WordPress version, PHP version, relevant reproduction steps, and any non-sensitive diagnostic code shown by the activity/health tools. Do not post affiliate credentials, private destination tokens, or other secrets in public support channels.

== Support Development ==

If Kitgenix Affiliate Link Manager is useful to your site, you can support continued development by leaving a WordPress.org review, sharing constructive feedback, contributing code where appropriate, or using the donation link listed at the top of this readme.

== Credits ==

Kitgenix Affiliate Link Manager is developed by Kitgenix and built on WordPress APIs and conventions.

== Upgrade Notice ==

= 2.0.0 =
Major administration, analytics, health-checking, API, and developer-tooling release. Back up your site before updating and review redirect/health settings after deployment.

== Changelog ==

= 2.0.0 (31 August 2026) =

* New: Redesigned the entire admin experience around the shared Kitgenix design system, with a sticky topbar, light/dark theme toggle, and in-page settings search using "/" or Cmd/Ctrl+K to filter the current tab.
* New: Added a Kitgenix Hub shortcut to the topbar plus a mobile-friendly hamburger menu with dropdown submenus for narrower screens.
* New: Added Image Optimizer to the Kitgenix Hub plugin discovery list.
* New: Added Enable and Disable actions to the Links tab bulk-actions menu alongside Delete, Reset Clicks, and Export CSV.
* New: Added CSV and JSON link importing directly to the Links tab, with matching options by slug, ID, or always creating a new link.
* New: Added a "Check now" row action for running an immediate destination health check on an individual affiliate link.
* New: Added a "Check destination health now" bulk action for checking selected links without waiting for the scheduled health check.
* New: Added a "Run health check now" action to the All Links card header and health summary notice for checking the entire link library on demand.
* New: Added "Set group…" and "Add tags…" bulk actions so large existing link libraries can be reorganised without editing links individually.
* New: Added configurable destination health-check frequency under Settings, with every 30 minutes, hourly, twice-daily, and daily scheduling options.
* New: Added a configurable health-check timeout between 3 and 30 seconds, replacing reliance on the previous developer-only `kitgenix_affiliate_health_check_timeout` filter default.
* New: Added a Click Count field to the Add/Edit Link form and modal so existing click totals can be migrated or set manually without using REST or WP-CLI.
* New: Added `POST /links/{id}/duplicate` to the REST API for duplicating affiliate links programmatically.
* New: Added `POST /links/{id}/reset-clicks` to the REST API for resetting an individual link's click count.
* New: Added `POST /links/{id}/check-health` to the REST API for running an on-demand destination health check.
* New: Added `duplicate`, `reset-clicks`, and `check-health` WP-CLI subcommands so these actions are available from command-line and automation workflows.
* New: REST and WP-CLI create/update operations now accept an explicit `clicks` value, matching the admin interface and CSV/JSON import functionality.
* New: Added a configurable per-import row limit across the admin UI, REST API, and WP-CLI import paths, defaulting to 2,000 rows and filterable through `kitgenix_affiliate_max_import_items`.
* New: Imports that exceed the configured row limit now report how many records were skipped rather than attempting an unbounded request that may time out or exhaust memory.
* New: On WordPress 7.1 and later, the Kitgenix brand icon is registered with the WordPress Icon Registration API as `kitgenix/mark` for discovery through `wp_get_icon()` and the icons REST endpoint.
* Improved: Rebuilt the Links tab and Add/Edit Link forms using the shared Kitgenix card and setting-row components.
* Improved: Replaced traditional checkboxes throughout the Links and settings interfaces with consistent toggle switches.
* Improved: Added a clearer empty-state interface when no affiliate links match the current search or filters.
* Improved: Redesigned admin notices as dismissible, colour-coded success, error, information, and warning banners used consistently across save confirmations, health warnings, and logs.
* Improved: Analytics Snapshot and per-link analytics now use stat tiles and collapsible panels for a more compact and scannable interface.
* Improved: The Log tab now displays entries in a searchable, paginated table containing time, context, outcome, and a plain-English note instead of a plain text block.
* Improved: Destination health checks now record whether a failure was caused by a dead destination, security-plugin block, or temporary network issue instead of collapsing every failure into one generic result.
* Improved: Link save, delete, and duplicate requests rejected by expired security tokens are now recorded in the Log tab instead of only displaying a temporary notice.
* Improved: Link validation and database errors are now recorded in the Log tab for easier diagnostics.
* Improved: Added a reference table to the Log tab explaining what each recorded category means and whether administrator action is required, including distinguishing routine stale security tokens from genuine problems.
* Improved: The Support tab is now four focused cards – a single-button PayPal donate card, a live usage-stats card, a "where a donation goes" summary, and a "get involved" panel for reviews and plugin links.
* Improved: Restyled the Kitgenix Hub using the same colours, typography, spacing, and design tokens as the Affiliate Link Manager settings screens.
* Improved: The Kitgenix Hub now honours the light/dark theme preference already selected within the plugin instead of relying only on the operating system's colour preference.
* Improved: Updated the Kitgenix Hub listing from "MultiStore Sync" to "MultiStore for WooCommerce".
* Improved: The Edit Link modal and other shared Kitgenix dialogs now trap Tab and Shift+Tab keyboard focus inside the active modal, matching their `aria-modal="true"` accessibility semantics.
* Performance: Analytics Snapshot data – including global 7-day and 30-day totals, the 14-day timeline, and top sources, placements, and referrers – is now cached in a short-lived transient instead of rerunning multiple aggregate database queries on every admin page load.
* Performance: Analytics Snapshot caches are invalidated immediately when a new click is recorded or a link's analytics are reset so displayed statistics remain current.
* Performance: REST and WP-CLI link listing, export, and import matching now cap the number of records loaded per call to reduce worst-case memory usage on very large affiliate-link libraries.
* Performance: The REST/WP-CLI record limit defaults to 5,000 links and can be customised using the `kitgenix_affiliate_list_records_limit` filter.
* Performance: The admin Links tab remains unaffected by the new API record cap because it already performs SQL-level pagination.
* Fix: The settings sidebar now sticks directly beneath the topbar using its actual measured height rather than a fixed offset, preventing overlap when the topbar wraps at narrower desktop widths.
* Fix: Number inputs, including the Links Per Page setting, are no longer cramped to a fixed 50px width.
* Fix: The "Add Link" and "Save Settings" buttons now have appropriate spacing from the preceding form fields.
* Fix: Shared admin asset cache-busting now falls back safely when a file modification time cannot be read instead of referencing an undefined constant.
* Fix: CSV/JSON import no longer triggers a fatal error when a manually edited CSV row contains more columns than the header row; excess columns are now safely truncated before processing.
* Fix: CSV imports through both the admin UI and WP-CLI no longer re-process the header row as an additional data row.
* Fix: Corrected CSV import summaries that could previously report an unnecessary failed record, such as "created 5, failed 1" for a valid five-row file.
* Fix: Collapsible-card chevron icons now rotate correctly when Import Links, Link Analytics, and other collapsible cards are opened or closed.
* Fix: The Log tab's "Clear log" action is now available to WooCommerce shop managers, matching other link-management actions, instead of requiring administrator-only `manage_options`.
* Fix: The Settings save handler now rejects only submissions that actually contain a stale or forged settings-form nonce rather than discarding unrelated programmatic option updates that do not contain that field.
* Fix: Uninstall cleanup now runs separately for every site in a WordPress Multisite network so settings, logs, health state, and plugin transients are not left behind on subsites.
* Security: CSV exports now neutralise spreadsheet formula and DDE injection by prefixing potentially executable values with an apostrophe when a Name, Group, Tag, or Note starts with `=`, `+`, `-`, `@`, a tab character, or carriage return.
* Security: CSV export hardening prevents link values saved by lower-privileged users, such as WooCommerce shop managers, from being interpreted as spreadsheet formulas when a higher-privileged administrator later opens an export in Excel, Google Sheets, or similar software.
* Compatibility: Confirmed compatibility with WordPress 7.1.
* Compatibility: The WordPress admin menu icon continues to use the existing `add_menu_page()` icon implementation because the new WordPress Icon Registration API is not consumed by that API.
* Compatibility: Verified the plugin produces no PHP warnings, notices, or deprecation messages on PHP 8.1, 8.2, 8.3, or 8.4 across activation, administration, redirects, REST API requests, WP-CLI commands, cron processing, and uninstall.
* Dev: `Affiliate_Link_Post_Type::upsert_link()` now handles explicit click counts consistently across the admin UI, REST API, WP-CLI, and CSV/JSON import paths.
* Dev: Removed duplicated click-count update logic from the import implementation by consolidating it into `Affiliate_Link_Post_Type::upsert_link()`.
* Dev: Extracted shared modal open/close and keyboard focus-trap behaviour into `kitgenix-admin-components.js` for reuse by the Edit Link modal and other Kitgenix UI components.
* Dev: Added the `kitgenix_affiliate_max_import_items` filter for controlling the maximum number of rows processed by an individual import.
* Dev: Added the `kitgenix_affiliate_list_records_limit` filter for controlling maximum REST/WP-CLI link-record loads.
* Dev: Refreshed the Kitgenix logo, favicon, and WordPress.org listing assets, including plugin icons and banners, using the current brand asset set.
* Dev: Removed an unused CSS rule left over from the Log tab's previous plain-text layout.
* Documentation: Regenerated the plugin's `.pot` translation template, which had fallen several releases behind the current interface.
* Documentation: Expanded the readme Security & Privacy section to document the click-analytics data stored by the plugin.
* Documentation: Expanded the readme Uninstall section to document exactly which settings, logs, health-check data, analytics-related state, and transients are removed from each site under the applicable uninstall configuration.
