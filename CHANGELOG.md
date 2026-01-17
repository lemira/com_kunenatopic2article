# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.5] - 2025-01-16

### Fixed
- Fixed update server URL in component manifest

## [1.0.4] - 2025-01-14

### Added
- **Comprehensive video content processing**: new `VideoProcessor.php` helper for automatic recognition and processing of video links from various platforms (YouTube, Vimeo, etc.)
- **Dual-mode video handling**:
  - Integration with Joomla AllVideos plugin (recommended mode) — automatic conversion of links to AllVideos tags for video playback directly in articles
  - Standalone mode — creation of responsive and secure iframes for YouTube and Vimeo, "pretty" links for other platforms
- **Automatic detection** of AllVideos plugin availability and status
- **Administrator notification** with recommendation to install/enable AllVideos plugin when absent
- New language constants for video content handling

### Changed
- Updated `View/Topic/HtmlView.php` to check AllVideos plugin availability and display appropriate recommendations

### Improved
- Visual presentation of video content in created articles
- Reliability of video playback from Kunena posts

## [1.0.3] - 2025-12-10

### Fixed
- Added GPL license headers to all PHP files for JED compliance
- Added JEXEC security checks to all PHP files
- Fixed XML manifest structure (removed duplicate entries)
- Fixed language file paths and naming convention
- Removed duplicate language keys in .ini files
- Added missing language keys for error messages
- Fixed SQL uninstall file path in manifest
- Corrected namespace declaration order in PHP files

### Changed
- Renamed language files to follow Joomla 5 standards (removed language prefix)
- Updated language file declarations in XML manifest
- Replaced error_log() calls with proper error handling
- Modified BBCode parser files to include GPL headers
- Language constant values now properly quoted in .ini files
- Removed trailing spaces from language strings

### Added
- LICENSE_MIT.txt file for BBCode parser attribution
- Explicit language file entries in XML manifest
- com_kunenatopic2article.sys.ini files for all languages

### Technical
- Passed JED Checker validation
- Improved code compliance with Joomla 5 standards
- Enhanced security with JEXEC checks in all entry points

## [1.0.2] - 2025-01-07

### Changed
- Updated XML manifest with full author name
- Changed creation date format to English
- Fixed SQL uninstall file path from `sql/` to `admin/sql/`
- Improved services provider path specification

### Added
- Update server configuration for automatic updates
- CHANGELOG.md file for version history tracking

### Technical
- Prepared component for JED (Joomla Extensions Directory) submission
- Enhanced compatibility documentation

## [1.0.1] - 2025-01-XX

### Added
- Initial public release
- Support for Joomla 5.x and Kunena 6.x
- Two post transfer schemes: Sequential (Flat) and Threaded (Tree)
- Customizable post info blocks
- BBCode to HTML parsing using chriskonnertz/bbcode
- Article splitting for large topics
- Author filtering functionality
- Email notifications to topic authors and administrators
- Multi-language support: English, German, Russian
- CSS styling customization
- Preview function before article creation
- Precise positioning plugin (plg_system_kunenatopic2articleimgfix)

### Features
- Preserves formatting, links, and BBCode content
- Handles hidden and deleted posts intelligently
- Maintains parent-child post relationships in tree mode
- Creates service information lines in articles
- Provides detailed creation reports with article links
- Supports utf8mb4 database encoding

## [1.0.0] - 2025-01-XX

### Added
- Initial development version
- Core functionality for converting Kunena topics to Joomla articles

---

## Release Notes Format

### Types of changes:
- **Added** - for new features
- **Changed** - for changes in existing functionality
- **Deprecated** - for soon-to-be removed features
- **Removed** - for now removed features
- **Fixed** - for any bug fixes
- **Security** - in case of vulnerabilities

### Version numbering:
- **Major.Minor.Patch** (e.g., 1.0.2)
- **Major** - incompatible API changes
- **Minor** - new functionality in a backward compatible manner
- **Patch** - backward compatible bug fixes

---

[1.0.5]: https://github.com/lemira/com_kunenatopic2article/compare/V1.0.4...V1.0.5
[1.0.4]: https://github.com/lemira/com_kunenatopic2article/compare/V1.0.3...V1.0.4
[1.0.3]: https://github.com/lemira/com_kunenatopic2article/compare/V1.0.2...V1.0.3
[1.0.2]: https://github.com/lemira/com_kunenatopic2article/compare/V1.0.1...V1.0.2
[1.0.1]: https://github.com/lemira/com_kunenatopic2article/compare/Vv.0.0...V1.0.1
[1.0.0]: https://github.com/lemira/com_kunenatopic2article/releases/tag/v1.0.0
