=== Kitgenix Affiliate Link Manager ===
Contributors: kitgenix
Donate link: https://buymeacoffee.com/kitgenix
Tags: affiliate, links, redirect, shortlinks, marketing
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.1.3
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

Manage affiliate short links, branded redirects, and click tracking from one WordPress dashboard.

== Description ==

Affiliate URLs can get long, messy, and hard to manage — especially once they’re embedded across pages, posts, emails, and ads.

**Kitgenix Affiliate Link Manager** gives you a simple, central place to create and manage short affiliate links, and redirect visitors using a clean URL format:

`/go/{slug}` (prefix configurable)

It’s designed to be lightweight and practical:
- Create links with a Name, Slug and Destination URL
- Organise larger libraries with link groups, tags, campaign views, and campaign notes
- Schedule start and expiry windows, send inactive traffic to a fallback URL, and rotate between multiple live destinations
- Run automated destination health checks that flag broken offers, merchant-side 404s, and redirecting destinations inside the dashboard
- Track daily click timelines plus source, referrer, and placement attribution so reports show which links and placements perform best
- Manage links through a REST API and WP-CLI commands for create, update, disable, import, and export workflows
- Configure the redirect prefix (default: `go`)
- Choose redirect status: 307, 302 or 301
- Track click counts per link
- No third-party redirect/short-link service (redirects happen on your site)

= Features =

- Search links by name, slug, or destination URL from the main Links tab.
- Organise links with one primary group, multi-tag labels, reusable campaign views, and campaign notes.
- Schedule links with start/end dates, fallback URLs, and sequential or random destination rotation.
- Run hourly health checks against primary, fallback, and rotated destinations, then show dashboard alerts when destinations fail or start redirecting.
- Show a global analytics snapshot plus per-link 14-day timelines, top sources, top referrers, and top placements inside the Links tab.
- Filter large link libraries by group or tag without leaving the main Links tab.
- Use a protected REST API for listing, creating, updating, disabling, importing, and exporting links outside wp-admin.
- Use WP-CLI commands for scripted link creation, updates, disable/enable actions, and JSON/CSV import-export workflows.
- Paginate and sort large link libraries by Name, Slug, and Clicks.
- Duplicate links, reset click counts, bulk delete links, and export selected links to CSV including campaign, schedule, rotation, health, and analytics data.
- Copy generated short URLs with one click.
- Temporarily disable a link without deleting it.
- Choose a per-link rel value: `nofollow`, `sponsored`, or `nofollow sponsored`.
- Tune how many links appear per page (10-200).
- Get warnings when your chosen prefix may conflict with WordPress core URLs or an existing page slug.
- Benefit from atomic click counting and object-cache-friendly slug lookups on busy sites.
- Use the plugin safely on multisite installs with multisite-aware activation, deactivation, and uninstall routines.

SEO note: you can choose a per-link rel value (nofollow / sponsored / both). When placing your short URL into content, use sponsored/nofollow to follow search engine guidelines for affiliate links.

= Notes =

- This plugin stores links in a private custom post type and performs redirects via WordPress rewrite rules.
- If short URLs do not start working immediately after you change the redirect prefix, re-save Permalinks (Settings → Permalinks) so WordPress refreshes rewrite rules.

== Quick Start ==

1. Install and activate the plugin.
2. Go to Kitgenix → Affiliate Links.
3. Add a link (Name + Destination URL + optional custom slug, schedule window, fallback URL, rotation rules, group, tags, and campaign note).
4. Copy the generated short URL and use it anywhere.
5. Use search, campaign views, analytics snapshots, group/tag filters, health alerts, sorting, pagination, or CSV export as your link library grows.

Automation tip: use the REST API or WP-CLI when you want to manage affiliate links in deployment scripts, migrations, or headless workflows.

== Installation ==

1. Install via Plugins → Add New and search for “Kitgenix Affiliate Link Manager”, or upload the ZIP to `/wp-content/plugins/`.
2. Activate the plugin.
3. Open Kitgenix → Affiliate Links.
4. (Optional) Update Settings → Prefix and Redirect Status.
5. If you changed the prefix, re-save Settings → Permalinks.

== Frequently Asked Questions ==

= What URL format does it use? =
By default it redirects from `/go/{slug}`. You can change the prefix in settings.

= Does it track clicks? =
Yes. Each redirect increments a click counter stored against the link.

= Can I export or bulk-manage links? =
Yes. The Links tab supports bulk delete, bulk click reset, and CSV export for the selected links, including campaign metadata, destination rules, health fields, and analytics summary columns.

= Can I organise links into campaigns? =
Yes. Each link can have one primary group, multiple tags, and an optional campaign note. The Links tab also adds reusable campaign views and group/tag filters so large libraries stay manageable.

