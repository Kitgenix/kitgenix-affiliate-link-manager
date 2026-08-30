=== Kitgenix Affiliate Link Manager ===
Contributors: kitgenix
Donate link: https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2
Tags: affiliate-links, link-cloaking, url-shortener, redirect, click-tracking, link-tracking, campaign-tracking, analytics, link-management, affiliate-marketing, shortlinks, rest-api
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 2.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Plugin URI: https://wordpress.org/plugins/kitgenix-affiliate-link-manager/
Author: Kitgenix
Author URI: https://kitgenix.com/
Author Plugin URI: https://kitgenix.com/plugins/kitgenix-affiliate-link-manager
Documentation URI: https://kitgenix.com/plugins/kitgenix-affiliate-link-manager/documentation
Support URI: https://wordpress.org/support/plugin/kitgenix-affiliate-link-manager/
Author Support URI: https://kitgenix.com/plugins/kitgenix-affiliate-link-manager/support
Feature Request URI: https://kitgenix.com/plugins/kitgenix-affiliate-link-manager/feature-request

Cloak, redirect, and track affiliate links with branded short URLs, campaigns, link scheduling, and destination health checks.

== Description ==

**Kitgenix Affiliate Link Manager** is a self-hosted affiliate link cloaking and management plugin for WordPress. It replaces long, messy affiliate URLs with short, branded links that redirect through your own domain, so you can update, track, and clean them up from one dashboard instead of hunting through years of old content. It's built for bloggers, review sites, and affiliate publishers who place the same offers across many posts, pages, emails, and ads.

Every link redirects through your own domain in a short, configurable format such as `/go/{slug}`, cloaking the raw affiliate URL behind a clean short link. Behind the scenes, the plugin counts clicks, attributes traffic to sources and placements, groups links into campaigns, and checks destinations on a schedule so broken or redirecting offers get caught before your readers do.

= Key Features =

- **Create short affiliate links:** turn any destination URL into a branded short link with a name, custom or auto-generated slug, a configurable redirect prefix (default `go`), and a choice of 307, 302, or 301 redirect status.
- **Search and organise:** find links by name, slug, or destination URL, then group them with one primary group, multi-tag labels, reusable campaign views, and campaign notes.
- **Schedule and rotate:** set start/end dates, a fallback URL, and sequential or random destination rotation for each link.
- **Monitor destination health automatically:** run destination health checks against primary, fallback, and rotated destinations on a schedule you control, with dashboard alerts when a destination fails or starts redirecting.
- **Track clicks and attribution:** view a global analytics snapshot plus per-link 14-day timelines, top sources, top referrers, and top placements inside the Links tab.
- **Filter large libraries:** narrow big link libraries by group or tag without leaving the main Links tab.
- **Automate with a REST API:** list, create, update, disable, import, and export links through a protected REST API outside wp-admin.
- **Automate with WP-CLI:** script link creation, updates, disable/enable actions, and JSON/CSV import-export workflows from the command line.
- **Paginate and sort:** browse large link libraries sorted by Name, Slug, or Clicks.
- **Bulk manage links:** duplicate, reset click counts, delete, enable/disable, assign a group or tags, trigger a destination health check, or export to CSV in bulk – including campaign, schedule, rotation, health, and analytics data.
- **Import links:** bring in a CSV or JSON file directly in the admin UI, matching existing links by slug or ID, or always creating new links, using the same format as CSV export.
- **Check health on demand:** trigger a destination health check for one link, a bulk selection, or your entire library, without waiting for the next scheduled run.
- **Tune health-check timing:** set the check frequency (30 minutes / hourly / twice daily / daily) and per-request timeout from Settings.
- **Set a starting click count:** give a link an exact starting click total from the admin UI – useful when migrating from another shortlink tool.
- **Copy short URLs:** copy any generated short URL with one click.
- **Disable without deleting:** temporarily pause a link's redirect without losing its data.
- **Choose a per-link rel value:** `nofollow`, `sponsored`, or `nofollow sponsored`.
- **Control list size:** show 10–200 links per page.
- **Avoid prefix conflicts:** get a warning when your chosen prefix may clash with WordPress core URLs or an existing page slug.
- **Perform well on busy sites:** benefit from atomic click counting and object-cache-friendly slug lookups.
- **Keep redirects on your own site:** no third-party redirect or short-link service is required – links resolve entirely on your own domain.
- **Run safely on multisite:** multisite-aware activation, deactivation, and uninstall routines.

