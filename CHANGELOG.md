# Changelog
All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [1.0.2] - 2025-12-02
### Added
- Add PHP 8.4 compatibility
- Customer address prefil for the same session
- Better brand logo support in checkout shipping methods, add Bring/Posten logo support
- Cancel Vipps Order on total amount mismatch
- Functionality to reuse current session and renew functionality for iframe
- Create session if doesn't exist for order cancel
- New session generation via `vipps/checkout/session`
- New Update totals logic (update vipps total when magento total is changed, recalculate shipping methods)

### Changed
- Fix deprecated ZendClient usage in 'Test Credentials' button, replaced by Laminas
- Add new logging locations for extra information
- Remove auto address removal from quote on session init
- Fix datetime in `event_log.phtml`
