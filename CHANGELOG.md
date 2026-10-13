# Changelog

## 2.1.0 – 13 October 2026

- **New:** Added a configurable log auto-delete setting (7/14/30 days) to automatically remove old event log entries.
- **New:** Added a "Request a Feature" button to the Need Help? sidebar, linking directly to this plugin's page on the Kitgenix roadmap (https://roadmap.kitgenix.com).
- **New:** Added a "Request a Feature" banner below the plugin grid on the Kitgenix Hub page, linking to the shared Kitgenix roadmap.

## 2.0.0 – 22 August 2026

- **New:** Redesigned the entire admin experience around the shared Kitgenix design system, with a sticky topbar, light/dark theme toggle, and in-page settings search using "/" or Cmd/Ctrl+K to filter the current tab.
- **New:** Added a Kitgenix Hub shortcut to the topbar plus a mobile-friendly hamburger menu with dropdown submenus for narrower screens.
- **New:** Added Image Optimizer to the Kitgenix Hub plugin discovery list.
- **New:** Added Enable and Disable actions to the Links tab bulk-actions menu alongside Delete, Reset Clicks, and Export CSV.
- **New:** Added CSV and JSON link importing directly to the Links tab, with matching options by slug, ID, or always creating a new link.
- **New:** Added a "Check now" row action for running an immediate destination health check on an individual affiliate link.
- **New:** Added a "Check destination health now" bulk action for checking selected links without waiting for the scheduled health check.
- **New:** Added a "Run health check now" action to the All Links card header and health summary notice for checking the entire link library on demand.
- **New:** Added "Set group…" and "Add tags…" bulk actions so large existing link libraries can be reorganised without editing links individually.
- **New:** Added configurable destination health-check frequency under Settings, with every 30 minutes, hourly, twice-daily, and daily scheduling options.
- **New:** Added a configurable health-check timeout between 3 and 30 seconds, replacing reliance on the previous developer-only `kitgenix_affiliate_health_check_timeout` filter default.
- **New:** Added a Click Count field to the Add/Edit Link form and modal so existing click totals can be migrated or set manually without using REST or WP-CLI.
- **New:** Added `POST /links/{id}/duplicate` to the REST API for duplicating affiliate links programmatically.
- **New:** Added `POST /links/{id}/reset-clicks` to the REST API for resetting an individual link's click count.
- **New:** Added `POST /links/{id}/check-health` to the REST API for running an on-demand destination health check.
- **New:** Added `duplicate`, `reset-clicks`, and `check-health` WP-CLI subcommands so these actions are available from command-line and automation workflows.
- **New:** REST and WP-CLI create/update operations now accept an explicit `clicks` value, matching the admin interface and CSV/JSON import functionality.
- **New:** Added a configurable per-import row limit across the admin UI, REST API, and WP-CLI import paths, defaulting to 2,000 rows and filterable through `kitgenix_affiliate_max_import_items`.
- **New:** Imports that exceed the configured row limit now report how many records were skipped rather than attempting an unbounded request that may time out or exhaust memory.
- **New:** On WordPress 7.1 and later, the Kitgenix brand icon is registered with the WordPress Icon Registration API as `kitgenix/mark` for discovery through `wp_get_icon()` and the icons REST endpoint.
- **Improved:** Rebuilt the Links tab and Add/Edit Link forms using the shared Kitgenix card and setting-row components.
- **Improved:** Replaced traditional checkboxes throughout the Links and settings interfaces with consistent toggle switches.
- **Improved:** Added a clearer empty-state interface when no affiliate links match the current search or filters.
- **Improved:** Redesigned admin notices as dismissible, colour-coded success, error, information, and warning banners used consistently across save confirmations, health warnings, and logs.
- **Improved:** Analytics Snapshot and per-link analytics now use stat tiles and collapsible panels for a more compact and scannable interface.
- **Improved:** The Log tab now displays entries in a searchable, paginated table containing time, context, outcome, and a plain-English note instead of a plain text block.
- **Improved:** Destination health checks now record whether a failure was caused by a dead destination, security-plugin block, or temporary network issue instead of collapsing every failure into one generic result.
- **Improved:** Link save, delete, and duplicate requests rejected by expired security tokens are now recorded in the Log tab instead of only displaying a temporary notice.
- **Improved:** Link validation and database errors are now recorded in the Log tab for easier diagnostics.
- **Improved:** Added a reference table to the Log tab explaining what each recorded category means and whether administrator action is required, including distinguishing routine stale security tokens from genuine problems.
- **Improved:** The Support tab is now three focused cards – a donate card with a collapsible monthly-amount picker, a "what your support funds" summary, and a "get involved" panel for reviews and plugin links.
- **Improved:** Restyled the Kitgenix Hub using the same colours, typography, spacing, and design tokens as the Affiliate Link Manager settings screens.
- **Improved:** The Kitgenix Hub now honours the light/dark theme preference already selected within the plugin instead of relying only on the operating system's colour preference.
- **Improved:** Updated the Kitgenix Hub listing from "MultiStore Sync" to "MultiStore for WooCommerce".
- **Improved:** The Edit Link modal and other shared Kitgenix dialogs now trap Tab and Shift+Tab keyboard focus inside the active modal, matching their `aria-modal="true"` accessibility semantics.
- **Performance:** Analytics Snapshot data – including global 7-day and 30-day totals, the 14-day timeline, and top sources, placements, and referrers – is now cached in a short-lived transient instead of rerunning multiple aggregate database queries on every admin page load.
- **Performance:** Analytics Snapshot caches are invalidated immediately when a new click is recorded or a link's analytics are reset so displayed statistics remain current.
- **Performance:** REST and WP-CLI link listing, export, and import matching now cap the number of records loaded per call to reduce worst-case memory usage on very large affiliate-link libraries.
- **Performance:** The REST/WP-CLI record limit defaults to 5,000 links and can be customised using the `kitgenix_affiliate_list_records_limit` filter.
- **Performance:** The admin Links tab remains unaffected by the new API record cap because it already performs SQL-level pagination.
- **Fix:** The settings sidebar now sticks directly beneath the topbar using its actual measured height rather than a fixed offset, preventing overlap when the topbar wraps at narrower desktop widths.
- **Fix:** Number inputs, including the Links Per Page setting, are no longer cramped to a fixed 50px width.
- **Fix:** The "Add Link" and "Save Settings" buttons now have appropriate spacing from the preceding form fields.
- **Fix:** Shared admin asset cache-busting now falls back safely when a file modification time cannot be read instead of referencing an undefined constant.
- **Fix:** CSV/JSON import no longer triggers a fatal error when a manually edited CSV row contains more columns than the header row; excess columns are now safely truncated before processing.
- **Fix:** CSV imports through both the admin UI and WP-CLI no longer re-process the header row as an additional data row.
- **Fix:** Corrected CSV import summaries that could previously report an unnecessary failed record, such as "created 5, failed 1" for a valid five-row file.
- **Fix:** Collapsible-card chevron icons now rotate correctly when Import Links, Link Analytics, and other collapsible cards are opened or closed.
- **Fix:** The Log tab's "Clear log" action is now available to WooCommerce shop managers, matching other link-management actions, instead of requiring administrator-only `manage_options`.
- **Fix:** The Settings save handler now rejects only submissions that actually contain a stale or forged settings-form nonce rather than discarding unrelated programmatic option updates that do not contain that field.
- **Fix:** Uninstall cleanup now runs separately for every site in a WordPress Multisite network so settings, logs, health state, and plugin transients are not left behind on subsites.
- **Security:** CSV exports now neutralise spreadsheet formula and DDE injection by prefixing potentially executable values with an apostrophe when a Name, Group, Tag, or Note starts with `=`, `+`, `-`, `@`, a tab character, or carriage return.
- **Security:** CSV export hardening prevents link values saved by lower-privileged users, such as WooCommerce shop managers, from being interpreted as spreadsheet formulas when a higher-privileged administrator later opens an export in Excel, Google Sheets, or similar software.
- **Compatibility:** Confirmed compatibility with WordPress 7.1.
- **Compatibility:** The WordPress admin menu icon continues to use the existing `add_menu_page()` icon implementation because the new WordPress Icon Registration API is not consumed by that API.
- **Compatibility:** Verified the plugin produces no PHP warnings, notices, or deprecation messages on PHP 8.1, 8.2, 8.3, or 8.4 across activation, administration, redirects, REST API requests, WP-CLI commands, cron processing, and uninstall.
- **Dev:** `Affiliate_Link_Post_Type::upsert_link()` now handles explicit click counts consistently across the admin UI, REST API, WP-CLI, and CSV/JSON import paths.
- **Dev:** Removed duplicated click-count update logic from the import implementation by consolidating it into `Affiliate_Link_Post_Type::upsert_link()`.
- **Dev:** Extracted shared modal open/close and keyboard focus-trap behaviour into `kitgenix-admin-components.js` for reuse by the Edit Link modal and other Kitgenix UI components.
- **Dev:** Added the `kitgenix_affiliate_max_import_items` filter for controlling the maximum number of rows processed by an individual import.
- **Dev:** Added the `kitgenix_affiliate_list_records_limit` filter for controlling maximum REST/WP-CLI link-record loads.
- **Dev:** Refreshed the Kitgenix logo, favicon, and WordPress.org listing assets, including plugin icons and banners, using the current brand asset set.
- **Dev:** Removed an unused CSS rule left over from the Log tab's previous plain-text layout.
- **Documentation:** Regenerated the plugin's `.pot` translation template, which had fallen several releases behind the current interface.
- **Documentation:** Expanded the readme Security & Privacy section to document the click-analytics data stored by the plugin.
- **Documentation:** Expanded the readme Uninstall section to document exactly which settings, logs, health-check data, analytics-related state, and transients are removed from each site under the applicable uninstall configuration.