SEO note: you can choose a per-link rel value (nofollow / sponsored / both). When placing your short URL into content, use sponsored/nofollow to follow search engine guidelines for affiliate links.

= Link Cloaking & Redirects =

Every affiliate link gets its own branded short URL on your domain, in the form `/{prefix}/{slug}` (the prefix defaults to `go` and is fully configurable). WordPress rewrite rules handle the redirect, so no raw affiliate URL is ever exposed in your content. Choose 307 (temporary, recommended), 302 (temporary), or 301 (permanent) as the redirect status, either globally in Settings or per-request via a developer filter. Each link can also have a fallback URL and up to several rotation destinations, cycled sequentially or randomly, for split-testing offers or handling expired promotions gracefully.

= Click Tracking & Analytics =

Every redirect increments an atomic click counter and logs an aggregated analytics row against the link's date, traffic source, and placement. The Links tab shows a global analytics snapshot (7-day and 30-day totals, a 14-day timeline, and top sources/placements/referrers), and each link's editor view shows the same breakdown scoped to that link. Source and placement are read from standard `utm_source`/`utm_content`/`utm_campaign` style query parameters when present, falling back to the visitor's referrer hostname or "Direct".

= Organizing Links =

Links can be grouped into one primary group, tagged with multiple labels, and annotated with a free-text campaign note. The Links tab adds group/tag filters and reusable campaign views so libraries with hundreds or thousands of links stay manageable, alongside server-side search, sorting by Name/Slug/Clicks, and SQL-level pagination.

= Destination Health Checks =

A scheduled background job (30 minutes / hourly / twice daily / daily, your choice) requests each link's primary, fallback, and rotation destinations and records whether they responded successfully, redirected, or failed – distinguishing a dead destination from a security-plugin block or a temporary network issue. Results surface as dashboard alerts, and you can trigger an immediate check for a single link, a bulk selection, or your whole library at any time.

= Developer Features =

- A REST API namespace (`kitgenix-affiliate-link-manager/v1`) for listing, creating, updating, disabling, duplicating, resetting clicks, checking health, exporting, and importing links.
- WP-CLI commands (`wp kitgenix-affiliate link ...`) covering the same operations for scripted deployments and migrations.
- Filters to adjust the redirect destination, redirect status, slug-lookup cache TTL, health-check timeout, manage capability, and import/export record limits.
- An action that fires immediately after a click is counted and before the redirect exits.
- See the Developers section below for the full reference of hooks, meta keys, and routes.

= Compatibility =

- Works standalone; WooCommerce is not required. If WooCommerce is active, WooCommerce Shop Managers can also manage affiliate links.
- Multisite-aware activation, deactivation, rewrite flushing, and uninstall cleanup, run per-site.
- No tracking cookies. Click analytics never store IP addresses, user agents, or other visitor-identifying data – see Security & Privacy below.
- Confirmed compatible with WordPress 7.1 and PHP 8.1–8.4.

= How It Works =

1. Go to Kitgenix → Affiliate Links and add a link with a Name and Destination URL, plus any optional custom slug, schedule window, fallback URL, rotation rules, group, tags, or campaign note.
2. Copy the generated short URL and use it in place of the raw affiliate link anywhere on your site.
3. As your link library grows, use search, campaign views, analytics snapshots, group/tag filters, health alerts, sorting, pagination, and CSV export to keep it manageable.

