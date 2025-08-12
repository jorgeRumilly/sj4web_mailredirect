# Changelog – SJ4WEB - Mail Redirect

## [1.0.0] - 2025-08-12
### Added
- Initial release.
- Hook `actionEmailSendBefore` intercepts all outgoing emails.
- Module configuration: enable/disable + target email address.
- Optional subject prefix `[REDIRECTED]`.
- Optional redirect banner in email content.
- Translation files in XLF format (`en-US` and `fr-FR`).
- README and CHANGELOG included.
- Configurable whitelist (emails, domains, templates) via BO.
- Exclusion logic centralized in helper methods.
- Added GPL v3 license header to all PHP files.
