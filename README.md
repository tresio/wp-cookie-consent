# S3 Cookie Consent

A WordPress port of the Tresio CMS cookie consent widget. The banner, storage key, JavaScript globals and data attributes match the CMS, so QA checks and GTM setups carry over.

## Installing on a site

Build the zip with `bin/zip.sh cookie-consent` from the repo root, then:

1. Test on staging first if the site has one.
2. Plugins → Add New → Upload Plugin, upload `dist/s3-cookie-consent.zip`, activate. Nothing changes until consent is ticked.
3. Settings → Cookie Consent:
   - Paste the GA, WhatConverts and Meta Pixel IDs. Pick the Google mode and whether WhatConverts is essential.
   - Add each tracking snippet under Scripts with its location. Tick Essential for anything that must load before a choice.
   - Check the policy legal name, address and phone.
   - Tick Consent and save.
4. Turn off the old copies: deactivate the moved snippets in Header Footer Code Manager, and any plugin that adds the same tags (Site Kit, GTM4WP). Otherwise they load twice, and ungated.
5. Create a page with the slug `cookie-policy` containing `[s3_cookie_policy]`, and set it to noindex in the SEO plugin.
6. Add a "Cookie Settings" link: a menu item with the URL `#cookie-settings`, or `[s3_cookie_settings]` in a footer block or widget.
7. Purge the page cache (WP Rocket, LiteSpeed, host cache, Cloudflare).
8. In a private window, run through the test checklist below.

Updating: upload the new zip the same way and choose "Replace current with uploaded". Settings are kept. Deleting the plugin from the Plugins screen removes its settings.

The banner title, banner content, category descriptions and policy text all ship with default wording, so a site works without editing them.

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
| Google tag IDs | Comma-separated GA4 / Ads IDs. Loads only when `wp_get_environment_type()` is `production`, or with `?loadTags=1`. |
| Google mode | Essential (loads with cookies), Optional (blocked until Accept), or Consent Mode (loads denied, granted on Accept). |
| Google tag gateway path | First-party gtag path, with a fallback to googletagmanager.com. |
| WhatConverts ID, v2, Essential | WhatConverts call tracking. |
| Meta Pixel IDs | Always non-essential. |
| Scripts | Named code snippets, each with a location (head, body open, footer), an Essential flag and an Active flag. Editing code needs the `unfiltered_html` capability. |
| Cookie policy fields | Values for `[s3_cookie_policy]`. A blank legal name uses the Yoast or Rank Math organisation name, then the Site Title. |

Inside a non-essential snippet, top-level `<script type="application/ld+json">` blocks and elements with `data-s3-consent="essential"` still load before any choice.

## Shortcodes and links

- `[s3_cookie_policy]`: the cookie policy text. Put it on a page with the slug `cookie-policy` and set that page to noindex.
- `[s3_cookie_settings text="Cookie Settings"]`: a link that reopens the banner.
- Any link to `#cookie-settings` (for example a custom menu item) also reopens the banner.
- `window.s3CookieConsent.openSettings()` does the same from JavaScript.

## Differences from the CMS

- Script fields belong to the plugin. Snippets in Header Footer Code Manager are not gated; move them into Scripts.
- Injected external scripts keep their source order (`async = false`).
- The banner script is inline in the footer, not part of a theme bundle, so it can't be missing.
- The banner has `role="dialog"`, an accessible name, and closes on Escape.
- `localStorage` access is wrapped in try/catch.
- The Tresio tracking bundle is not built in. Add it as a script if a site needs it.

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
10. Caching or optimisation plugin active (WP Rocket, LiteSpeed, Autoptimize): the head bootstrap still runs before gtag.