Automation tip: use the REST API or WP-CLI to manage affiliate links in deployment scripts, migrations, or headless workflows.

= Notes =

- This plugin stores links in a private custom post type and performs redirects via WordPress rewrite rules.
- If short URLs do not start working immediately after you change the redirect prefix, re-save Permalinks (Settings → Permalinks) so WordPress refreshes rewrite rules.

== Installation ==

1. Install via Plugins → Add New and search for "Kitgenix Affiliate Link Manager", or upload the plugin ZIP to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Open Kitgenix → Affiliate Links to add your first link.
4. (Optional) Adjust Settings → Prefix and Redirect Status to match your preference.
5. If you changed the prefix after activation, re-save Settings → Permalinks so WordPress picks up the new rewrite rule.

== External Services ==

This plugin includes a shared "Kitgenix hub" component in wp-admin which may fetch publicly available plugin metadata from WordPress.org using WordPress core's `plugins_api()` function. This is used only to display install counts, star ratings, and icons/banners for Kitgenix plugins on the Kitgenix admin hub screen (Kitgenix → Kitgenix), and only runs for logged-in administrators viewing that screen. No visitor data, site content, or affiliate link data is ever sent as part of these requests. Results are cached in transients (see below) to minimise the number of outbound requests.

Caching:
- Transient: `kitgenix_hub_wporg_active_installs_v1`
- Transient: `kitgenix_hub_wporg_ratings_v1`
- Transient: `kitgenix_hub_wporg_media_v1`

WordPress.org's Plugin API is provided by the WordPress.org team; see the WordPress.org Privacy Policy (https://wordpress.org/about/privacy/) and Terms of Service (https://wordpress.org/about/terms-of-service/) for details on that service.

Redirect destinations:
- When a visitor uses a short URL like `/go/{slug}`, the plugin redirects them to the destination URL you configured for that link. Destination URLs are commonly on third-party domains (affiliate programs). This is a core, expected function of the plugin – no destination URL, click data, or visitor information is sent anywhere other than the browser being redirected.

== Frequently Asked Questions ==

= What URL format do the short affiliate links use? =
By default, links redirect from `/go/{slug}`. You can change the prefix in Settings.

= Does this plugin track affiliate link clicks? =
Yes. Each redirect increments a click counter stored against the link.

= Does it include click analytics and attribution reports? =
Yes. The Links tab shows a global analytics snapshot, and each link editor view includes recent daily trends plus top sources, referrers, and placements based on tracked short-link visits.

= Can I organise affiliate links into campaigns? =
Yes. Each link can have one primary group, multiple tags, and an optional campaign note. The Links tab also adds reusable campaign views and group/tag filters so large libraries stay manageable.

= Can I schedule links or rotate between multiple destinations? =
Yes. Each link can have an optional start date, expiry date, fallback URL, and additional destinations for sequential or random rotation while the link is active.

= Does it warn me about broken affiliate offers or redirect chains? =
Yes. The plugin runs destination health checks on a schedule you control (30 minutes / hourly / twice daily / daily), stores the latest result against each link, and shows dashboard alerts when a destination returns an error or starts redirecting before the final page loads. You can also trigger an immediate check for one link, a bulk selection, or your whole library at any time, instead of waiting for the next scheduled run.

= Can I export or bulk-manage my affiliate links? =
Yes. The Links tab supports bulk delete, bulk enable/disable, bulk click reset, bulk group/tag assignment, bulk destination health checks, and CSV export for the selected links, including campaign metadata, destination rules, health fields, and analytics summary columns. You can also import a CSV or JSON file of links directly from the Links tab.

= Does it include a REST API? =
Yes. The plugin includes management endpoints for listing, creating, updating, disabling, importing, and exporting affiliate links outside wp-admin. The API is intended for authenticated site owners, agencies, and automation pipelines.