## 1.1.3 – 26 May 2026

- **New:** Added a Log tab to the admin settings page. Records recent plugin activity (settings saves and key operations) with timestamps, context labels, and plain-English notes to aid troubleshooting.
- **Fix:** Resolved a missing class file include that would have caused a fatal error when visiting the Log tab.
- **Fix:** Activity log data is now fully cleaned up when the plugin is uninstalled.
- **Compatibility:** Confirmed compatibility with WordPress 7.0.

## 1.1.2 – 26 May 2026

- **Dev:** Skipped to be in line with other Kitgenix Plugins.

## 1.1.1 – 26 May 2026

- **Dev:** Skipped to be in line with other Kitgenix Plugins.

## 1.1.0 – 7 May 2026

- **New:** Added link groups so each affiliate link can be assigned to a reusable campaign view.
- **New:** Added link tags for labelling traffic sources, content types, and partner groupings.
- **New:** Added optional campaign notes to store placement context, reminders, or partner-specific details.
- **New:** Added campaign views and group/tag filters to the Links tab for faster navigation across large link libraries.
- **New:** Added a protected REST API for listing, creating, updating, disabling, importing, and exporting affiliate links outside wp-admin.
- **New:** Added WP-CLI commands for scripted link management, including create, update, enable, disable, import, and export workflows.
- **New:** Added JSON and CSV import-export support for automation pipelines and deployment tooling.
- **New:** Added per-link start dates and expiry dates so offers can go live and retire automatically.
- **New:** Added fallback URLs for links that are not live yet or have already expired.
- **New:** Added sequential and random destination rotation using the primary destination plus optional extra URLs.
- **New:** Added automated destination health checks for primary, fallback, and rotated URLs.
- **New:** Added dashboard alerts for broken offers, merchant-side 404s, and redirecting destinations.
- **New:** Added a daily analytics timeline for each link plus a global analytics snapshot inside the Links tab.
- **New:** Added source, referrer, and placement attribution using tracked short-link visits.
- **Improvement:** Added analytics summary fields to shared link records, REST responses, WP-CLI output, and CSV exports.
- **Improvement:** Search now matches campaign groups, tags, and campaign notes in addition to the existing link fields.
- **Improvement:** Duplicated links now retain their campaign metadata.
- **Improvement:** CSV exports now include link group, tags, and campaign note columns.
- **Improvement:** Added schedule and rotation fields to the admin editor, REST API, WP-CLI commands, and CSV exports.
- **Improvement:** Added destination health fields to shared link records, REST responses, WP-CLI output, and CSV exports.
- **Fix:** Prevented a PHP fatal when rendering affiliate-link 404 responses on themes that do not provide a 404 template.

