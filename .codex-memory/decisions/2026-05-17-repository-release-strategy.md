# Decision: Repository Release Strategy

## Date
2026-05-17

## Context
The project was renamed publicly to WPSimpleCompliance and moved to the GitHub repository `CodyCloudSrls/WPSimpleCompliance`. Existing WordPress installations may already use the historical plugin slug and folder name `simple-privacy-cookie-policy`.

## Decision
Use WPSimpleCompliance as the product and repository name, but keep the plugin package root and main file as `simple-privacy-cookie-policy` for update compatibility. Release updates through GitHub Releases using a generated asset named `simple-privacy-cookie-policy.zip`.

## Consequences
The GitHub Actions release workflow must build the ZIP asset instead of relying on GitHub source archives. Generated local ZIP files must remain uncommitted.

