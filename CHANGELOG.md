# Changelog

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