## 1.0.1 – 19 March 2026

- **New:** Links list now supports pagination.
- **New:** Links list now supports server-side search (name, slug, destination).
- **New:** Links list now supports server-side sorting by Name, Slug, and Clicks.
- **New:** Added bulk actions for deleting links, resetting click counts, and exporting selected links to CSV.
- **New:** Added per-link actions to duplicate a link and reset its click count.
- **New:** Added a "Links per page" setting (10–200) to control list pagination size.
- **New:** Added an "Enabled" toggle per link to temporarily disable redirects without deleting the link.
- **Improvement:** Added inline validation for slug and destination URL fields in the admin UI.
- **Improvement:** Support tab click totals are calculated more efficiently on large sites.
- **Improvement:** Redirect handling now supports object-cache-friendly slug lookups for better performance on high-traffic links.
- **Improvement:** Settings now warn when the redirect prefix may conflict with WordPress/core URLs or an existing Page slug.
- **UI:** Improved the Kitgenix admin header layout for better alignment and less clutter.
- **UI:** Social links in admin headers now render as compact icon buttons with accessible labels.
- **UI:** Added responsive header helpers so titles/descriptions and actions/links lay out consistently.
- **UI:** Admin tables inside Kitgenix pages now use Kitgenix styling for a more consistent branded look.
- **UI:** Improved keyboard accessibility for the Edit Link modal with focus trapping and focus restoration.
- **Fix:** Added missing translators comments for pluralized admin notices.
- **Fix:** Hardened admin table escaping, pagination rendering, CSV export output, uninstall cleanup, and click-count bookkeeping to satisfy WordPress coding standards.
- **Fix:** Admin notices now display above the Kitgenix header using the WordPress standard notice area.
- **Fix:** Added defensive notice normalization to prevent notices being relocated into the header by other scripts.
- **Fix:** Added spacing between adjacent action links/buttons such as Edit/Delete.
- **Fix:** Click counting is now atomic to avoid missed increments under concurrent traffic.
- **Fix:** Admin link actions now permit WooCommerce managers when WooCommerce is installed.
- **Fix:** Escaped shared Kitgenix Hub card media output for WordPress coding standards compliance.
- **Dev:** Added a developer filter to tune redirect slug lookup cache TTL.
- **Dev:** Added developer hooks around redirects, including destination URL/status filters and a redirect action.
- **Dev:** Capability required to manage links is now filterable for developers.
- **Cleanup:** Added an optional "delete data on uninstall" setting for clean uninstalls.
- **Maintenance:** Updated the plugin Author URI to the public Kitgenix WordPress.org profile and replaced the old custom admin-menu icon CSS with the native Dashicons icon.

## 1.0.0 – 1 March 2026

- **New:** Initial release.
- **New:** Create and manage affiliate links with Name, Slug, and Destination URL.
- **New:** Redirect visitors via clean short URLs using `/go/{slug}` with a configurable prefix.
- **New:** Settings to configure the redirect prefix and redirect status (307/302/301).
- **New:** Click tracking per link.
- **New:** Optional per-link rel guidance (nofollow / sponsored / both) for SEO compliance.
- **New:** Admin UI for listing, adding, and editing affiliate links.
- **New:** Links are stored in a private custom post type.
- **New:** Redirect handling via WordPress rewrite rules.
- **Security:** Added capability checks and nonces for admin actions.
- **Security:** Added destination URL validation prior to redirect.
- **Security:** No external services and no tracking cookies.