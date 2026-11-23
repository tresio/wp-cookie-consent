# S3 Cookie Consent

A WordPress port of the Tresio CMS cookie consent widget. The banner, storage key, JavaScript globals and data attributes match the CMS, so QA checks and GTM setups carry over.

## Installing on a site

Download `s3-cookie-consent.zip` from the [latest release](https://github.com/tresio/wp-cookie-consent/releases/latest), then:

1. Test on staging first if the site has one.
2. Plugins → Add New → Upload Plugin, upload the zip, activate. Nothing changes until consent is ticked.
   On the Plugins screen, click **Enable auto-updates** in the plugin's row (right-hand column) so new releases install themselves (see [Updates](#updates)).
3. Settings → Cookie Consent:
   - Paste the GA, WhatConverts and Meta Pixel IDs. Tick Essential for Google and WhatConverts if they should load before a choice, and Consent Mode if Google should load cookieless.
   - Move the site's other tracking snippets into Scripts (see [Moving scripts from Header Footer Code Manager](#moving-scripts-from-header-footer-code-manager)).
   - Check the policy legal name, address and phone.
   - Tick Consent and save.
4. Turn off the old copies: the moved HFCM snippets, and any plugin that adds the same tags (Site Kit, GTM4WP). Otherwise they load twice, and ungated.
5. Check the Cookie Policy page. Saving with Consent on creates and publishes `/cookie-policy/` (see below).
6. Add a "Cookie Settings" link: a menu item with the URL `#cookie-settings`, or `[s3_cookie_settings]` in a footer block or widget.
7. If the site runs NitroPack, add the exclusions in [NitroPack](#nitropack).
8. Purge the page cache (NitroPack, WP Rocket, LiteSpeed, host cache, Cloudflare).
9. In a private window, run through the test checklist below.

The banner title, banner content, category descriptions and policy text all ship with default wording, so a site works without editing them.

## Updates

The plugin updates from this repository's GitHub Releases. Only a pushed version tag publishes a release (see [Releasing](#releasing)); ordinary pushes to `main` never reach sites.

- **Auto-updates on** (recommended, set during install): WordPress installs new releases in the background on its twice-daily update run. Nobody needs to click anything.
- **Auto-updates off** (WordPress's default): the Plugins screen shows "There is a new version of S3 Cookie Consent available" with an **update now** link, and someone has to click it.
- Sites check GitHub at most every 12 hours, so a release reaches a site within about a day. **Dashboard → Updates → Check Again** checks straight away.
- A failed check (GitHub unreachable or rate-limited) waits an hour before trying again.
- Settings are kept across updates. Deleting the plugin from the Plugins screen removes its settings.
- Sites on 0.2.0 or earlier have no updater: upload 0.3.0 or later by hand once (Upload Plugin → Replace current with uploaded).

## Moving scripts from Header Footer Code Manager

Stop using HFCM for tracking tags on consent sites; HFCM prints its snippets without asking for consent. For each active HFCM snippet:

1. In Settings → Cookie Consent → Scripts, click **Add script**, paste the snippet's code, and give it the same name.
2. Set the location: HFCM **Header** → Head, **Footer** → Footer. Body open is for snippets that must sit right after `<body>` (for example a GTM `<noscript>` iframe).
3. Tick **Essential** only if it must run before a choice (site functionality, not analytics or ads). Leave it unticked to hold it until Accept All.
4. Leave **Active** unticked for now and save.
5. In HFCM, deactivate the original snippet.
6. Back in Scripts, tick **Active** and save. The tag is never loaded twice or missing.

GA, WhatConverts and Meta Pixel snippets don't need a script: paste their IDs into the built-in fields instead. HFCM snippets limited to certain pages or devices load on every page once moved; check that is acceptable. When nothing is left in HFCM, deactivate it.

## NitroPack

NitroPack can delay or combine inline scripts until the visitor interacts with the page. If it does that to this plugin's scripts, essential tags load late and the banner may not appear until the visitor scrolls or clicks. In the NitroPack dashboard, under **Excluded Resources**, add exclusions for code containing:

- `s3CookieConsentConfig` (the consent setup in `<head>`)
- `s3ConsentCustomScripts.register` (held scripts)
- `partial_cookie_consent` (the banner script)

Purge NitroPack's cache after any change to the cookie consent settings. NitroPack's page cache itself is fine: the plugin serves the same HTML to every visitor.

## Releasing

The version lives in the plugin header, the `S3CC_VERSION` constant and `CHANGELOG.md`. Write notes under `## [Unreleased]` as you go, then:

```bash
bin/bump.sh patch --tag
```

```bash
git push origin main --follow-tags
```

`bin/bump.sh` takes `patch`, `minor`, `major` or an exact version, and `--dry-run` shows what it would change. Pushing the tag runs the Release workflow: it checks the versions match the tag, lints, builds `s3-cookie-consent.zip` and publishes a GitHub Release with the zip attached. Sites pick it up within 12 hours. Tags with a hyphen (`v1.0.0-beta.1`) are published as prereleases, which sites are never offered.

To build the zip locally: `bin/build.sh`.

## How it works

- The visitor's choice is stored in `localStorage._s3CookieConsent` as `{"type": "all" | "essential", "timestamp": ms}`. It expires after 30 days.
- PHP never reads the choice. Every visitor gets the same HTML, so page caching is safe.
- Essential tags and scripts load on every page view. Non-essential ones are printed as inert JSON strings and injected only when the choice is `all`.
- Reject after an earlier Accept reloads the page, so already-loaded scripts stop.

## Settings (Settings → Cookie Consent)

| Setting | Effect |
|---|---|
| Consent | Master switch. Off: no banner, and everything loads directly. |
| Title, Content | Banner heading and body (HTML). |
| Manage Preferences | Adds the preferences panel. Still a single yes/no choice. |
| Google tag IDs, Essential | Comma-separated GA4 / Ads IDs. Essential loads them before any choice, with cookies (CMS `essentialScripts` with `'ga'`). Loads only when `wp_get_environment_type()` is `production`, or with `?loadTags=1`. |
| WhatConverts ID, v2, Essential | WhatConverts call tracking. v2 is WhatConverts' newer script domain (CMS `whatconverts.v2`). Essential is CMS `'wc'`. |
| Meta Pixel IDs | Always non-essential. |
| Google Consent Mode | Only when Google is not essential. Off: Google is blocked until Accept. On: Google loads cookieless before a choice and is upgraded on Accept (CMS `google.cookieless`). |
| Google tag gateway path | First-party gtag path, with a fallback to googletagmanager.com. |
| Scripts | Named code snippets, each with a location (head, body open, footer), an Essential flag and an Active flag. Editing code needs the `unfiltered_html` capability. |
| Cookie policy fields | Values for `[s3_cookie_policy]`. A blank legal name uses the Yoast or Rank Math organisation name, then the Site Title. |

Inside a non-essential snippet, top-level `<script type="application/ld+json">` blocks and elements with `data-s3-consent="essential"` still load before any choice.

## Cookie policy page

The first time an admin loads WP admin with Consent on, the plugin creates a published page titled "Cookie Policy" at `/cookie-policy/` containing `[s3_cookie_policy]`.

- If a page with that slug already exists, it is used as is, with its content untouched.
- The page is noindexed (core `wp_robots`, plus Yoast and Rank Math post meta) and left out of the core sitemap.
- This happens once. If someone deletes the page, it is not recreated; the settings page says so.
- Turning Consent off sets the page the plugin created to draft, because its text describes a banner that is no longer there. Turning Consent back on republishes it, but only if the plugin drafted it. Edits to the page are kept. An existing page the plugin adopted is never drafted or published automatically.
- It is not added to any menu. Link to it from the footer.

## Shortcodes and links

- `[s3_cookie_policy]`: the cookie policy text. The page above already contains it.
- `[s3_cookie_settings text="Cookie Settings"]`: a link that reopens the banner.
- Any link to `#cookie-settings` (for example a custom menu item) also reopens the banner.
- `window.s3CookieConsent.openSettings()` does the same from JavaScript.

## Differences from the CMS

- Script fields belong to the plugin. Snippets in Header Footer Code Manager are not gated; move them into Scripts.
- Injected external scripts keep their source order (`async = false`).
- The banner script is inline in the footer, not part of a theme bundle, so it can't be missing.
- The banner has `role="dialog"`, an accessible name, and closes on Escape.
- `localStorage` access is wrapped in try/catch.

## Test checklist

Use a private window. Check `localStorage` and the Network tab.

1. First visit: banner shows, no `_s3CookieConsent` key, essential tags loaded, Meta Pixel and non-essential scripts not loaded.
2. Accept All: banner hides with no reload, type is `all`, non-essential tags and scripts load once.
3. Next page: no banner, everything loads.
4. Fresh Reject: type is `essential`, no reload, no non-essential requests.
5. Cookie Settings, then Reject after an Accept: the page reloads and non-essential tags are gone.
6. Close (X): nothing stored, banner returns on the next page.
7. Timestamp older than 30 days, or invalid JSON in the key: key removed, banner returns.
8. Consent Mode: `dataLayer` starts with consent default; hits carry `gcs=G100` before Accept and `gcs=G111` after.
9. Consent off: no banner, all tags and scripts load directly.
10. Caching or optimisation plugin active (NitroPack, WP Rocket, LiteSpeed, Autoptimize): the banner shows without scrolling or clicking, and the head bootstrap still runs before gtag.
11. Styling: check the banner, the Manage Preferences panel and `/cookie-policy/` against the theme. Themes can change paragraph spacing, link colours and font weights; fix those in the site's Additional CSS.
