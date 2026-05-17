# WPSimpleCompliance

WPSimpleCompliance is a lightweight WordPress plugin for EU-oriented cookie consent, privacy/cookie policy pages, Google Consent Mode v2 defaults, cookie scanning and an accessibility statement helper.

The plugin is intentionally self-contained: no SaaS dependency, no external update library, no telemetry.

## Main Features

- First-layer cookie banner with accept, reject and granular preferences.
- Consent categories: necessary, preferences, statistics and marketing.
- Google Consent Mode v2 default-denied initialization.
- Runtime activation for blocked scripts marked with `type="text/plain"` and `data-lde-consent`.
- Generated privacy policy, cookie policy and accessibility statement pages.
- Technical scan of HTTP `Set-Cookie` headers and common third-party scripts.
- GitHub Releases updater for WordPress admin updates.

## Compliance Notes

This plugin provides technical controls and legal-document templates, but it is not a substitute for a legal review. The site owner remains responsible for verifying real processing activities, legal bases, processors, international transfers, retention periods and configured third-party tags before publication.

EU/Italian cookie compliance depends on the whole site implementation: non-essential scripts must be blocked before consent, the cookie table must reflect actual vendors, and consent must be renewed when processing conditions materially change.

See `COMPLIANCE-REVIEW.md` for the current technical review and residual risks.

## WordPress Update Flow

The plugin checks public GitHub Releases from:

`https://github.com/CodyCloudSrls/WPSimpleCompliance`

Each production release must include an asset named:

`simple-privacy-cookie-policy.zip`

The ZIP must contain the plugin files inside a root folder named `simple-privacy-cookie-policy`. This preserves update compatibility with existing installations that used the previous plugin slug.

## Releasing

1. Update the plugin version in `simple-privacy-cookie-policy.php`.
2. Update `CHANGELOG.md`.
3. Commit the changes.
4. Tag the release, for example:

```bash
git tag v1.2.0
git push origin main --tags
```

The GitHub Actions workflow builds and attaches `simple-privacy-cookie-policy.zip` to the GitHub Release.

## Shortcodes

- `[simple_cookie_settings]`
- `[simple_cookie_policy]`
- `[simple_privacy_policy]`
- `[simple_accessibility_statement]`

## Minimum Requirements

- WordPress 5.8+
- PHP 7.4+
