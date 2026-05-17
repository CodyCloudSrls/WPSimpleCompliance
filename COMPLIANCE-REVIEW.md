# Compliance Review

Date: 2026-05-17

This review is technical and product-oriented. It is not a legal opinion.

## Current Status

The plugin includes the core mechanics expected from an EU-oriented cookie consent implementation:

- No non-essential category is enabled by default.
- The first banner layer includes accept, reject and customization controls.
- Preferences are granular by purpose category.
- Consent can be reopened and changed through shortcode/API controls.
- Google Consent Mode v2 is initialized as denied before optional consent updates.
- Generated privacy/cookie policy pages include controller details, purposes, legal bases, retention, transfers, rights and complaint authority fields.
- Cookie choices are persisted for at least 180 days by default and after sanitization.

## Production Requirements Outside The Plugin

Compliance still depends on site configuration:

- Non-essential scripts must be blocked before consent, for example by using `type="text/plain"` and `data-lde-consent="statistics"` or `data-lde-consent="marketing"`.
- The technical scan must be reviewed manually, because HTTP scanning cannot prove all browser-side storage, SDK behavior or tag-manager rules.
- Generated policy text must be completed with the real controller, processors, vendors, retention periods, transfer safeguards and legal bases.
- A new consent version should be set when processing conditions materially change, so previous consent is ignored and requested again.
- Accessibility status should be verified on the final theme/content combination, including keyboard navigation, focus order, contrast and uploaded documents.

## Changes Applied In 1.2.0

- Added GitHub Releases update checks for `CodyCloudSrls/WPSimpleCompliance`.
- Required a release ZIP asset with a stable WordPress plugin root folder.
- Rebranded the public plugin name to WPSimpleCompliance.
- Ignored stale consent cookies when `cookie_version` changes.
- Added local receipt metadata (`id`, `method`, timestamp and categories) to the consent cookie.
- Raised minimum consent persistence to 180 days.

## Residual Risks

- The plugin cannot guarantee that a theme, tag manager or third-party plugin does not emit trackers before consent.
- Consent evidence is browser-local; if a client needs auditable server-side consent logs, that should be designed separately with data minimization and retention rules.
- The generated legal documents are templates and require client-specific validation.

## Reference Points

- Garante Privacy, Linee guida cookie e altri strumenti di tracciamento, 10 June 2021: https://www.garanteprivacy.it/home/docweb/-/docweb-display/docweb/9677876
- Garante Privacy cookie topic page: https://www.garanteprivacy.it/temi/cookie
- EDPB Guidelines 05/2020 on consent under Regulation 2016/679: https://www.edpb.europa.eu/our-work-tools/our-documents/guidelines/guidelines-052020-consent-under-regulation-2016679_en
- EDPB Cookie Banner Taskforce report, 17 January 2023: https://www.edpb.europa.eu/system/files/2023-01/edpb_20230118_report_cookie_banner_taskforce_en.pdf