= Does it include WP-CLI commands? =
Yes. You can create, update, enable, disable, delete, import, and export links from the command line, which is useful for scripted rollouts, migrations, and maintenance tasks.

= Can I temporarily disable an affiliate link without deleting it? =
Yes. Each link has an Enabled toggle. Disabled links stop redirecting until you enable them again.

= Can I use a permanent 301 redirect for affiliate links? =
Yes, but it is generally better to use a temporary redirect for affiliate links. Use 307 (recommended) or 302 unless you have a specific reason to make the redirect permanent.

= Should affiliate links use nofollow or sponsored attributes? =
Yes. When you add the short URL to a post or page, tag the link with `rel="nofollow"` or `rel="sponsored"` (ideally both) to comply with search engine guidelines.

= Do I need WooCommerce to use this plugin? =
No, WooCommerce is not required.

= Will this plugin slow down my site, even with a large link library? =
No. Click counting is atomic, slug lookups are object-cache-friendly, and the admin analytics snapshot is cached. The admin Links tab also performs server-side search and SQL-level pagination, so browsing, filtering, and sorting stay responsive as your library grows into the hundreds or thousands of links.

= Is this plugin compatible with WordPress Multisite? =
Yes. Activation, deactivation, rewrite flushing, and uninstall cleanup are multisite-aware.

= Does the plugin store visitor data or personal information? =
No. Click tracking stores aggregated counts by link, date, source, and placement, plus a truncated referrer hostname – never IP addresses, user agents, cookies, or other visitor-identifying data. See the Security & Privacy section for full details.

= Can I migrate affiliate links from another shortlink plugin? =
Yes. Import your existing links via CSV or JSON (matching by slug or ID, or creating new links), and set each link's starting click count from the admin UI, REST API, or WP-CLI so historical totals carry over.

= What happens to my links and click data if I uninstall the plugin? =
By default, uninstalling removes only the plugin's settings, activity log, and health-check state – your affiliate link posts and click data are kept. If you want a full clean uninstall instead, enable the "Delete all affiliate links and click data when the plugin is uninstalled" option in Settings before removing the plugin. See the Uninstall section for the complete list of what is and isn't removed.

== Screenshots ==

1. The Affiliate Links list, showing the short URL, destination, click count, and health status for each link alongside search, group/tag filters, and sorting controls.
2. The Add/Edit Affiliate Link screen, where a destination URL, custom slug, schedule window, fallback URL, rotation destinations, group, tags, campaign note, and rel value are set for a single link.
3. The Analytics Snapshot, showing global 7-day and 30-day click totals, a 14-day click timeline, and the top sources, referrers, and placements driving traffic.
4. The destination health summary, showing healthy, warning, and critical counts across the link library plus a list of the affected links and a "Run health check now" action.
5. The Settings screen, covering the redirect prefix, redirect status (307/302/301), links per page, health check frequency and timeout, and the uninstall data-cleanup option.
6. The Import Links panel, where a CSV or JSON file is uploaded and matched against existing links by slug, ID, or always created as new links.
7. The Log tab, listing recorded events such as settings saves, link validation errors, and health-check results in a searchable, paginated table with plain-English explanations.

== Developers ==

Text domain:
kitgenix-affiliate-link-manager

Custom post type:
- `kitgenix_aff_link`

Taxonomies:
- Link groups: `kitgenix_aff_group`
- Link tags: `kitgenix_aff_tag`