= Can I schedule or rotate destinations? =
Yes. Each link can now have an optional start date, expiry date, fallback URL, and additional destinations for sequential or random rotation while the link is active.

= Does it warn me about broken offers or redirect chains? =
Yes. The plugin runs automated destination health checks, stores the latest result against each link, and shows dashboard alerts when a destination returns an error or starts redirecting before the final page loads.

= Does it include analytics timelines and attribution reports? =
Yes. The Links tab now shows a global analytics snapshot, and each link editor view includes recent daily trends plus top sources, referrers, and placements based on tracked short-link visits.

= Does it include a REST API? =
Yes. The plugin now includes management endpoints for listing, creating, updating, disabling, importing, and exporting affiliate links outside wp-admin. The API is intended for authenticated site owners, agencies, and automation pipelines.

= Does it include WP-CLI commands? =
Yes. You can create, update, enable, disable, delete, import, and export links from the command line, which is useful for scripted rollouts, migrations, and maintenance tasks.

= Can I temporarily disable a link without deleting it? =
Yes. Each link has an Enabled toggle. Disabled links stop redirecting until you enable them again.

= Can I use a 301 redirect? =
Yes, but it is generally better to use a temporary redirect for affiliate links. Use 307 (recommended) or 302 unless you have a specific reason to make the redirect permanent.

= Should I add nofollow/sponsored? =
Yes. When you add the short URL to a post/page, tag the link with `rel="nofollow"` or `rel="sponsored"` (ideally both) to comply with search engine guidelines.

= Does it require WooCommerce? =
No.

= Can it handle large link libraries? =
Yes. The admin list supports server-side search across names, destinations, groups, tags, campaign notes, and health messages, plus campaign views, analytics snapshots, group/tag filters, health alerts, sorting, pagination, configurable links-per-page limits, and object-cache-friendly slug lookups.

= Is it multisite compatible? =
Yes. Activation, deactivation, rewrite flushing, and uninstall cleanup are multisite-aware.

== Screenshots ==

1. Affiliate Links list view with search, campaign views, analytics snapshots, group/tag filters, destination health alerts, sorting, pagination, and quick actions.
2. Add/Edit Affiliate Link modal with validation, schedule windows, fallback URLs, rotation settings, groups, tags, campaign notes, rel selection, and enable toggle.
3. Bulk actions including delete, reset clicks, and CSV export with campaign, schedule, rotation, health, and analytics metadata.
4. Settings: redirect prefix, redirect status, links per page, and uninstall cleanup.
5. Support tab with totals, links, and quick copy tools.

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
- `{prefix}kitgenix_aff_click_stats` — stores daily aggregated click counts by link, source, placement, and referrer host.

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
- Link save nonce action: `kitgenix_affiliate_link_save`
- Link save nonce field name: `kitgenix_affiliate_link_nonce`
- Link delete nonce action: `kitgenix_affiliate_link_delete`
- Link delete nonce query arg: `nonce`
- Link duplicate nonce action: `kitgenix_affiliate_link_duplicate`
- Link reset clicks nonce action: `kitgenix_affiliate_link_reset_clicks`
- Bulk actions nonce action: `kitgenix_affiliate_link_bulk`
- Bulk actions nonce field name: `kitgenix_affiliate_link_bulk_nonce`
- Settings save nonce action: `kitgenix_affiliate_link_manager_settings_save`
- Settings save nonce field name: `kitgenix_affiliate_link_manager_settings_nonce`

Settings UI field identifiers:
- Redirect status <select> id: `kitgenix_affiliate_redirect_status`
- Links per page <input> id: `kitgenix_affiliate_links_per_page`

Developer filters:
- `kitgenix_affiliate_slug_cache_ttl` (int $ttl, int $post_id, string $slug) — adjust redirect slug lookup cache TTL (seconds).
- `kitgenix_affiliate_destination_url` (string $destination_url, int $post_id) — filter the final redirect destination before redirecting.
- `kitgenix_affiliate_redirect_status` (int $status, int $post_id) — filter the HTTP redirect status per request.
- `kitgenix_affiliate_manage_capability` (string $capability) — override the capability required to manage links.
- `kitgenix_affiliate_health_check_timeout` (int $timeout, string $url) — adjust the outbound timeout used by destination health checks.

