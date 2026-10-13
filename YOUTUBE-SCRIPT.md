# YouTube Script: Kitgenix Affiliate Link Manager

**Suggested video title:** Stop Losing Affiliate Commissions to Ugly, Broken Links | Kitgenix Affiliate Link Manager for WordPress
**Alternative title:** Branded Affiliate Links, Click Analytics & Broken-Link Alerts in One Free WordPress Plugin
**Estimated runtime:** about 12 to 13 minutes (around 2,300 spoken words at a relaxed pace). To get under 12 minutes, shorten sections 10 (For Developers) and 12 (Tips) first.
**Plugin version covered:** 2.1.0 (requires WordPress 6.0+ and PHP 8.1+)
**Presenter voice:** Kitgenix, first person ("we" / "you")

---

## 1. Hook

[ON SCREEN: A blog post in a browser. Hover over a "Check price" button and the status bar shows a huge affiliate URL full of tracking parameters.]

Look at that link. That's what your readers see when they hover over your "Check price" button. It's long, it's messy, and it doesn't exactly say "trust me".

[ON SCREEN: Click the link. It lands on a merchant's "Page not found" screen.]

And here's the worse part. Six months after you published that review, the merchant restructured their site, and the link died. Nobody told you. Your readers have been clicking through to a 404, and you've been losing commission without knowing it.

[ON SCREEN: Quick cut of a blog with ten affiliate buttons highlighted, each with a question mark over it.]

And even when the links work, which ones are actually getting clicked? The button at the top of the post? The comparison table? The sidebar? Most of us are guessing.

## 2. Intro

[ON SCREEN: Kitgenix logo animation, then the plugin's admin screen in WordPress.]

Hi, we're Kitgenix, and this is Kitgenix Affiliate Link Manager, a free, self-hosted WordPress plugin for affiliate links.

It's for bloggers, review and comparison sites, niche affiliate publishers, and anyone else who puts partner links into their WordPress content.

[ON SCREEN: Text overlay: "Clean links. Live destinations. Real click data. All inside WordPress."]

Here's the promise: clean, branded links on your own domain, with destinations you can change at any time, tracked and monitored from one dashboard in WordPress.

## 3. Why We Built This

[ON SCREEN: A split screen. On the left, a stack of separate tools labelled "cloaking", "scheduling", "link checker", "analytics". On the right, one Kitgenix dashboard.]

Plenty of link-cloaking plugins do one thing: shorten a URL and redirect it. But real affiliate work is messier. Offers start and end, merchants move pages, and you want to know which retailer and which placement actually perform.

So we built one plugin that does all of it: branded redirects, start and expiry scheduling with a fallback, destination rotation, automatic health monitoring, and click analytics.

[ON SCREEN: Text overlay: "No external tracking account. Data stays in your WordPress database."]

It all runs inside WordPress. You don't need an external tracking account, and your click data stays in your own database.

## 4. Installing & Activating

[ON SCREEN: WordPress admin, Plugins > Add New. Search for "Kitgenix Affiliate Link Manager", then click Install and Activate.]

Installing works like any other plugin. Go to Plugins, then Add New, search for Kitgenix Affiliate Link Manager, install it and activate it. You'll need WordPress 6.0 or newer and PHP 8.1 or newer.

[ON SCREEN: After activation the browser redirects to the plugin screen. Highlight the "Kitgenix" menu and the "Affiliate Links" submenu.]

After activation you'll land straight on the plugin's screen. You can always find it again under the Kitgenix menu, then Affiliate Links.

[ON SCREEN: Click the Settings tab. Zoom in on the "Link prefix" field showing "go".]

Before you create any links, open the Settings tab. The most important setting here is the **link prefix**. It's the word that sits in front of every short link. The default is "go", so your links will look like yoursite.com/go/your-link-name.

You can change it to something else, like "recommends" or "visit". If you pick something that clashes with a reserved WordPress path, or a page that already exists at that address, the plugin will warn you when you save.

[ON SCREEN: Show the "Redirect type" dropdown: 307 (Temporary, recommended), 302, 301.]

Next is the **redirect type**. We recommend leaving it on 307, which is a temporary redirect. That tells browsers and search engines the destination behind the link can change, and with affiliate links it usually does.

[ON SCREEN: Briefly show Links per page, Health check frequency, Health check timeout, and the uninstall data toggle.]

You'll also find links per page, health-check frequency and timeout, and an uninstall data option that's off by default, so deleting the plugin by accident won't wipe your links or analytics.

[ON SCREEN: Settings > Permalinks > Save Changes.]

One small tip. If you change the prefix and your new links don't work straight away, go to Settings, then Permalinks, and click Save. That refreshes WordPress's rewrite rules.

## 5. Creating Your First Affiliate Link

[ON SCREEN: Links tab, Add/Edit Link card.]

Now let's build a link. On the Links tab you'll see the Add Link form. We'll go through every field.

[ON SCREEN: Type "Acme Standing Desk" into Name. The Slug field fills in automatically with "acme-standing-desk".]

First, the **Name**. This is just for you. As you type, the plugin suggests a **Slug**, which is the last part of the public URL. You can keep the suggestion or type your own, and the plugin checks that no other link is already using it.

[ON SCREEN: Paste a long affiliate URL into Destination URL.]

Then the **Destination URL**. Paste your full affiliate link here, tracking parameters and all. Your readers won't see it. They'll only see yoursite.com/go/acme-standing-desk. And when the merchant changes their URL later, you update it once, right here, and every post that uses the short link is fixed straight away.

[ON SCREEN: Fill in Start date/time and Expiry date/time.]

Next, **scheduling**. You can set a start date and time, an expiry date and time, both, or neither. The plugin checks them every time someone clicks, so a link really does go live and really does expire on schedule.

[ON SCREEN: Fill in Fallback URL with a "best standing desks" roundup page.]

That's where the **Fallback URL** comes in. If someone clicks the link before it starts or after it expires, they're sent to the fallback instead, maybe a category page or a roundup post. If you don't set a fallback, they get a 404. So it's worth setting one.

[ON SCREEN: Open the Rotation mode dropdown: None, Sequential, Random. Add a second URL to "Additional rotation destinations".]

Next is **Rotation mode**. Leave it on None, and the link always goes to your main destination. Switch it to Sequential or Random, and add extra URLs, one per line, and the link spreads clicks across your main destination plus those extras. Sequential takes turns in order. Random picks one each time. This is how you split-test two offers behind one link.

[ON SCREEN: Type "Office Furniture" into Link group, then "review, desks, q4" into Tags.]

Then **organisation**. Every link can belong to one **group**, like "Office Furniture" or "Black Friday", and it can have as many **tags** as you like. Groups appear as quick filter buttons at the top of your link list, so it's easy to jump between campaigns.

[ON SCREEN: Type a note into Campaign note: "Commission 8% – renegotiate in January".]

The **Campaign note** is a private note for you. Use it for commission rates, contact details, or reminders like "this deal ends at midnight".

[ON SCREEN: Show the Link rel dropdown with "nofollow sponsored" selected and the Enabled toggle switched on.]

You can also pick a **rel** value: nofollow, sponsored, or both, which is the default. It's stored with the link and shown in your link list and exports, so you always know how a link should be marked. And there's the **Enabled** switch.

[ON SCREEN: Text overlay: "Disabled = always 404. Fallback is only for scheduled or expired links."]

One thing to be clear about. If you disable a link, it always returns a 404, even if it has a fallback URL. The fallback is only used for links that haven't started yet or have already expired. So if you want people to land somewhere useful, set an expiry date. Disabling is a hard off switch.

[ON SCREEN: Click "Add Link". The new row appears in the All Links table. Click the Copy button next to the short URL.]

Save it, and your link appears in the All Links table. Click Copy next to the short URL, paste it into your post, and you're done.

## 6. Click Analytics

[ON SCREEN: Scroll to the Analytics Snapshot panel. Highlight the 7-day and 30-day totals, then the 14-day timeline.]

Every time someone clicks one of your links, the plugin counts it. At the top of the Links tab you'll find the **Analytics Snapshot**, which covers your whole site: clicks in the last seven and last thirty days, plus a fourteen-day timeline.

[ON SCREEN: Highlight Top sources, Top referrers and Top placements.]

Below that you'll see your **top sources**, **top referrers**, and **top placements**. Open any link to see the same breakdown for that link on its own, in the Link Analytics panel.

[ON SCREEN: Show two versions of the same short link: "/go/acme-standing-desk/?placement=top-button" and "/go/acme-standing-desk/?placement=comparison-table".]

Placements are where this gets really useful. If you add a parameter like `placement=top-button` or `utm_content=comparison-table` to the end of your short link, the plugin reports those placements separately. Now you know whether people click the button at the top of the article or the table halfway down, and you can put more effort into the spot that actually gets clicks.

[ON SCREEN: Text overlay: "No IP addresses. No cookies. Daily totals only."]

And it's privacy-friendly. The plugin only stores daily totals. It doesn't store IP addresses, cookies, full user-agent strings, or full referrer URLs. For referrers it keeps only the website's domain name.

## 7. Health Checks

[ON SCREEN: The All Links table with a red "health alert" pill on one row, and the health summary notice at the top of the page.]

Now for the feature that saves you money: **automatic health checks**.

In the background, the plugin regularly checks the destinations behind your links. That includes your main destination, your fallback URL, and every rotation URL. It follows the merchant's redirects, up to five steps, and records where it ended up.

[ON SCREEN: A legend appears: Healthy (green), Warning (amber), Critical (red), Unknown (grey).]

Each link gets one of four statuses:

- **Healthy** means the destination is responding normally.
- **Warning** usually means the destination now redirects somewhere. That's not always a problem, but it's worth a look, because sometimes a product page quietly redirects to the merchant's homepage.
- **Critical** means something is properly wrong: a 404, a server error, a redirect loop, or a site that isn't responding at all. Fix these first.
- **Unknown** means the link hasn't been checked yet.

[ON SCREEN: Show the health summary notice with critical/warning counts and deep links, then click "Run health check now".]

When anything needs attention, a summary banner appears at the top of the plugin screen. It shows how many links are critical or have warnings, with links that take you straight to them. You don't have to wait for the next scheduled check, either. Click "Check now" on a single link, or "Run health check now" to check your whole library.

[ON SCREEN: Text overlay: "Timeouts and blocked requests are reported separately from dead links."]

The plugin is also careful to tell the difference between a destination that's really dead and a temporary network hiccup, or a request that was blocked by a security setting. That way you aren't chasing problems that don't exist.

## 8. Scheduling & Rotation in Practice

[ON SCREEN: Create a link named "Black Friday Hosting Deal" with slug "bf-hosting".]

Let's look at two quick real-world setups.

First, a time-limited promo. Say a hosting company is running a Black Friday deal. We create a link called "bf-hosting", point it at the special offer page, set the start date to Friday morning and the expiry to Monday at midnight. The fallback is the host's normal pricing page.

[ON SCREEN: The link row shows a "Scheduled" pill, then cut to it showing "Expired".]

You can put that link in your posts and emails ahead of time. Before Friday, clicks go to the regular pricing page. During the sale, they go to the deal. After Monday, they go back to regular pricing. No midnight editing, and no readers landing on an expired-offer page.

[ON SCREEN: Edit a link "best-vpn". Set Rotation mode to Sequential and add a second partner's URL.]

Second, a split test. You're not sure whether Partner A or Partner B converts better for your readers. Put Partner A in the destination field, add Partner B as an extra rotation destination, and set the mode to Sequential. Clicks now alternate between the two.

[ON SCREEN: Text overlay: "Tip: compare conversions in each partner's own affiliate dashboard."]

One honest tip. The plugin counts clicks per link, not per destination. So to find the winner, compare the results in each partner's own affiliate dashboard. When you've got your answer, set rotation back to None, and your published link points at the winner without you touching a single post.

## 9. Bulk Tools & Import/Export

[ON SCREEN: The All Links table with search, group/tag filters, and sorting by Clicks.]

Once you've got dozens or hundreds of links, the list tools really help. You can search by name, slug, destination, note, group or tag. You can filter by group or tag, and sort by name or by clicks to see your top performers.

[ON SCREEN: Tick several rows and open the Bulk actions dropdown: Enable, Disable, Set group…, Add tags…, Check destination health now, Reset clicks, Export CSV, Delete.]

Tick a few links, and bulk actions let you enable, disable, group, tag, health-check, reset, export or delete them in one go. And the Duplicate row action is great for spinning up a new campaign from an existing one.

[ON SCREEN: Expand the Import Links card. Choose a .csv file and show the "Match existing links by" options: slug, ID, none.]

To move links in or out, use **import and export**. You can export to CSV, or import a CSV or JSON file. On import you choose how to match existing links: by slug, by ID, or not at all, which always creates new links. That makes bulk updates in a spreadsheet easy. And if you're coming from another tool, there's a Click Count field on each link, so you can bring your existing totals with you.

## 10. For Developers

[ON SCREEN: A terminal running `wp kitgenix-affiliate link create --title="Acme Desk" --destination-url="https://..."`.]

If you're a developer, or you like to automate things, there's more under the hood.

There's a full set of WP-CLI commands under `wp kitgenix-affiliate link`: create, get, update, enable, disable, duplicate, reset-clicks, check-health, delete, import and export.

[ON SCREEN: A REST client calling GET /wp-json/kitgenix-affiliate-link-manager/v1/links.]

There's also an authenticated REST API under `kitgenix-affiliate-link-manager/v1` for listing, creating, updating, deleting, importing, exporting and health-checking links. It's never public: only users allowed to manage links can use it. And there are developer filters and a redirect action if you want to hook into the redirect itself.

## 11. Real-World Example

[ON SCREEN: A product review blog, "The Home Office Desk", showing a review of a standing desk.]

Let's put it all together. Say you run a home-office review blog, and you've just reviewed the Acme standing desk. It's sold by two retailers, and one of them is running a launch discount until the end of the month.

[ON SCREEN: Add Link form, filling in each field as it's mentioned.]

You create a link called "Acme Standing Desk" with the slug "acme-desk". The destination is Retailer One's discounted product page. Under rotation you choose Random and add Retailer Two's product page, so clicks are shared between both retailers.

You set the expiry to the last day of the month, when the launch deal ends, and use Retailer Two's normal product page as the fallback. You put it in the "Office Furniture" group, tag it "review" and "launch-deal", and add a campaign note: "Retailer One discount ends on the 31st. Check commission rates."

[ON SCREEN: Paste the short link into the post twice: once with ?placement=hero-button and once with ?placement=verdict-box.]

In the post, you use the same short link in two places, the button at the top and the verdict box at the bottom, with a different placement parameter on each.

[ON SCREEN: Fast-forward graphic: "One week later". Show the Link Analytics panel with the placement breakdown.]

A week later you open the link's analytics. The verdict box is getting more clicks than the top button, so you know where your readers make up their minds.

[ON SCREEN: The health summary notice turns up a "Warning" on the Retailer Two URL.]

Midway through the month, a health check flags Retailer Two's URL with a warning: it's now redirecting. You check it, find the new product URL, paste it in, and you're done. You don't have to edit the post.

[ON SCREEN: Calendar flips to the 1st. Clicking the link now goes to Retailer Two's regular product page.]

And when the month ends, the link expires on its own and every click goes to the fallback. No dead deal page, and you didn't have to log in.

## 12. Tips & Best Practices

[ON SCREEN: A numbered list builds on screen as each tip is spoken.]

A few tips before we wrap up.

**One:** Choose your prefix before you publish links, and stick with it. Changing it later changes every short URL.

**Two:** Any link with a start or expiry date should have a fallback URL. And remember, if you want people to land somewhere useful, use an expiry date, not the Disabled switch.

**Three:** Add placement parameters to your short links. It's the quickest way to learn what your readers actually click.

**Four:** Deal with critical health alerts first. Critical means readers are hitting a dead end right now.

**Five:** Match the health-check frequency to your library. Hourly suits most sites. With thousands of links, a less frequent schedule is kinder to your server.

**Six:** Export a CSV every so often as a backup.

## 13. Recap & Call to Action

[ON SCREEN: A montage of the Links table, the Analytics Snapshot, the health summary notice, and the link editor.]

So that's Kitgenix Affiliate Link Manager. Clean, branded short links on your own domain. Destinations you can change at any time. Scheduling with fallbacks, rotation for split-testing, automatic health checks that catch broken links before your readers do, and privacy-friendly click analytics. All in one place, inside WordPress.

[ON SCREEN: The WordPress.org plugin page with the "Download" button highlighted. Text overlay: "Free on WordPress.org".]

It's free on WordPress.org. Install it, set your prefix, and convert your top five affiliate links today. You'll probably find at least one that's already broken.

[ON SCREEN: Subscribe button animation, then a link to the Kitgenix Facebook group and kitgenix.com.]

If this video helped, give it a like and subscribe for more WordPress tools from Kitgenix. If you have questions or feature ideas, leave a comment or join our Facebook group. And if the plugin saves you a few commissions, a review on WordPress.org really helps us out.

Thanks for watching. We'll see you in the next one.

[ON SCREEN: Kitgenix end card with links to related videos.]