Post meta:
- Destination URL: `_kitgenix_affiliate_destination_url`
- Click count: `_kitgenix_affiliate_clicks`
- Rel value: `_kitgenix_affiliate_rel` (allowed: `nofollow`, `sponsored`, `nofollow sponsored`)
- Enabled flag: `_kitgenix_affiliate_enabled` (1/0; defaults to enabled)
- Campaign note: `_kitgenix_affiliate_campaign_note`
- Starts at: `_kitgenix_affiliate_starts_at` (UTC timestamp)
- Expires at: `_kitgenix_affiliate_expires_at` (UTC timestamp)
- Fallback URL: `_kitgenix_affiliate_fallback_url`
- Rotation mode: `_kitgenix_affiliate_rotation_mode` (`none`, `sequential`, `random`)
- Rotation destinations: `_kitgenix_affiliate_rotation_destinations` (stored as an array)
- Health status: `_kitgenix_affiliate_health_status`
- Health message: `_kitgenix_affiliate_health_message`
- Health checked at: `_kitgenix_affiliate_health_checked_at` (UTC timestamp)
- Health HTTP code: `_kitgenix_affiliate_health_http_code`
- Health final URL: `_kitgenix_affiliate_health_final_url`
- Health redirect hops: `_kitgenix_affiliate_health_redirect_hops`

Analytics table:
- `{prefix}kitgenix_aff_click_stats` – stores daily aggregated click counts by link, source, placement, and referrer host.

Settings option:
- `kitgenix_affiliate_link_manager_settings`

Settings group (Settings API):
- `kitgenix_affiliate_link_manager_settings_group`

Redirect query var:
- `kitgenix_affiliate_slug`

Admin actions / nonces:
- Admin-post action (save): `admin_post_kitgenix_affiliate_link_save`
- Admin-post action (delete): `admin_post_kitgenix_affiliate_link_delete`
- Admin-post action (duplicate): `admin_post_kitgenix_affiliate_link_duplicate`
- Admin-post action (reset clicks): `admin_post_kitgenix_affiliate_link_reset_clicks`
- Admin-post action (bulk actions): `admin_post_kitgenix_affiliate_link_bulk`
- Admin-post action (check health, single link): `admin_post_kitgenix_affiliate_link_check_health`
- Admin-post action (check health, all links): `admin_post_kitgenix_affiliate_run_health_checks_now`
- Admin-post action (import): `admin_post_kitgenix_affiliate_link_import`
- Link save nonce action: `kitgenix_affiliate_link_save`
- Link save nonce field name: `kitgenix_affiliate_link_nonce`
- Link delete nonce action: `kitgenix_affiliate_link_delete`
- Link delete nonce query arg: `nonce`
- Link duplicate nonce action: `kitgenix_affiliate_link_duplicate`
- Link reset clicks nonce action: `kitgenix_affiliate_link_reset_clicks`
- Link check health (single) nonce action: `kitgenix_affiliate_link_check_health`
- Check health (all links) nonce action: `kitgenix_affiliate_run_health_checks_now`
- Bulk actions nonce action: `kitgenix_affiliate_link_bulk`
- Bulk actions nonce field name: `kitgenix_affiliate_link_bulk_nonce`
- Bulk actions field name (action value, e.g. group name or tags): `bulk_value`
- Import nonce action: `kitgenix_affiliate_link_import`
- Import nonce field name: `kitgenix_affiliate_link_import_nonce`
- Settings save nonce action: `kitgenix_affiliate_link_manager_settings_save`
- Settings save nonce field name: `kitgenix_affiliate_link_manager_settings_nonce`

Settings UI field identifiers:
- Redirect status <select> id: `kitgenix_affiliate_redirect_status`
- Links per page <input> id: `kitgenix_affiliate_links_per_page`
- Health check frequency <select> id: `kitgenix_affiliate_health_check_frequency` (settings key: `health_check_frequency`, values: `thirty_minutes`, `hourly`, `twicedaily`, `daily`)
- Health check timeout <input> id: `kitgenix_affiliate_health_check_timeout` (settings key: `health_check_timeout`, seconds, 3–30)