REST API:
- Namespace: `kitgenix-affiliate-link-manager/v1`
- `GET /wp-json/kitgenix-affiliate-link-manager/v1/links` — list links with optional `search`, `group`, `tag`, `enabled`, `orderby`, `order`, `page`, and `per_page` parameters.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links` — create a link, including optional `starts_at`, `expires_at`, `fallback_url`, `rotation_mode`, and `rotation_destinations` fields.
- `GET /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}` — fetch one link.
- `PUT|PATCH /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}` — update a link, including schedule and rotation settings.
- `DELETE /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}` — move a link to the Trash.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}/disable` — disable a link.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links/{id}/enable` — re-enable a link.
- `GET /wp-json/kitgenix-affiliate-link-manager/v1/links/export` — export filtered links as structured rows.
- `POST /wp-json/kitgenix-affiliate-link-manager/v1/links/import` — import links with `match_existing=slug|id|none` and a `links` array payload.
- Link responses and exports now include health fields such as `health_status`, `health_message`, `health_checked_at`, `health_http_code`, and `health_redirect_hops`.
- Link responses and exports now also include analytics fields such as `clicks_last_7_days`, `clicks_last_30_days`, `timeline_14_days`, `top_sources_30_days`, `top_referrers_30_days`, and `top_placements_30_days`.

WP-CLI:
- Base command: `wp kitgenix-affiliate link`
- Subcommands: `get`, `create`, `update`, `disable`, `enable`, `delete`, `export`, `import`
- `create` / `update` accept `--starts-at`, `--expires-at`, `--fallback-url`, `--rotation-mode`, and `--rotation-destinations`.
- `export` supports JSON or CSV output.
- `import` supports JSON or CSV input and can match existing links by slug or ID.
- `get` and `export` output the stored destination health summary fields alongside the rest of the link record.
- `get` and `export` also output analytics summary fields, including recent timelines and top attribution breakdowns.

Scheduled events:
- Hourly health-check event: `kitgenix_affiliate_run_health_checks`
- Initial catch-up event: `kitgenix_affiliate_run_health_checks_once`

Developer actions:
- `kitgenix_affiliate_redirected` (int $post_id, string $destination_url, int $status) — fires after the click is counted and before the redirect exits.

== External Services ==

This plugin includes a shared “Kitgenix hub” component in wp-admin which may fetch publicly available plugin metadata from WordPress.org using WordPress core’s `plugins_api()` function.

Caching:
- Transient: `kitgenix_hub_wporg_active_installs_v1`
- Transient: `kitgenix_hub_wporg_ratings_v1`
- Transient: `kitgenix_hub_wporg_media_v1`

Redirect destinations:
- When a visitor uses a short URL like `/go/{slug}`, the plugin redirects them to the destination URL you configured for that link. Destination URLs are commonly on third-party domains (affiliate programs).

== Security & Privacy ==

- No tracking cookies are added.
- Admin actions are protected with capability checks and nonces.
- Redirect destinations are validated before redirect.

== Uninstall ==

By default, uninstall removes only plugin settings and plugin-only transients (it does not delete stored affiliate link posts or click data).

Optional: enable the “Delete all affiliate links and click data when the plugin is uninstalled” setting if you want a clean uninstall.

Deleted:
- Option: `kitgenix_affiliate_link_manager_settings`
- Transient: `kitgenix_affiliate_link_manager_do_activation_redirect`
- Transient (short-lived): `kitgenix_affiliate_link_manager_do_rewrite_flush`

== Support Development ==

If this plugin saves you time managing affiliate URLs, you can support ongoing development here:
https://buymeacoffee.com/kitgenix

== Credits ==
Built with ❤︎ by @kitgenix - https://kitgenix.com

== Upgrade Notice ==

= 1.1.3 =
Recommended for all websites.

== Changelog ==
= 1.1.3 (26 May 2026) =
* Compatibility: Confirmed compatibility with WordPress 7.0.
* New: Added a Log tab to the admin settings page. Records recent plugin activity (settings saves and key operations) with timestamps, context labels, and plain-English notes to aid troubleshooting.
* Fix: Resolved a missing class file include that would have caused a fatal error when visiting the Log tab.
* Fix: Activity log data is now fully cleaned up when the plugin is uninstalled.

= 1.1.2 (26 May 2026) =
* Dev: Skipped to be in line with other Kitgenix Plugins

= 1.1.1 (26 May 2026) =
* Dev: Skipped to be in line with other Kitgenix Plugins

= 1.1.0 (7 May 2026) =
* New: Added link groups so each affiliate link can be assigned to a reusable campaign view.
* New: Added link tags for labelling traffic sources, content types, and partner groupings.
* New: Added optional campaign notes to store placement context, reminders, or partner-specific details.
* New: Added campaign views and group/tag filters to the Links tab for faster navigation across large link libraries.
* New: Added a protected REST API for listing, creating, updating, disabling, importing, and exporting affiliate links outside wp-admin.
* New: Added WP-CLI commands for scripted link management, including create, update, enable, disable, import, and export workflows.
* New: Added JSON and CSV import-export support for automation pipelines and deployment tooling.
* New: Added per-link start dates and expiry dates so offers can go live and retire automatically.
* New: Added fallback URLs for links that are not live yet or have already expired.
* New: Added sequential and random destination rotation using the primary destination plus optional extra URLs.
* New: Added automated destination health checks for primary, fallback, and rotated URLs.
* New: Added dashboard alerts for broken offers, merchant-side 404s, and redirecting destinations.
* New: Added a daily analytics timeline for each link plus a global analytics snapshot inside the Links tab.
* New: Added source, referrer, and placement attribution using tracked short-link visits.
* Improvement: Added analytics summary fields to shared link records, REST responses, WP-CLI output, and CSV exports.
* Improvement: Search now matches campaign groups, tags, and campaign notes in addition to the existing link fields.
* Improvement: Duplicated links now retain their campaign metadata.
* Improvement: CSV exports now include link group, tags, and campaign note columns.
* Improvement: Added schedule and rotation fields to the admin editor, REST API, WP-CLI commands, and CSV exports.
* Improvement: Added destination health fields to shared link records, REST responses, WP-CLI output, and CSV exports.
* Fix: Prevented a PHP fatal when rendering affiliate-link 404 responses on themes that do not provide a 404 template.

= 1.0.1 (19 March 2026) =
* Fix: Added missing translators comments for pluralized admin notices.
* Fix: Hardened admin table escaping, pagination rendering, CSV export output, uninstall cleanup, and click-count bookkeeping to satisfy WordPress coding standards.
* UI: Improved the Kitgenix admin header layout for better alignment and less clutter.
* UI: Social links in admin headers now render as compact icon buttons (with accessible labels).
* UI: Added responsive header helpers so titles/description and actions/links lay out consistently.
* Fix: Admin notices now display above the Kitgenix header using the WordPress standard notice area.
* Fix: Added defensive notice normalization to prevent notices being relocated into the header by other scripts.
* UI: Admin tables inside Kitgenix pages now use Kitgenix styling for a more consistent branded look.
* Fix: Added spacing between adjacent action links/buttons (e.g., Edit/Delete).
* New: Links list now supports pagination.
* New: Links list now supports server-side search (name, slug, destination).
* New: Links list now supports server-side sorting by Name, Slug, and Clicks.
* New: Added bulk actions for deleting links, resetting click counts, and exporting selected links to CSV.
* New: Added per-link actions to duplicate a link and reset its click count.
* New: Added a “Links per page” setting (10–200) to control list pagination size.
* New: Added an “Enabled” toggle per link to temporarily disable redirects without deleting the link.
* Dev: Added a developer filter to tune redirect slug lookup cache TTL.
* UI: Improved keyboard accessibility for the Edit Link modal (focus trap + restore focus).
* Improvement: Added inline validation for slug and destination URL fields in the admin UI.
* Improvement: Support tab click totals are calculated more efficiently on large sites.
* Improvement: Redirect handling now supports object-cache-friendly slug lookups for better performance on high-traffic links.
* Dev: Added developer hooks around redirects (destination URL/status filters and a redirect action).
* Improvement: Settings now warn when the redirect prefix may conflict with WordPress/core URLs or an existing Page slug.
* Cleanup: Added an optional “delete data on uninstall” setting for clean uninstalls.
* Fix: Click counting is now atomic to avoid missed increments under concurrent traffic.
* Fix: Admin link actions now permit WooCommerce managers (when WooCommerce is installed).
* Dev: Capability required to manage links is now filterable for developers.
* Fix: Escaped shared Kitgenix hub card media output for WordPress coding standards compliance.
* Maintenance: Updated the plugin Author URI to the public Kitgenix WordPress.org profile and replaced the old custom admin-menu icon CSS with the native Dashicons icon.

= 1.0.0 (01 March 2026) =
* New: Initial release.
* New: Create and manage affiliate links with Name, Slug, and Destination URL.
* New: Redirect visitors via clean short URLs using `/go/{slug}` (prefix configurable).
* New: Settings to configure the redirect prefix and redirect status (307/302/301).
* New: Click tracking per link.
* New: Optional per-link rel guidance (nofollow / sponsored / both) for SEO compliance.
* New: Admin UI for listing, adding, and editing affiliate links.
* New: Links are stored in a private custom post type.
* New: Redirect handling via WordPress rewrite rules.
* Security: Added capability checks and nonces for admin actions.
* Security: Added destination URL validation prior to redirect.
* Security: No external services and no tracking cookies.
