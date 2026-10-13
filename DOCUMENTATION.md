# Kitgenix Affiliate Link Manager

## Overview

**Plugin Name:** Kitgenix Affiliate Link Manager
**Plugin URI:** https://wordpress.org/plugins/kitgenix-affiliate-link-manager/
**Description (from the main file docblock):** "Manage affiliate short links, branded redirects, and click tracking from one WordPress dashboard."
**Version:** 2.1.0 (constant `KitgenixAffiliateLinkManager_Version` / `KITGENIX_AFFILIATE_LINK_MANAGER_VERSION`, defined in `kitgenix-affiliate-link-manager.php`).
**Requires at least:** WordPress 6.0
**Tested up to:** WordPress 7.1
**Requires PHP:** 8.1
**Author:** Kitgenix (https://kitgenix.com/)
**License:** GPLv3 or later (https://www.gnu.org/licenses/gpl-3.0.html)
**Text Domain:** `kitgenix-affiliate-link-manager`
**Domain Path:** `/languages`
**WooCommerce:** not required. WooCommerce is only *detected* (`class_exists('WooCommerce')`) to broaden the management capability from `manage_options` to `manage_woocommerce` so WooCommerce shop managers can administer links; the plugin has no other WooCommerce dependency (no product/order integration).

The plugin's core purpose is to let a WordPress site publish stable, first‑party short URLs (e.g. `example.com/go/product-name/`) that redirect to admin‑managed, changeable destination URLs, while recording aggregate (non‑personal) click analytics and periodically checking that destinations are still healthy.

## Architecture

### Folder / file layout

```
kitgenix-affiliate-link-manager.php        Main plugin bootstrap file (also hosts the shared "Kitgenix Hub" cross-plugin admin page)
uninstall.php                              WP_UNINSTALL_PLUGIN cleanup routine
readme.txt                                 WordPress.org plugin readme (SVN-style)
CHANGELOG.md                               Human-maintained changelog (ahead of readme.txt)
languages/
  kitgenix-affiliate-link-manager.pot      Translation template (no .po/.mo files shipped)
includes/core/
  class-settings.php                       Reads/normalizes the single plugin settings option
  class-event-log.php                      Capped, auto-pruning admin activity log (options-backed)
  class-affiliate-link-post-type.php       CPT + taxonomies + all link CRUD/import/export/query logic (the domain model)
  class-analytics.php                      Custom DB table + aggregate click analytics (snapshots, timelines, breakdowns)
  class-redirector.php                     Rewrite rule + template_redirect handler that performs the actual redirect
  class-health-checks.php                  Destination health checking (HTTP HEAD/GET probing) + WP-Cron scheduling
  class-rest-api.php                       REST API routes under kitgenix-affiliate-link-manager/v1
  class-loader.php                         Central init() that wires up all Core classes on plugins_loaded
  class-cli-command.php                    WP-CLI `wp kitgenix-affiliate link ...` commands (only loaded when WP_CLI is true)
includes/admin/
  class-admin-options.php                  register_setting()/sanitize_callback for the settings option
  class-settings-ui.php                    The entire wp-admin UI: menu, tabs, forms, tables, modal, admin-post.php handlers (~2,900 lines)
assets/css/
  admin.css                                Plugin-specific admin page layout (links grid, campaign views, etc.)
  kitgenix-admin-ui.css                    Shared Kitgenix design system (CSS custom properties, topbar, cards, tables, toggles, modal, toasts) — intentionally kept byte-identical across all Kitgenix plugins
  kitgenix-hub.css                         Styles for the shared "Kitgenix" top-level hub page (plugin discovery grid)
assets/js/
  admin.js                                 Plugin-specific behaviour: auto-slug, inline validation, edit-modal data fill, copy-to-clipboard, bulk-action value toggle, select-all
  kitgenix-admin-components.js             Shared component controller: toasts, modal open/close + focus trap, collapsible cards, copy-to-clipboard helper, live table search/pagination (shared across Kitgenix plugins)
  kitgenix-admin-tabs.js                   Shared topbar/tabs controller: tab switching, in-page settings search ("/" or Cmd/Ctrl+K), mobile hamburger menu, dropdowns, light/dark theme toggle (localStorage), notice relocation, sticky-offset measurement
assets/images/                             Logos, favicons, banners, screenshots, social icons (static assets, no PHP)
```

### Bootstrap sequence

All bootstrap code lives directly in `kitgenix-affiliate-link-manager.php` (no autoloader class — a flat `require_once` chain):

1. **File is loaded by WordPress.** `defined('ABSPATH') || exit;` guards direct access.
2. A handful of **shared, cross-plugin helper functions** are defined if not already defined by a sibling Kitgenix plugin (`kitgenix_get_admin_menu_icon()`, `kitgenix_ensure_admin_menu()`, `kitgenix_affiliate_link_manager_register_icons()`, `kitgenix_hub_get_wporg_active_installs()`, `kitgenix_hub_get_wporg_ratings()`, `kitgenix_hub_get_wporg_media()`, `kitgenix_render_admin_page()`, `kitgenix_affiliate_link_manager_register_admin_ui_style()`). These build/maintain a shared top-level **"Kitgenix" admin menu** (`admin_menu` priority 5) used by the whole Kitgenix plugin family, and a "Hub" page (`admin.php?page=kitgenix`) that lists other Kitgenix plugins (install/activate/open/review) using cached (`get_transient`/`set_transient`, 1-day TTL) WordPress.org `plugins_api()` lookups for install counts, ratings, and banner/icon media.
3. **WordPress 7.1 Icon Registration API** integration: on `init`, `kitgenix_affiliate_link_manager_register_icons()` calls `wp_register_icon_collection('kitgenix', …)` and `wp_register_icon('kitgenix/mark', …)` when those functions exist, exposing the brand SVG via `wp_get_icon()`/the `/wp-json/wp/v2/icons` REST endpoint. This is independent of (and does not replace) the classic `add_menu_page()` icon, which still uses a base64 data URI built by `kitgenix_get_admin_menu_icon()`.
4. Shared admin CSS (`kitgenix-admin-ui.css`) is registered on `admin_enqueue_scripts` priority 5; the Hub page's own CSS (`kitgenix-hub.css`) is enqueued only when `$_GET['page'] === 'kitgenix'`.
5. **Constants are defined** (guarded with `defined()` checks so re-inclusion or multi-load is harmless):
   - `KitgenixAffiliateLinkManager_Version` / `KITGENIX_AFFILIATE_LINK_MANAGER_VERSION` = `2.1.0`
   - `KITGENIX_AFFILIATE_LINK_MANAGER_FILE` = `__FILE__`
   - `KitgenixAffiliateLinkManager_Path` / `KITGENIX_AFFILIATE_LINK_MANAGER_DIR` = plugin dir path
   - `KitgenixAffiliateLinkManager_URL` = plugin dir URL
   - `KitgenixAffiliateLinkManager_Includes_Path` = `.../includes/`
6. **`require_once` chain** loads, in order: `core/class-settings.php`, `core/class-event-log.php`, `core/class-affiliate-link-post-type.php`, `core/class-analytics.php`, `core/class-redirector.php`, `core/class-health-checks.php`, `core/class-rest-api.php`, `core/class-loader.php`, then conditionally `core/class-cli-command.php` (only `if ( defined('WP_CLI') && WP_CLI )`), then `admin/class-admin-options.php` and `admin/class-settings-ui.php`.
7. **Admin classes are initialized immediately** (not deferred to a hook): `Admin_Options::init()` and `Settings_UI::init()` are called at file-load time — both internally just register their own `admin_init`/`admin_menu` hooks, so this is safe even though it runs before `plugins_loaded`.
8. On **`plugins_loaded`**, `Loader::init()` is called if the `Loader` class exists (defensive `class_exists` check with an `admin_notices` fallback error message if not — guards against a corrupted/partial install). `Loader::init()` calls, in order: `Affiliate_Link_Post_Type::init()`, `Analytics::init()`, `Redirector::init()`, `Health_Checks::init()`, `Event_Log::init()`, `Rest_API::init()`, and `CLI_Command::init()` if that class exists. It also hooks `update_option_kitgenix_affiliate_link_manager_settings` to flush rewrite rules when the redirect prefix changes.
9. **`register_activation_hook`**: validates minimum PHP (8.1) and WordPress (6.0) versions (deactivates itself and `wp_die()`s with an explanatory message if not met), then for the current site (or every site in the network if `$network_wide` is true) re-initializes the Loader, installs the analytics DB table, schedules health checks, registers the rewrite rule, and calls `flush_rewrite_rules()`. It also sets a 2-minute transient `kitgenix_affiliate_link_manager_do_rewrite_flush` as a fallback (some hosts don't persist the activation-time flush reliably) and a 30-second transient `kitgenix_affiliate_link_manager_do_activation_redirect` to redirect the admin to the plugin's settings page after activation (skipped on bulk activation, detected via `$_GET['activate-multi']`).
10. **`register_deactivation_hook`**: clears the health-check cron hooks and the event-log prune cron hook, flushes rewrite rules, and deletes the activation-redirect transient. It does **not** delete any data (see Uninstall section).
11. A `plugin_action_links_{basename}` filter adds a "Settings" link on the Plugins screen.

### Autoloading / class-loading approach

There is **no PSR-4 autoloader and no Composer**. Every class file is pulled in via an explicit `require_once` in the main plugin file, in a fixed, dependency-aware order. All classes live under the `KitgenixAffiliateLinkManager\Core` or `KitgenixAffiliateLinkManager\Admin` namespaces and use **static methods only** — nothing in the plugin is ever instantiated with `new` (except native WordPress/PHP objects like `\WP_Error`, `\WP_Post`, `\DateTimeImmutable`). Every class exposes a static `init()` that self-registers its own WordPress hooks; classes are otherwise stateless (aside from a couple of static in-memory caches, e.g. `Analytics::$link_snapshots`).

### Key classes and responsibilities

| Class | File | Responsibility |
|---|---|---|
| `Core\Settings` | `class-settings.php` | Thin read layer over the single settings option: prefix, redirect status, links-per-page, health-check frequency/timeout, delete-on-uninstall flag, and `build_short_url()`. |
| `Core\Event_Log` | `class-event-log.php` | Capped (100 entries), auto-pruning (7/14/30-day retention) activity log stored in a WP option; records settings saves, link save/validation errors, nonce failures, health-check outcomes, and imports. Provides `get_category_and_note()` mapping structured event codes to human-readable severity/guidance. |
| `Core\Affiliate_Link_Post_Type` | `class-affiliate-link-post-type.php` | The domain model. Registers the `kitgenix_aff_link` CPT and its two taxonomies; owns all sanitization/validation, CRUD (`upsert_link`, `delete_link`, `duplicate_link`), schedule/rotation/fallback resolution (`resolve_redirect_destination`), click counting (`increment_clicks`, atomic SQL where possible), bulk group/tag assignment, CSV/JSON import (`import_links`) and export flattening, and `list_link_records()` (the in-PHP filter/search/sort engine used by REST, WP-CLI, and export). ~1,600 lines — the largest and most central class. |
| `Core\Analytics` | `class-analytics.php` | Creates/upgrades a custom DB table (`{prefix}kitgenix_aff_click_stats`), records one aggregate row per link/day/source/placement/referrer-host, and computes 7/30-day totals, 14-day timelines, and top-5 source/placement/referrer breakdowns — both per-link and site-wide (with a 5-minute transient cache for the site-wide "overview" snapshot). |
| `Core\Redirector` | `class-redirector.php` | Registers the rewrite rule/query var for `{prefix}/{slug}`, and on `template_redirect` resolves the slug (using a short-lived object-cache lookup), evaluates schedule/rotation/enabled state, applies developer filters, increments the click counter, fires an action, and issues the redirect (or a 404). |
| `Core\Health_Checks` | `class-health-checks.php` | HTTP HEAD/GET probing of primary/fallback/rotation destination URLs (manual redirect-following up to 5 hops, transport-error classification), storage of per-link health postmeta, a site-wide summary option, WP-Cron-driven batch checking with a resumable cursor, and on-demand single/all checks. |
| `Core\Rest_API` | `class-rest-api.php` | Registers all REST routes under `kitgenix-affiliate-link-manager/v1`; thin controller layer over `Affiliate_Link_Post_Type` and `Health_Checks`. |
| `Core\Loader` | `class-loader.php` | Single `init()` entry point that wires every Core class together on `plugins_loaded`, plus a rewrite-flush-on-prefix-change listener. |
| `Core\CLI_Command` (`extends \WP_CLI_Command`) | `class-cli-command.php` | `wp kitgenix-affiliate link ...` subcommands mirroring the REST/admin capabilities, plus CSV/JSON file import/export. |
| `Admin\Admin_Options` | `class-admin-options.php` | `register_setting()` + the settings `sanitize_callback`, including reserved-prefix / page-slug-conflict warnings via `add_settings_error()`. |
| `Admin\Settings_UI` | `class-settings-ui.php` | The entire wp-admin experience: menu registration, asset enqueueing, all 4 tabs (Links, Settings, Log, Support) rendering, the Add/Edit form and its duplicate Edit modal, CSV export, CSV/JSON import upload handling, and every `admin_post_*` handler. |

## Features

Every feature below is implemented end-to-end in the classes listed; see "Data Flow" further down for the full request lifecycle.

- **Branded short links.** Each link is a private `kitgenix_aff_link` post. Its public URL is `home_url('/' . prefix . '/' . slug . '/')`, built by `Settings::build_short_url()`. The prefix is a single site-wide setting; the slug is per-link (auto-generated from the title via WordPress's own slug uniquification if left blank, or explicitly settable/validated for uniqueness against other links of the same post type).
- **Destination management.** `_kitgenix_affiliate_destination_url` postmeta stores the current destination; it can be changed at any time without changing the public slug.
- **Enable/disable.** `_kitgenix_affiliate_enabled` postmeta (defaults to enabled when absent) lets an admin 404 a link without deleting it.
- **Rel guidance.** `_kitgenix_affiliate_rel` stores one of `nofollow`, `sponsored`, `nofollow sponsored` (default), applied as the `rel` attribute on the admin-visible destination link and available in exports.
- **Scheduling (start/expiry).** `_kitgenix_affiliate_starts_at` / `_kitgenix_affiliate_expires_at` store Unix timestamps (parsed from ISO 8601, a site-local `datetime-local` string, or a raw numeric timestamp via `DateTimeImmutable`). `Affiliate_Link_Post_Type::get_redirect_state()` classifies a link as `disabled`, `scheduled`, `expired`, or `active` at request time.
- **Fallback URL.** `_kitgenix_affiliate_fallback_url` — used instead of the primary destination whenever the redirect state is `scheduled` or `expired`. A `disabled` link always 404s regardless of any fallback URL set — `Redirector::maybe_redirect()` intercepts the `disabled` state before the resolved fallback destination is ever used (see "Frontend Behavior"). If no fallback is set and the state isn't active, the visitor gets a 404.
- **Destination rotation.** `_kitgenix_affiliate_rotation_mode` (`none` | `sequential` | `random`) plus `_kitgenix_affiliate_rotation_destinations` (an array of extra URLs). The rotation pool is always the primary destination plus the extra URLs, deduplicated. Sequential rotation is `click_count % pool_size` (deterministic, not stateful/pointer-based); random uses `wp_rand()`.
- **Groups & tags.** Two custom (non-public, `show_ui => false`) taxonomies: `kitgenix_aff_group` (hierarchical, one term per link enforced in code even though WordPress itself would allow multiple) and `kitgenix_aff_tag` (flat, many per link).
- **Campaign notes.** `_kitgenix_affiliate_campaign_note` free-text field (sanitized with `sanitize_textarea_field`).
- **Click counting.** `_kitgenix_affiliate_clicks` postmeta, incremented via a raw SQL `UPDATE ... SET meta_value = CAST(meta_value AS UNSIGNED) + 1` for atomicity under concurrent traffic, with PHP-level fallbacks if the direct SQL path fails or the meta row doesn't exist yet. A derived option `kitgenix_affiliate_link_manager_total_clicks` caches the sum across all published links (kept in sync incrementally via `adjust_total_clicks()`, with a full recompute available via `refresh_total_clicks()`).
- **Aggregate click analytics.** Every click also inserts/increments a row in the custom `{prefix}kitgenix_aff_click_stats` table, keyed by link, date, a normalized "source" (from `utm_source`/`source`/`src`/`ref`/`referrer` query args or the referrer host), a normalized "placement" (from `placement`/`utm_content`/`utm_campaign`/`utm_medium`/`slot`/`variant`), and the referrer host. No IP address, full user-agent, full referrer URL, or cookie is stored.
- **Destination health checks.** Automatic (WP-Cron, configurable frequency) and on-demand (single link, or "run for all") HTTP checks of every destination URL a link could resolve to (primary + fallback + rotation pool), manually following redirects up to 5 hops, classifying results as `healthy` / `warning` (redirects) / `critical` (4xx/5xx, redirect loop, dead connection) / `unknown`, and distinguishing genuine dead links from WP's own SSRF-protection blocks or transient network errors.
- **CSV/JSON import & export.** Available from the admin Links tab, the REST API, and WP-CLI, all funneling through the same `Affiliate_Link_Post_Type::import_links()` / `list_link_records()` + `flatten_link_record_for_export()` code paths. CSV export neutralizes formula/DDE injection by prefixing values starting with `=`, `+`, `-`, `@`, a tab, or a carriage return with a literal apostrophe.
- **Bulk admin actions.** Enable, Disable, Set group…, Add tags…, Check destination health now, Reset clicks, Export CSV, Delete — all via one `admin_post_kitgenix_affiliate_link_bulk` handler keyed by a `bulk_action` select value.
- **REST API & WP-CLI** — see their own sections below.
- **Activity log.** A capped, human-readable event log with a reference table in the admin UI explaining what each event code means and whether it needs attention (deliberately distinguishing "stale nonce" from a real attack, and "transient network hiccup" from a genuinely broken link).
- **Kitgenix Hub.** A shared top-level "Kitgenix" admin page (defined in the main plugin file, present in every Kitgenix plugin) listing sibling Kitgenix plugins with install/activate/open/review actions and live WordPress.org metadata.

> **Note on disabled links:** `resolve_redirect_destination()` itself treats any non-`'active'` state (`disabled`, `scheduled`, or `expired`) identically and would return the fallback URL for all three if used alone. However, `Redirector::maybe_redirect()` — the actual request-handling entry point — checks for the `disabled` state first and renders a genuine 404 before the resolved fallback is ever consulted. So in practice a disabled link always 404s; the fallback URL only ever takes effect for `scheduled`/`expired` links.

## Admin Interface

### Menus

- **Top-level "Kitgenix" menu** (slug `kitgenix`, icon a base64-encoded SVG, position 58, capability `manage_woocommerce` if WooCommerce is active else `manage_options`) — registered once per site by whichever Kitgenix plugin loads first (`kitgenix_ensure_admin_menu()`, idempotent via `$admin_page_hooks` check). Its callback `kitgenix_render_admin_page()` renders the plugin-discovery Hub grid.
- **Submenu "Affiliate Links"** (page slug `kitgenix-affiliate-link-manager`, menu title "Affiliate Links", page title "Affiliate Link Manager") registered under `kitgenix` by `Settings_UI::register_menu()` at `admin_menu` priority 20, using the same capability logic (`Settings_UI::get_manage_capability()` — filterable via `kitgenix_affiliate_manage_capability`).

The single admin page (`Settings_UI::render_page()`) is a client-side-tabbed single-page app (tab state kept in the URL `?tab=` query var + `#kitgenix-tab-*` hash, synced by `kitgenix-admin-tabs.js`) with four tabs:

#### Links tab (`render_links_tab()`)

- **Add/Edit Link card** — a form (`admin-post.php?action=kitgenix_affiliate_link_save`) with fields: Name (`title`, required), Slug (`slug`, optional/auto), Destination URL (`destination_url`, required, `type="url"`), Start date/time (`starts_at`, `datetime-local`), Expiry date/time (`expires_at`), Fallback URL (`fallback_url`), Rotation mode (`rotation_mode`: none/sequential/random), Additional rotation destinations (`rotation_destinations`, one URL per line, textarea), Link group (`group_name`, free text with a `<datalist>` of existing group names), Tags (`tag_names`, comma-separated), Campaign note (`campaign_note`, textarea), Link rel (`rel` select), Enabled (`enabled` toggle switch, defaults on), and — only in edit mode — Click count (`clicks`, numeric, lets an admin set an exact total e.g. when migrating from another tool).
- The same form is duplicated as a **modal dialog** (`#kitgenix-affiliate-edit-modal`, `role="dialog"` `aria-modal="true"`) populated client-side from the clicked row's `data-*` attributes (`kitgenix-admin-components.js` handles the modal mechanics/focus-trap; `admin.js`'s `bindModalEditor()` fills the fields) so editing from the table doesn't navigate away.
- **Import Links card** (collapsible) — uploads a `.csv`/`.json` file to `admin-post.php?action=kitgenix_affiliate_link_import`, with a "Match existing links by" select (`slug` / `id` / `none`).
- **Campaign views** — pill-style quick filters: "All links", one per existing group term (only if it has ≥1 link), "Ungrouped" (if any exist).
- **Analytics Snapshot panel** — site-wide 7/30-day totals, 14-day timeline summary, top sources/referrers/placements (from `Analytics::get_overview_snapshot()`).
- **All Links table** — server-paginated (`Settings::get_links_per_page()`, 10–200), server-searched (title/slug/destination/campaign-note/group-or-tag-term-name via a `posts_join`/`posts_where`/`posts_distinct` filter trio scoped to this specific `WP_Query`), sortable columns Name/Clicks (via `posts_clauses` for clicks, since it's postmeta), filterable by group/tag. Each row shows name, disabled/scheduled/expired/health-alert pills, destination (clickable, `rel` applied), short URL + Copy button, click count, and row actions: Edit, Duplicate, Check now (health), Reset clicks, Delete.
- **Bulk actions** (checkbox column + a `<select>`): Enable, Disable, Set group… (needs a text value), Add tags… (needs a text value), Check destination health now, Reset clicks, Export CSV, Delete.
- **"Run health check now"** button in the card header (checks every published link immediately, bypassing the cron cursor/batching).

#### Settings tab (`render_settings_tab()`)

Form posts to `options.php` (native WordPress Settings API) with `settings_fields('kitgenix_affiliate_link_manager_settings_group')` plus an *additional* custom nonce field (`kitgenix_affiliate_link_manager_settings_nonce`, action `kitgenix_affiliate_link_manager_settings_save`) checked inside the `sanitize_callback`. Fields (all stored inside the single array option `kitgenix_affiliate_link_manager_settings`):

| Field | Option key | Type / range | Default |
|---|---|---|---|
| Link prefix | `prefix` | text, `sanitize_title_with_dashes()`, trimmed of slashes | `go` |
| Redirect type | `redirect_status` | select: 307 (Temporary, recommended) / 302 / 301 | `307` |
| Links per page | `links_per_page` | number, 10–200 | `50` |
| Health check frequency | `health_check_frequency` | select: `thirty_minutes` / `hourly` / `twicedaily` / `daily` | `hourly` |
| Health check timeout | `health_check_timeout` | number (seconds), 3–30 | `8` |
| Data removal (uninstall) | `delete_data_on_uninstall` | toggle | off (`0`) |

Saving triggers two settings-error warnings (non-blocking, just informational `add_settings_error()` calls) if the prefix is one of the reserved words `wp-admin`, `wp-json`, `wp-login`, `xmlrpc`, `feed`, or if a WordPress Page already exists at that slug.

#### Log tab (`render_log_tab()`)

- Auto-delete-logs-after select (7/14/30 days, option `kitgenix_affiliate_link_manager_log_retention_days`, saved via its own `admin_post_kitgenix_affiliate_link_manager_save_log_retention` handler) and a "Clear log" button (`admin_post_kitgenix_affiliate_link_manager_clear_event_log`).
- A searchable, client-side-paginated (`data-kitgenix-table-paginate="25"`) table of the last up-to-100 log entries: Time, Context, Outcome (colour-coded badge), Note.
- A static reference table (`render_log_code_reference_table()`) explaining each structured event category (e.g. `cached-or-expired-page`, `link-healthy`, `link-broken`, `validation-error`, `database-error`) in plain English and whether it needs action.

#### Support tab (`render_support_tab()`)

A single hero card: PayPal donate button (`https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2`), "Leave a review", "Read the docs", and "Copy plugin link" (clipboard, with a `window.prompt` fallback for non-secure contexts). No dynamic data is fetched here (the `readme.txt` changelog mentions a "live usage-stats card" from an earlier design iteration that is not present in the current code).

### Assets/behaviour common to the whole admin page

- A **sticky topbar** (logo, plugin name/version, tab links, a search box that filters the currently visible tab's `.kitgenix-card`/`.kitgenix-setting-row`/etc. elements by text match, a light/dark theme toggle persisted to `localStorage['kitgenixAdminTheme']`, a link back to the Kitgenix Hub, and a mobile hamburger menu).
- A **sidebar** (`render_sidebar()`) with "Need Help?" (docs + support links), Facebook group, and a social-icon grid.
- Every server-rendered admin notice type (`kitgenix_saved`, `kitgenix_deleted`, `kitgenix_duplicated`, `kitgenix_reset`, `kitgenix_bulk_*`, `kitgenix_health_checked`, `kitgenix_health_check_all`, `kitgenix_import_*`, `kitgenix_error`) is read from `$_GET` (all `(int)`/`sanitize_text_field()` cast — no nonce needed since these are purely display flags, not state-changing) in `render_admin_notices()` and shown via the shared dismissible `.kitgenix-notice` component.
- A **health summary notice** (`render_health_summary_notice()`) surfaces critical/warning/pending counts site-wide with deep links to the affected links and a "Run health check now" action, whenever `Health_Checks::get_summary()` has anything to report.

## Hooks Reference

### WordPress/WooCommerce core hooks the plugin hooks INTO

| Hook | File:Line (approx) | Purpose |
|---|---|---|
| `admin_menu` (priority 5) | `kitgenix-affiliate-link-manager.php` | `kitgenix_ensure_admin_menu()` — create the shared top-level Kitgenix menu once. |
| `admin_menu` (priority 20) | `Settings_UI::register_menu()` | Register the "Affiliate Links" submenu. |
| `init` | `kitgenix-affiliate-link-manager.php` | Register the WP 7.1 icon collection/icon. |
| `init` | `Redirector::add_rewrite()` | Register the rewrite tag + rewrite rule for `{prefix}/{slug}`. |
| `init` | `Affiliate_Link_Post_Type::register_post_type()` / `register_taxonomies()` | Register CPT + taxonomies. |
| `init` | `Analytics::maybe_install_table()` (priority 1) | Create/upgrade the click-stats table if the stored DB version differs. |
| `init` | `Health_Checks::maybe_schedule_health_checks()` | (Re)schedule the health-check cron if unscheduled or the frequency setting changed. |
| `init` | `Event_Log::maybe_schedule_prune()` | Schedule the daily log-prune cron if not already scheduled. |
| `init` (priority 20) | `kitgenix-affiliate-link-manager.php` | One-time fallback `flush_rewrite_rules()` guarded by the `kitgenix_affiliate_link_manager_do_rewrite_flush` transient. |
| `admin_init` | `Admin_Options::register_settings()` | `register_setting()` for the settings option. |
| `admin_init` | `kitgenix-affiliate-link-manager.php` | Perform the post-activation redirect (guarded by transient, skipped on bulk activation). |
| `admin_enqueue_scripts` (priority 5) | `kitgenix-affiliate-link-manager.php` | Register the shared `kitgenix-admin-ui.css`. |
| `admin_enqueue_scripts` | `kitgenix-affiliate-link-manager.php` | Enqueue `kitgenix-hub.css` + Thickbox on the Hub page only. |
| `admin_enqueue_scripts` | `Settings_UI::enqueue_assets()` | Enqueue this plugin's admin CSS/JS, scoped to its own page hook. |
| `admin_post_kitgenix_affiliate_link_save` | `Settings_UI::handle_link_save()` | Create/update a link. |
| `admin_post_kitgenix_affiliate_link_delete` | `Settings_UI::handle_link_delete()` | Trash a link. |
| `admin_post_kitgenix_affiliate_link_duplicate` | `Settings_UI::handle_link_duplicate()` | Duplicate a link. |
| `admin_post_kitgenix_affiliate_link_reset_clicks` | `Settings_UI::handle_link_reset_clicks()` | Reset one link's click count/analytics. |
| `admin_post_kitgenix_affiliate_link_bulk` | `Settings_UI::handle_link_bulk()` | All bulk row-checkbox actions, including CSV export. |
| `admin_post_kitgenix_affiliate_link_check_health` | `Settings_UI::handle_link_check_health()` | On-demand single-link health check. |
| `admin_post_kitgenix_affiliate_run_health_checks_now` | `Settings_UI::handle_run_health_checks_now()` | On-demand all-links health check. |
| `admin_post_kitgenix_affiliate_link_import` | `Settings_UI::handle_link_import()` | CSV/JSON upload import. |
| `admin_post_kitgenix_affiliate_link_manager_clear_event_log` | `Settings_UI::handle_clear_event_log()` | Clear the activity log. |
| `admin_post_kitgenix_affiliate_link_manager_save_log_retention` | `Settings_UI::handle_save_log_retention()` | Save log retention days. |
| `update_option_kitgenix_affiliate_link_manager_settings` | `Loader::maybe_flush_rewrites()` | Flush rewrites if the `prefix` changed. |
| `update_option_kitgenix_affiliate_link_manager_settings` | `Settings_UI::on_settings_saved()` | Record a `settings-saved` event-log entry. |
| `template_redirect` | `Redirector::maybe_redirect()` | The actual short-link → destination redirect. |
| `query_vars` | `Redirector::add_query_vars()` | Register `kitgenix_affiliate_slug`. |
| `rest_api_init` | `Rest_API::register_routes()` | Register all REST routes. |
| `plugins_loaded` | `kitgenix-affiliate-link-manager.php` | `Loader::init()` — wire up every Core class. |
| `cron_schedules` | `Health_Checks::register_cron_schedules()` | Register the custom `kitgenix_affiliate_thirty_minutes` interval. |
| `posts_join` / `posts_where` / `posts_distinct` | `Settings_UI::render_links_tab()` (closures, added/removed per-request) | Extend the admin Links-tab `WP_Query` to search postmeta + taxonomy term names. |
| `posts_clauses` | `Settings_UI::render_links_tab()` (closure) | Sort the admin Links-tab query by click count (postmeta, cast to unsigned). |
| `allowed_redirect_hosts` | `Redirector::maybe_redirect()` (closure) | Allow the current destination's host through `wp_safe_redirect()` for that one request. |
| `plugin_action_links_{basename}` | `kitgenix-affiliate-link-manager.php` | Add a "Settings" row-action link on the Plugins list. |
| `register_activation_hook` | `kitgenix-affiliate-link-manager.php` | Version checks, table install, cron scheduling, rewrite flush, activation-redirect transient. |
| `register_deactivation_hook` | `kitgenix-affiliate-link-manager.php` | Clear health-check + log-prune cron, flush rewrites. |

### Custom filters the plugin defines (documented `apply_filters` calls)

| Filter | Args | Default | Where applied |
|---|---|---|---|
| `kitgenix_affiliate_destination_url` | `$destination, $post_id, $slug` | resolved destination | `Redirector::maybe_redirect()`, before the final redirect. |
| `kitgenix_affiliate_redirect_status` | `$status, $post_id, $slug, $destination` | `Settings::get_redirect_status()` (301/302/307) | `Redirector::maybe_redirect()`. |
| `kitgenix_affiliate_slug_cache_ttl` | `$ttl, $post_id, $slug` | `10 * MINUTE_IN_SECONDS` | `Redirector::maybe_redirect()` — TTL for the object-cache slug→post-ID lookup. |
| `kitgenix_affiliate_manage_capability` | `$capability` | `manage_woocommerce` or `manage_options` | `Rest_API::get_manage_capability()`, `Settings_UI::get_manage_capability()` — controls who can manage links via wp-admin and REST. |
| `kitgenix_affiliate_health_check_timeout` | `$timeout, $url` | `Settings::get_health_check_timeout()` (3–30s, admin-configurable) | `Health_Checks::perform_request()`. |
| `kitgenix_affiliate_max_import_items` | `$max` | `2000` | `Affiliate_Link_Post_Type::get_max_import_items()` — caps rows processed per import call. |
| `kitgenix_affiliate_list_records_limit` | `$limit` | `5000` | `Affiliate_Link_Post_Type::list_link_records()` — caps records loaded by REST/CLI list/export/import-matching (the admin Links tab is unaffected; it uses SQL-level pagination via `WP_Query`). |

### Custom actions the plugin fires

| Action | Args | Fired | Purpose |
|---|---|---|---|
| `kitgenix_affiliate_redirected` | `$post_id, $slug, $destination, $status` | `Redirector::maybe_redirect()`, immediately after click counting and just before `wp_safe_redirect()`/`exit` | Lets other code react to (or log/analyze) a completed redirect. |

## Shortcodes / Blocks / Widgets

**None.** The plugin registers no shortcodes (`add_shortcode`), no Gutenberg blocks (`register_block_type`), and no widgets. All functionality is delivered via the wp-admin UI, the REST API, WP-CLI, and the front-end redirect handler on `template_redirect`.

## Data Storage

### Custom database table

`{$wpdb->prefix}kitgenix_aff_click_stats` (created/upgraded via `dbDelta()` in `Analytics::install_table()`, tracked by option `kitgenix_affiliate_link_manager_analytics_db_version`, current schema version `1.1.1`):

```sql
CREATE TABLE {prefix}kitgenix_aff_click_stats (
    id               bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    link_id          bigint(20) unsigned NOT NULL,
    event_date       date NOT NULL,
    source_key       varchar(32)  NOT NULL DEFAULT 'direct',
    source_label     varchar(191) NOT NULL DEFAULT 'Direct',
    placement_key    varchar(32)  NOT NULL DEFAULT 'unassigned',
    placement_label  varchar(191) NOT NULL DEFAULT 'Unassigned',
    referrer_host    varchar(64)  NOT NULL DEFAULT '',
    clicks           bigint(20) unsigned NOT NULL DEFAULT 0,
    last_clicked_at  datetime NULL DEFAULT NULL,
    PRIMARY KEY  (id),
    UNIQUE KEY link_date_source_placement_referrer (link_id, event_date, source_key, placement_key, referrer_host),
    KEY link_id (link_id),
    KEY event_date (event_date),
    KEY source_key (source_key),
    KEY placement_key (placement_key),
    KEY referrer_host (referrer_host)
) {charset_collate};
```

Rows are upserted with `INSERT ... ON DUPLICATE KEY UPDATE clicks = clicks + 1` (one row per link/date/source/placement/referrer-host combination), so this table never stores per-visit records, IP addresses, cookies, or full user-agent/referrer strings — only aggregate daily counts.

### Custom post type

`kitgenix_aff_link` (constant `Affiliate_Link_Post_Type::POST_TYPE`) — `public => false`, `show_ui => false`, `show_in_menu => false`, no rewrite, `exclude_from_search => true`, `supports => ['title']` only, `map_meta_cap => true`. Entirely managed through the plugin's own UI/API, never through the native `wp-admin/edit.php` screens.

### Taxonomies

- `kitgenix_aff_group` — hierarchical, non-public, no UI (`show_ui => false`). Code enforces "one primary group per link" convention even though the taxonomy itself would allow more (`wp_set_object_terms(..., false)` replaces rather than appends).
- `kitgenix_aff_tag` — flat, non-public, no UI. Many tags per link; bulk-add appends (`wp_set_object_terms(..., true)`).

### Postmeta keys (all on `kitgenix_aff_link` posts)

| Meta key | Constant | Type stored | Purpose |
|---|---|---|---|
| `_kitgenix_affiliate_destination_url` | `META_DESTINATION` | URL string | Primary destination. |
| `_kitgenix_affiliate_clicks` | `META_CLICKS` | int | Click counter. |
| `_kitgenix_affiliate_rel` | `META_REL` | string | `nofollow` / `sponsored` / `nofollow sponsored`. |
| `_kitgenix_affiliate_enabled` | `META_ENABLED` | `1`/`0` | Enabled flag (absent = enabled). |
| `_kitgenix_affiliate_campaign_note` | `META_CAMPAIGN_NOTE` | text | Free-text note. |
| `_kitgenix_affiliate_starts_at` | `META_STARTS_AT` | Unix timestamp (string) | Schedule start. |
| `_kitgenix_affiliate_expires_at` | `META_EXPIRES_AT` | Unix timestamp (string) | Schedule expiry. |
| `_kitgenix_affiliate_fallback_url` | `META_FALLBACK_URL` | URL string | Fallback destination. |
| `_kitgenix_affiliate_rotation_mode` | `META_ROTATION_MODE` | string | `none` / `sequential` / `random`. |
| `_kitgenix_affiliate_rotation_destinations` | `META_ROTATION_DESTINATIONS` | array of URL strings | Extra rotation pool URLs. |
| `_kitgenix_affiliate_health_status` | `Health_Checks::META_STATUS` | string | `unknown`/`healthy`/`warning`/`critical`. |
| `_kitgenix_affiliate_health_message` | `Health_Checks::META_MESSAGE` | text | Human-readable last result. |
| `_kitgenix_affiliate_health_checked_at` | `Health_Checks::META_CHECKED_AT` | Unix timestamp | Last check time. |
| `_kitgenix_affiliate_health_http_code` | `Health_Checks::META_HTTP_CODE` | int | Last observed HTTP status. |
| `_kitgenix_affiliate_health_final_url` | `Health_Checks::META_FINAL_URL` | URL string | URL reached after following redirects. |
| `_kitgenix_affiliate_health_redirect_hops` | `Health_Checks::META_REDIRECT_HOPS` | int | Redirect hop count observed. |
| `_kitgenix_affiliate_health_code` | `Health_Checks::META_CODE` | string | Structured event-log code for the last result. |

### Options

| Option name | Autoload | Purpose |
|---|---|---|
| `kitgenix_affiliate_link_manager_settings` | default | The single settings array (`prefix`, `redirect_status`, `links_per_page`, `delete_data_on_uninstall`, `health_check_frequency`, `health_check_timeout`). |
| `kitgenix_affiliate_link_manager_total_clicks` | `false` | Cached sum of all published links' click counts. |
| `kitgenix_affiliate_link_manager_analytics_db_version` | default | Tracks the click-stats table schema version for `dbDelta()` upgrades. |
| `kitgenix_affiliate_link_manager_health_summary` | `false` | Site-wide health rollup: `checked_at`, `total`, `unknown`/`healthy`/`warning`/`critical` counts, up to 5 `issues`. |
| `kitgenix_affiliate_link_manager_health_cursor` | `false` | Offset cursor for the batched cron health check (`Health_Checks::BATCH_SIZE = 10` per run). |
| `kitgenix_affiliate_link_manager_event_log` | `false` | The capped (100-entry) activity log array. |
| `kitgenix_affiliate_link_manager_log_retention_days` | default | 7 / 14 / 30 (default 30). |

### Transients

| Transient | TTL | Purpose |
|---|---|---|
| `kitgenix_affiliate_link_manager_do_rewrite_flush` | 2 minutes | Fallback flag to flush rewrites on the next `init` after activation. |
| `kitgenix_affiliate_link_manager_do_activation_redirect` | 30 seconds | Triggers the post-activation redirect to the settings page. |
| `kitgenix_affiliate_link_manager_overview_snapshot` | 5 minutes (`Analytics::OVERVIEW_CACHE_TTL`) | Caches the site-wide analytics snapshot; explicitly cleared on every click record / analytics reset. |
| `kitgenix_hub_wporg_active_installs_v1`, `kitgenix_hub_wporg_ratings_v1`, `kitgenix_hub_wporg_media_v1` | 1 day (`DAY_IN_SECONDS`) | Cache WordPress.org `plugins_api()` lookups used by the shared Hub page. |

### Object cache

`Redirector::maybe_redirect()` caches successful slug→post-ID lookups in the `kitgenix_affiliate_link_manager` cache group under key `slug_map:{slug}`, TTL `10 * MINUTE_IN_SECONDS` by default (filterable). The cache entry is explicitly purged (`purge_redirect_cache_for_slug()`) whenever a link's slug changes, is saved, or `set_enabled()` is called — but note the *resolved destination itself is never cached*, only the slug→ID mapping, so schedule/rotation state is always evaluated live.

## AJAX / REST Endpoints

**No `admin-ajax.php` handlers exist** (`wp_ajax_*` is never registered) — all admin state-changing actions go through `admin-post.php` (see Hooks Reference table above), and all programmatic access goes through the REST API below.

### REST API — namespace `kitgenix-affiliate-link-manager/v1`

All routes share the same permission callback, `Rest_API::permission_callback()`, which requires `current_user_can()` the manage capability (`manage_woocommerce` if WooCommerce is active else `manage_options`, filterable via `kitgenix_affiliate_manage_capability`) and otherwise returns a `WP_Error` with `rest_authorization_required_code()` (typically 401/403). There is no anonymous/public REST access.

| Route | Method | Callback | Notes |
|---|---|---|---|
| `/links` | GET | `list_links` | Query params: `search`, `group`, `tag`, `enabled`, `orderby`, `order`, `per_page` (1–200, default 50), `page`. Response: `{items, pagination:{page, per_page, total_items, total_pages}}` plus `X-WP-Total`/`X-WP-TotalPages` headers. |
| `/links` | POST | `create_link` | Body params map 1:1 to link fields (`title`, `slug`, `destination_url`, `starts_at`, `expires_at`, `fallback_url`, `rotation_mode`, `rotation_destinations`/`rotation_urls`, `group_name`/`group`, `tag_names`/`tags`, `campaign_note`, `rel`, `enabled`, `clicks`). Returns 201 + the full link record, or a 400 `WP_Error` from validation. |
| `/links/export` | GET | `export_links` | Same filters as `/links`. Returns `{exported, headers, rows}` (flattened export rows, same shape as CSV/WP-CLI export). |
| `/links/import` | POST | `import_links` | Body: `{links: [...], match_existing: 'slug'|'id'|'none'}`. Returns `Affiliate_Link_Post_Type::import_links()`'s result: `{created, updated, failed, skipped, results}`. |
| `/links/{id}` | GET | `get_link` | 404 `WP_Error` if not found. |
| `/links/{id}` | PUT, PATCH | `update_link` | Partial update — only supplied params are changed (unset fields default to the link's current stored values). |
| `/links/{id}` | DELETE | `delete_link` | Trashes the post. Returns `{deleted: true, id}`. |
| `/links/{id}/disable` | POST | `disable_link` | Sets enabled = false. |
| `/links/{id}/enable` | POST | `enable_link` | Sets enabled = true. |
| `/links/{id}/duplicate` | POST | `duplicate_link` | Returns 201 + the new link record. |
| `/links/{id}/reset-clicks` | POST | `reset_link_clicks` | Zeroes clicks and per-link analytics. |
| `/links/{id}/check-health` | POST | `check_link_health` | Runs `Health_Checks::run_single_check()` synchronously, then returns the updated record. |

A link record (returned by most endpoints) includes: `id`, `title`, `slug`, `short_url`, `destination_url`, `starts_at`, `expires_at`, `fallback_url`, `rotation_mode`, `rotation_destinations`, `redirect_state`, `health_status`, `health_message`, `health_checked_at`, `health_http_code`, `health_final_url`, `health_redirect_hops`, `clicks_last_7_days`, `clicks_last_30_days`, `timeline_14_days`, `top_sources_30_days`, `top_placements_30_days`, `top_referrers_30_days`, `clicks`, `rel`, `enabled`, `group_name`, `group_slug`, `tag_names`, `tag_slugs`, `campaign_note`, `created`, `modified`.

## Cron / Scheduled Events

| Hook | Schedule | Registered by | Purpose |
|---|---|---|---|
| `kitgenix_affiliate_run_health_checks` | User-configurable: `kitgenix_affiliate_thirty_minutes` (custom, 30 min) / `hourly` / `twicedaily` / `daily` (default `hourly`) | `Health_Checks::maybe_schedule_health_checks()` on `init` | Batched health check — processes `BATCH_SIZE = 10` links per run using a persisted offset cursor (`kitgenix_affiliate_link_manager_health_cursor` option), wrapping back to 0 once it reaches the end, then rebuilds the site-wide summary. Re-scheduled automatically (old hook cleared, new one set) if the frequency setting changes. |
| `kitgenix_affiliate_run_health_checks_once` | Single event, ~1 minute after first activation if no summary exists yet | `Health_Checks::maybe_schedule_health_checks()` | One-time "prime the pump" check so a fresh install has health data quickly instead of waiting for the first full hourly/daily cycle. Shares the same handler as the recurring hook. |
| `kitgenix_affiliate_link_manager_prune_event_log` | `daily` | `Event_Log::maybe_schedule_prune()` on `init` | Removes log entries older than the configured retention window (7/14/30 days) even on a quiet site with no new events to trigger the inline prune-on-write check. |

Both `Health_Checks::clear_scheduled_health_checks()` and `Event_Log::clear_scheduled_prune()` are called on `register_deactivation_hook`, so no cron jobs are left dangling after deactivation.

## Frontend Behavior

The plugin has **no visible front-end templates, widgets, or blocks** — its only front-end behaviour is the redirect itself:

- On every request, `Redirector::maybe_redirect()` runs on `template_redirect` and checks the `kitgenix_affiliate_slug` query var (populated by the rewrite rule `^{prefix}/([^/]+)/?$`). If absent, the function returns immediately (no-op for all normal page requests).
- If present: the slug is sanitized (`sanitize_title()`), looked up (object-cache-first, then `get_page_by_path()`), and the redirect destination is resolved via `Affiliate_Link_Post_Type::resolve_redirect_destination()` (which accounts for enabled state, schedule window, and rotation).
- A `disabled` state or an unresolvable/invalid destination renders a genuine 404 (`status_header(404)`, `nocache_headers()`, includes the theme's 404 template if one exists, else echoes `"404 Not Found"`).
- Otherwise, the click is counted (`increment_clicks()` — this always happens, even for scheduled/expired/fallback redirects, since it's tied to the *link*, not the specific destination reached), the `kitgenix_affiliate_redirected` action fires, the destination's host is temporarily whitelisted into `allowed_redirect_hosts` for that one request, and `wp_safe_redirect($destination, $status)` is issued with `exit`.
- No enqueued CSS/JS ships to the front end at all — `Settings_UI::enqueue_assets()` only fires on the plugin's own admin page hook.

## Assets

### CSS

| File | Enqueued on | Purpose |
|---|---|---|
| `assets/css/kitgenix-admin-ui.css` (~2,800 lines) | Every wp-admin page load (registered priority 5), enqueued on the plugin's own screen and the Hub screen | Shared Kitgenix design system: CSS custom properties (brand colors, spacing/radius scale, Manrope/Inter via Google Fonts `@import`), topbar, cards, settings rows, tables, toggles, modals, toasts, collapsible panels, pagination. Deliberately kept byte-identical across all Kitgenix plugins. |
| `assets/css/admin.css` (~200 lines) | Plugin's own settings page only | Plugin-specific layout: links grid, campaign-view pills, and other Affiliate-Link-Manager-specific tweaks layered on top of the shared design system. |
| `assets/css/kitgenix-hub.css` (~420 lines) | Only when `$_GET['page'] === 'kitgenix'` (the shared Hub page) | Plugin-discovery grid styling for the Hub page. |

### JavaScript

| File | Enqueued on | Purpose |
|---|---|---|
| `assets/js/kitgenix-admin-tabs.js` (~500 lines) | Plugin's own settings page | Shared topbar/tab controller: tab switching (URL hash + `?tab=` sync), in-page search filtering the active tab's content ("/" or Cmd/Ctrl+K shortcut), mobile hamburger + dropdown menus, light/dark theme toggle (persisted to `localStorage`), moving WordPress admin notices above the custom topbar, and measuring the sticky topbar's real height for CSS (`--kitgenix-sticky-offset`). No dependencies declared. |
| `assets/js/kitgenix-admin-components.js` (~590 lines) | Plugin's own settings page, dependency of `admin.js` | Shared reusable component controller: toast notifications, modal open/close with a Tab/Shift+Tab focus trap and return-focus behaviour, collapsible/accordion cards, a generic copy-to-clipboard handler (`data-kitgenix-copy`), live client-side table search (`data-kitgenix-table-search`), and opt-in client-side table pagination (`data-kitgenix-table-paginate="N"`). Exposes `window.KitgenixAdminUI`. |
| `assets/js/admin.js` (~460 lines) | Plugin's own settings page, depends on `kitgenix-admin-components` | Plugin-specific behaviour: auto-slug-from-title (until the user types in the slug field manually), inline client-side validation of slug/destination-URL fields on both the main form and the modal form, filling the Edit modal from a row's `data-*` attributes on click, copy-to-clipboard for short URLs (`data-kitgenix-copy-text`), bulk-action value-input show/hide, "select all" checkbox wiring, and notice dismiss-button handling. |

All three plugin-owned scripts/styles are cache-busted with `filemtime()` of the actual file (falling back to the plugin version constant if the file can't be stat'd).

## Internationalization

- **Text domain:** `kitgenix-affiliate-link-manager`, **Domain Path:** `/languages` (declared in the plugin header).
- No explicit `load_plugin_textdomain()` call was found in the codebase — as of WordPress 4.6+, translations for plugins hosted on WordPress.org are loaded automatically from translate.wordpress.org for the declared text domain, so this is consistent with the plugin's `Plugin URI` pointing at wordpress.org.
- `languages/kitgenix-affiliate-link-manager.pot` is present (generated by `wp i18n make-pot` / WP-CLI 2.12.0, `POT-Creation-Date: 2026-08-30`, `Project-Id-Version: ... 2.0.0`). **No `.po` or `.mo` files ship in the plugin** — translations are expected to come from WordPress.org's translation platform once the plugin is approved/updated there. The `.pot` file's declared project version (2.0.0) is behind the code's actual version (2.1.0), meaning strings added/changed for 2.1.0 (e.g. the log-retention feature) are not yet reflected in the template — see Known Limitations.
- All user-facing strings are wrapped in `__()`/`_e()`/`esc_html__()`/`_n()`/`_x()` etc. with the correct text domain throughout; translator comments (`/* translators: ... */`) are present ahead of every `sprintf()` with placeholders.

## Security

- **Capability checks.** The base capability is `manage_options`, widened to `manage_woocommerce` when WooCommerce is active (so WooCommerce Shop Managers, who don't have `manage_options`, can still manage links) — computed by `get_manage_capability()` in both `Rest_API` and `Settings_UI`, and filterable via `kitgenix_affiliate_manage_capability`. Every `admin_post_*` handler starts with `self::user_can_manage_links()` (`wp_die()` with "Forbidden" if not authorized), every REST route shares `Rest_API::permission_callback()`, and the top-level Hub page (`kitgenix_render_admin_page()`) checks `current_user_can('manage_options') || (WooCommerce && manage_woocommerce)`.
- **Nonces.** Every state-changing `admin_post_*` handler verifies a nonce before doing anything: `kitgenix_affiliate_link_save`, `kitgenix_affiliate_link_delete`, `kitgenix_affiliate_link_duplicate`, `kitgenix_affiliate_link_reset_clicks`, `kitgenix_affiliate_link_bulk`, `kitgenix_affiliate_link_check_health`, `kitgenix_affiliate_run_health_checks_now`, `kitgenix_affiliate_link_import`, `kitgenix_affiliate_link_manager_clear_event_log`, `kitgenix_affiliate_link_manager_save_log_retention`. A missing or failed nonce is both `wp_die()`'d *and* recorded to the event log via `log_nonce_invalid()` (categorized as a likely false-positive "cached-or-expired-page" event rather than an attack, since expired admin-page nonces are far more common than actual CSRF attempts). The Settings tab additionally layers a custom nonce (`kitgenix_affiliate_link_manager_settings_nonce`) on top of the native Settings API nonce, checked inside the `sanitize_callback` itself (`Admin_Options::sanitize()`) — carefully scoped to only reject requests that *do* include that field and fail verification, so that non-form `update_option()` calls (WP-CLI `option update`, another integration) aren't silently discarded.
- **Sanitization.** Consistent use of `sanitize_text_field()`, `sanitize_textarea_field()`, `sanitize_title()`/`sanitize_title_with_dashes()`, `sanitize_key()`, `esc_url_raw()`, `absint()`/`(int)` casts, and `wp_unslash()` before sanitizing `$_POST`/`$_GET`/`$_FILES` input. Destination/fallback/rotation URLs are additionally validated with `wp_http_validate_url()` before being accepted. Non-BMP characters (emoji) are stripped from titles pre-insert to avoid non-utf8mb4 database insert failures, with a friendly error message (`kitgenix_affiliate_db_charset`) if the database still rejects the write.
- **Escaping.** Consistent `esc_html()`/`esc_html__()`/`esc_attr()`/`esc_attr__()`/`esc_url()`/`esc_js()`/`esc_textarea()` on all dynamic output; `wp_kses()`/`wp_kses_post()` used for the few places composed HTML strings are echoed (Hub card badges/actions, pagination links, health-summary issue links). Raw `echo` of trusted static SVG icon markup is explicitly `phpcs:ignore`d with a comment explaining it contains no user input.
- **CSV export hardening.** `neutralize_csv_formula()` (duplicated identically in both `CLI_Command` and `Settings_UI`) prefixes any field beginning with `=`, `+`, `-`, `@`, a tab, or a carriage return with a literal apostrophe before writing it to CSV, preventing formula/DDE injection in Excel/Google Sheets when an export is later opened — notably protecting against a lower-privileged user (e.g. a Shop Manager) planting a malicious formula in a Name/Group/Tag/Note field that a higher-privileged admin later opens in a spreadsheet.
- **SSRF protections.** Health checks pass `reject_unsafe_urls => true` to `wp_remote_head()`/`wp_remote_get()` (WordPress's own private/loopback-address protection), classify that specific rejection as `health_check_blocked` (not a "dead link"), cap `limit_response_size` to 1024 bytes, and manually cap redirect-following at 5 hops (`redirection => 0` passed to the HTTP API, hops followed in a PHP loop) rather than trusting an unbounded automatic follow.
- **Open-redirect consideration.** The actual visitor-facing redirect in `Redirector::maybe_redirect()` deliberately does **not** rely on `wp_validate_redirect()`'s default same-site restriction — since sending visitors to external affiliate destinations is the entire point of the plugin, the destination's host is added to `allowed_redirect_hosts` for that single request just before calling `wp_safe_redirect()`. This is a legitimate design choice for this feature (destinations are always administrator/authorized-user-supplied, never visitor-supplied), but is worth flagging explicitly since it is the one place the plugin intentionally relaxes a WordPress core safety default.
- **File upload validation (import).** The CSV/JSON import handler checks `$_FILES` error codes, `is_uploaded_file()`, and restricts the accepted extension to `csv`/`json` before parsing.
- **Direct DB queries.** All direct `$wpdb` queries (click recording/reset, analytics aggregation, atomic click increment) use `$wpdb->prepare()` with placeholders for values; the plugin-owned table name is passed through `esc_sql()` before interpolation into the SQL string (table names can't be parameterized via `%s`), each instance carrying an explanatory `phpcs:ignore` comment.

## Uninstall / Cleanup

### On deactivation (`register_deactivation_hook`)

- Clears both health-check cron hooks and the event-log prune cron hook.
- `flush_rewrite_rules()`.
- Deletes the activation-redirect transient.
- **No data is deleted.** Links, settings, logs, and analytics all survive a deactivate/reactivate cycle.

### On uninstall (`uninstall.php`, only runs when the plugin is deleted through wp-admin/WP-CLI, guarded by `defined('WP_UNINSTALL_PLUGIN')`)

Uninstall is **conservative by design** and split into two independently-gated phases:

1. **Link/analytics data deletion — opt-in only.** Checks the `delete_data_on_uninstall` flag inside the `kitgenix_affiliate_link_manager_settings` option (checking both the per-site option and, defensively, the network-wide `get_site_option()` in case a multisite admin set it there directly). Only if that flag is truthy: every `kitgenix_aff_link` post is permanently deleted in batches of 100 (`wp_delete_post($id, true)` — force-delete, bypassing Trash) and the `{prefix}kitgenix_aff_click_stats` table is dropped. On multisite, this runs once per subsite via `switch_to_blog()`/`restore_current_blog()`.
2. **Plugin-owned settings/log/transient cleanup — always runs**, independent of the data-deletion flag, and — critically — **per-site on multisite** (looping every blog ID) rather than only the site `uninstall.php` happens to execute in, so no subsite is left with orphaned `wp_options` rows. Deletes options `kitgenix_affiliate_link_manager_settings`, `kitgenix_affiliate_link_manager_total_clicks`, `kitgenix_affiliate_link_manager_analytics_db_version`, `kitgenix_affiliate_link_manager_health_summary`, `kitgenix_affiliate_link_manager_health_cursor`, `kitgenix_affiliate_link_manager_event_log`, `kitgenix_affiliate_link_manager_log_retention_days`, and transients `kitgenix_affiliate_link_manager_do_activation_redirect`, `kitgenix_affiliate_link_manager_do_rewrite_flush`, `kitgenix_affiliate_link_manager_overview_snapshot`.
3. As a final defensive step, `delete_site_option()` is called for `kitgenix_affiliate_link_manager_settings`, `kitgenix_affiliate_link_manager_analytics_db_version`, `kitgenix_affiliate_link_manager_health_summary`, `kitgenix_affiliate_link_manager_health_cursor` — the plugin never writes these via `update_site_option()` itself, but this cleans them up in case a network admin ever set them directly (e.g. via WP-CLI).

The default (flag off) leaves affiliate link posts, their metadata/terms, and the click-stats table fully intact after uninstall — so reinstalling the plugin restores full history. The `readme.txt` documents this behaviour accurately under its "Uninstall" and FAQ sections.

## Known Limitations / TODOs

No literal `TODO`/`FIXME` code comments were found anywhere in the codebase. The following are gaps or notable design choices observed while reading the code, not flagged by the developers themselves:

- **`languages/kitgenix-affiliate-link-manager.pot`'s `Project-Id-Version` is `2.0.0`**, behind the plugin's actual `2.1.0`, so any strings introduced for the 2.1.0 retention feature are not yet in the translation template.
- **Sequential rotation is stateless/deterministic**, computed as `click_count % pool_size` rather than a persisted rotation pointer. This means concurrent/simultaneous requests can land on the same destination more often than a true round-robin would, and adding/removing a rotation destination immediately reshuffles which clicks map to which pool index (no attempt at stability across pool-size changes).
- **No REST/CLI schema/argument validation via `register_rest_route`'s `args`.** All REST routes register with no `args` schema (no type/sanitize/validate callbacks declared at the route level) — validation happens entirely inside `Affiliate_Link_Post_Type::upsert_link()`, which is thorough, but means malformed REST requests don't get WordPress's standard REST argument-validation error responses; they instead surface as whatever `WP_Error` `upsert_link()` produces (still mapped to HTTP 400, so behavior is reasonable, just not using the schema-based REST convention).
- **CSV import tolerates ragged rows** (pads/truncates to the header's column count) rather than rejecting a malformed file outright — a deliberate trade-off documented in code comments (avoids a fatal on a hand-edited CSV with a stray comma) but means a badly malformed row could silently import with misaligned/blank fields instead of failing loudly.
- **The two `neutralize_csv_formula()`/`build_csv_row()` implementations are duplicated** verbatim between `Core\CLI_Command` and `Admin\Settings_UI` rather than factored into a shared helper (e.g. on `Affiliate_Link_Post_Type`) — a maintenance point where the two copies could drift.
- **No REST rate limiting or brute-force protection** beyond standard WordPress authentication/capability checks — acceptable for an authenticated management API but worth noting for very large, automated import/export workflows.
- **Object cache for slug lookups has no cross-request invalidation guarantee on non-persistent-cache sites** — `wp_cache_*` calls silently no-op to a per-request array cache when no persistent object cache plugin is installed, so the "TTL"/staleness concern only actually matters on sites running Redis/Memcached, but this isn't called out anywhere in the code or docs.
- **Health-check timeout filter vs. setting overlap.** `kitgenix_affiliate_health_check_timeout` is still applied via `apply_filters()` even though a UI setting (`health_check_timeout`) was added in 2.0.0 specifically to replace the old "developer-only filter default" (per the changelog) — the filter now wraps the *setting's* value rather than a hardcoded default, so a site using the filter to force a fixed timeout will silently override the admin-configured setting; this is functionally fine but could confuse an admin who changes the setting and sees no effect if a filter is also active.