Developer filters:
- `kitgenix_affiliate_slug_cache_ttl` (int $ttl, int $post_id, string $slug) – adjust redirect slug lookup cache TTL (seconds).
- `kitgenix_affiliate_destination_url` (string $destination_url, int $post_id) – filter the final redirect destination before redirecting.
- `kitgenix_affiliate_redirect_status` (int $status, int $post_id) – filter the HTTP redirect status per request.
- `kitgenix_affiliate_manage_capability` (string $capability) – override the capability required to manage links.
- `kitgenix_affiliate_health_check_timeout` (int $timeout, string $url) – adjust the outbound timeout used by destination health checks; defaults to the Settings → Health check timeout value.
- `kitgenix_affiliate_max_import_items` (int $max) – adjust the maximum number of rows processed per CSV/JSON import call (default 2,000); rows beyond this limit are reported back as `skipped` instead of processed.
- `kitgenix_affiliate_list_records_limit` (int $max) – adjust the maximum number of links loaded per REST/WP-CLI list, export, or import-matching call (default 5,000). Does not affect the admin Links tab, which always paginates in SQL.

REST API:
- Namespace: `kitgenix-affiliate-link-manager/v1`
- `GET /wp-json/kitgenix-affiliate-link-manager/v1/links` – list links with optional `search`, `group`, `tag`, `enabled`, `orderby`, `order`, `page`, and `per_page` parameters.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links` – create a link, including optional `starts_at`, `expires_at`, `fallback_url`, `rotation_mode`, `rotation_destinations`, and `clicks` fields.
- `GET /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}` – fetch one link.
- `PUT|PATCH /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}` – update a link, including schedule, rotation, and `clicks` settings.
- `DELETE /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}` – move a link to the Trash.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}/disable` – disable a link.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}/enable` – re-enable a link.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}/duplicate` – duplicate a link, including campaign, schedule, and rotation settings.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}/reset-clicks` – reset a link's click count to 0.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}/check-health` – run a destination health check for one link immediately.
- `GET /wp-json/kitgenix-affiliate-link-manager/v1/links/export` – export filtered links as structured rows.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links/import` – import links with `match_existing=slug|id|none` and a `links` array payload. The response includes `created`, `updated`, `failed`, and `skipped` counts (`skipped` is non-zero only if the payload exceeded the per-import row limit) plus a per-row `results` array.
- Link responses and exports now include health fields such as `health_status`, `health_message`, `health_checked_at`, `health_http_code`, and `health_redirect_hops`.
- Link responses and exports now also include analytics fields such as `clicks_last_7_days`, `clicks_last_30_days`, `timeline_14_days`, `top_sources_30_days`, `top_referrers_30_days`, and `top_placements_30_days`.

WP-CLI:
- Base command: `wp kitgenix-affiliate link`
- Subcommands: `get`, `create`, `update`, `disable`, `enable`, `duplicate`, `reset-clicks`, `check-health`, `delete`, `export`, `import`
- `create` / `update` accept `--starts-at`, `--expires-at`, `--fallback-url`, `--rotation-mode`, `--rotation-destinations`, and `--clicks`.
- `duplicate` accepts `--by` and `--porcelain` and duplicates campaign, schedule, and rotation settings.
- `reset-clicks` resets a link's click count to 0.
- `check-health <identifier>` checks one link immediately; `check-health --all` checks every published link.
- `export` supports JSON or CSV output.
- `import` supports JSON or CSV input and can match existing links by slug or ID; a warning is printed if the file exceeds the per-import row limit.
- `get` and `export` output the stored destination health summary fields alongside the rest of the link record.
- `get` and `export` also output analytics summary fields, including recent timelines and top attribution breakdowns.

Scheduled events:
- Health-check event: `kitgenix_affiliate_run_health_checks` – runs on the interval chosen in Settings → Health check frequency (default hourly; also supports every 30 minutes, twice daily, or daily).
- Initial catch-up event: `kitgenix_affiliate_run_health_checks_once`
- Custom cron schedule: `kitgenix_affiliate_thirty_minutes` (registered via the `cron_schedules` filter when the 30-minute frequency is selected).

Developer actions:
- `kitgenix_affiliate_redirected` (int $post_id, string $destination_url, int $status) – fires after the click is counted and before the redirect exits.

WordPress 7.1 Icon Registration API:
- On WordPress 7.1+, the plugin registers its brand mark as `kitgenix/mark` via `wp_register_icon_collection()`/`wp_register_icon()`, making it available through `wp_get_icon('kitgenix/mark')` and the `/wp-json/wp/v2/icons` REST endpoint. This is unrelated to the wp-admin top-level menu icon, which continues to use a base64-encoded SVG `icon_url` (the only format `add_menu_page()` supports) on every supported WordPress version.

== Security & Privacy ==

- No tracking cookies are added.
- Admin actions are protected with capability checks and nonces; nonce failures are logged (not treated as an attack) and fail closed.
- Redirect destinations are validated before redirect, and only `http`/`https` URLs are ever redirected to – `javascript:`, `data:`, `file:`, and similar schemes are rejected.
- Destination health checks reuse WordPress core's outbound-request safety checks (`reject_unsafe_urls`), a short response-size limit, a configurable timeout, and a bounded redirect-hop limit, so a malicious or misconfigured destination URL cannot be used to probe your server's internal network.
- CSV exports neutralise formula/DDE injection: any cell beginning with `=`, `+`, `-`, `@`, a tab, or a carriage return is prefixed with an apostrophe before being written, so a link name or note containing a spreadsheet formula cannot execute when the export is opened in Excel/Sheets.

What click analytics store:
- Each recorded click stores: the affiliate link ID, the local calendar date, a short "source" label (from a `utm_source`/`source`/`ref` query parameter if present, otherwise the visitor's referrer hostname, otherwise "Direct"), a short "placement" label (from `placement`/`utm_content`/`utm_campaign`/`utm_medium`/`slot`/`variant` if present, otherwise "Unassigned"), and the visitor's referrer hostname truncated to 64 characters (e.g. `example.com`, never the full referring URL or its query string).
- Rows are aggregated per link/date/source/placement/referrer combination (an existing row's counter increments rather than a new row being written per click), not stored as individual per-visitor events.
- No IP addresses, user agents, cookies, device/browser fingerprints, or any other visitor-identifying data are ever stored by this plugin.

== Uninstall ==

By default, uninstall removes only plugin settings, the activity log, health-check state, and plugin-only transients – it does not delete stored affiliate link posts or click data. This cleanup runs per-site on multisite so no subsite is left with orphaned plugin data.

Optional: enable the "Delete all affiliate links and click data when the plugin is uninstalled" setting if you want a clean uninstall – this additionally removes every affiliate link post and drops the click-analytics table, per-site on multisite.

Always deleted (per site):
- Options: `kitgenix_affiliate_link_manager_settings`, `kitgenix_affiliate_link_manager_total_clicks`, `kitgenix_affiliate_link_manager_analytics_db_version`, `kitgenix_affiliate_link_manager_health_summary`, `kitgenix_affiliate_link_manager_health_cursor`, `kitgenix_affiliate_link_manager_event_log`
- Transients: `kitgenix_affiliate_link_manager_do_activation_redirect`, `kitgenix_affiliate_link_manager_do_rewrite_flush`, `kitgenix_affiliate_link_manager_overview_snapshot`

Deleted only when "Delete all affiliate links and click data" is enabled:
- All `kitgenix_aff_link` posts and their post meta
- The `{prefix}kitgenix_aff_click_stats` table

== Support Development ==

If this plugin saves you time managing affiliate URLs, you can support ongoing development with a donation via PayPal:
https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2

== Credits ==
Built with ❤︎ by @kitgenix - https://kitgenix.com

== Upgrade Notice ==

= 2.0.0 =
Confirmed compatibility with WordPress 7.1. Recommended for all websites – adds bulk enable/disable, admin-UI CSV/JSON import, on-demand health checks, bulk group/tag assignment, and configurable health check timing.

== Changelog ==

= 2.0.0 (25 August 2026) =

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
