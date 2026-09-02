# Changelog

All notable changes to this project will be documented in this file.

## [0.2.3] - 2026-08-31

### Added
- Updated README with full documentation
- Complete CHANGELOG history

## [0.2.2] - 2026-08-31

### Added
- PHPUnit test suite (9 tests, 14 assertions)
- AppConfig::getAppId(), getAppVersion()
- tests/bootstrap.php

### Fixed
- TokenAdapter: createFunction for count
- phpunit.xml warnings

## [0.2.1] - 2026-08-31

### Fixed
- ProcessQueueJob: processJobs returns int, not array
- JobMapper: createFunction instead of expr()->count()
- Job entity: public properties for Nextcloud 34
- Migration steps removed from info.xml

## [0.2.0] - 2026-08-12

### Added
- ProcessQueueJob background job (5 min interval)
- Web UI assets (css, js, img, templates)
- PageController

### Fixed
- AuditLog Entity: public properties
- AppLogger: PSR-3 LoggerInterface

## [0.1.9] - 2026-08-05

### Added
- Real Remote Wipe implementation
- Push notifications (Notifier)
- Wipe status tracking (pending → completed)
- DeviceAdapter: force sync before wipe

### Fixed
- AppLogger: PSR-3 compatibility
- Device Entity: public properties
- DeviceMapper: find() method

## [0.1.8] - 2026-08-03

### Added
- Database migrations (audit, devices, jobs)
- database.xml schema
- InstallSchema repair step

### Fixed
- TokenAdapter: executeQuery for SELECT
- RemoteWipe command error handling

## [0.1.7] - Initial release

- 6 console commands
- Basic device management

## [0.2.6] - 2026-09-01

### Added
- Web interface with 6 pages (Dashboard, Offboard Users, Bulk Operations, Audit Logs, Queue Management, Settings)
- Dashboard API endpoint (`/api/v1/dashboard`)
- Real-time user statistics, audit logs, and queue stats in web UI
- Hover effects and responsive design for tables
- Search and filter functionality for audit logs and users

### Fixed
- PageController registration in NC34 (registerService)
- Web routes for all pages
- `getGroupManager()` → `get(IGroupManager::class)` in ApiController
- `searchUsers()` method in NextcloudAdapter and UserAdapter
- `countAll()` and `countRecent()` methods in AuditLogRepository
- `executeStatement()` → `executeQuery()` for SELECT queries in AuditLogMapper
- `expr()->count()` → `createFunction('COUNT(id)')` for MySQL compatibility

### Changed
- Version bumped to 0.2.6
- Updated signature.json with new certificates

## [0.2.7] - 2026-09-01

### Added
- Web interface with 6 pages (Dashboard, Offboard Users, Bulk Operations, Audit Logs, Queue Management, Settings)
- Dashboard API endpoint
- API calls for Disable, Wipe, Delete Tokens buttons
- Bulk operations via web interface
- Queue processing via web interface
- User status (enabled/disabled) display
- DisplayName in user list
- CSP-safe event handlers

## [0.2.8] - 2026-09-02

### Added
- Wipe Agent system for Windows clients
- Deploy script to user's Nextcloud folder
- Download wipe agent script
- Automatic files:scan after deploy
- PowerShell agent for local file deletion
- Task Scheduler installation script
- checkWipeStatus API endpoint

### Fixed
- Remote wipe now deletes all user tokens
- Wipe doesn't fail on stale device tokens
- File scanner runs after script deploy
