# Changelog

## 1.3.0 - 2026-06-12

- Fixes the consent banner staying hidden behind full-page caches (LiteSpeed, Cloudflare, Varnish): the banner is now always rendered hidden server-side and the JavaScript re-asserts visibility from the consent cookie, so visitors without a valid consent always see the banner regardless of the cached HTML.
- Falls back to a `dataLayer` push when `gtag` is not yet defined, so a consent change is never silently dropped while Google Tag loads deferred/delayed.
- Hardens the Iubenda strip output buffer against PCRE backtrack limits on large pages: it can no longer blank the page when a regex replacement fails.
- Guarantees the legal document generator always runs with a full settings array, removing possible PHP 8.1 "undefined array key" warnings.
- Restricts the Escape key to closing the cookie preferences modal only when it is actually open.
- BREAKING (CSS only): renames the front-end CSS class and `data-*` attribute prefix from `lde-` to `spcp-` for a vendor-neutral, white-label build. The plugin ships its own inline CSS so the default appearance is unchanged; only custom site CSS that targeted `.lde-*` selectors needs to be updated to `.spcp-*`.

## 1.2.6 - 2026-06-04

- Expands the generated cookie policy with the full controller contact block instead of only the controller name.
- Adds an explicit privacy policy link from the generated cookie policy to the complete GDPR Article 13 notice.
- Warns administrators when core controller identity or contact fields are missing from the legal settings.

## 1.2.5 - 2026-06-04

- Wraps Facebook video plugin iframes in the same consent-aware Facebook embed flow used for page embeds.
- Prevents direct Facebook video iframe loading before marketing consent and preserves the browser-blocked fallback when tracking protection blocks the SDK.

## 1.2.4 - 2026-06-03

- Registers the native WordPress `Update URI` source hook for GitHub-hosted plugin updates.
- Adds the `version` field expected by WordPress source-specific update responses while preserving the GitHub release package URL.

## 1.2.3 - 2026-06-03

- Clarifies the Facebook fallback when browser tracking protection blocks the SDK after marketing consent.
- Stops repeated Facebook SDK retry attempts once the browser-blocked state is detected for the page.
- Hides the cookie preferences action in the blocked Facebook fallback after marketing consent is already active.

## 1.2.2 - 2026-06-03

- Re-synchronizes Facebook embeds when reopening cookie preferences with an existing marketing consent.
- Runs an additional post-load Facebook embed sync to recover from cached pages or late consent state hydration.
- Starts the Facebook embed loading/fallback flow directly after saving consent instead of relying only on blocked-script activation.

## 1.2.1 - 2026-06-03

- Added consent-aware handling for Facebook Page Plugin embeds.
- Removed direct Facebook SDK loading from rendered content until marketing consent is granted.
- Added a visible fallback link when browser privacy controls block Facebook after consent.
- Updated scan labeling for Facebook SDK embeds as marketing/social third-party content.

## 1.2.0 - 2026-05-17

- Rebranded the public plugin name to WPSimpleCompliance.
- Added native GitHub Releases update checks for `CodyCloudSrls/WPSimpleCompliance`.
- Added release packaging workflow for WordPress-compatible ZIP assets.
- Invalidates stored consent when the configured consent version changes.
- Stores a local consent receipt id and consent method in the browser consent cookie.
- Raised the minimum consent persistence setting to 180 days.

## 1.1.6 - 2026-05-10

- Initial packaged version provided for review.
