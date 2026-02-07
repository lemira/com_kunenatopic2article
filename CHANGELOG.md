# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1] - 2026-02-07

### 1. The topic author is assigned as the article author
- Fixed a coincidental error; now the author of the article is again assigned as the topic starter, not the administrator.

### 2. Plugin plg_system_kunenatopic2articleimgfix - Release v1.1.0 
- Improved Anchor Scrolling for Chromium/Gecko

#### 🚀 What's New
- This update addresses the common issue where browsers (especially Google Chrome and Microsoft Edge) fail to scroll to the specific post anchor (e.g., #2993) upon page load.

#### 🛠 Fixes & Improvements
- Disabled Native Scroll Restoration: Switched to history.scrollRestoration = 'manual' to prevent Chromium from interfering with the script-driven positioning.
- Inline JavaScript Implementation: The fix is now injected directly into the page header, eliminating the need for external JS files and avoiding cache-related issues.
- Post-Load Re-positioning: Added a retry mechanism that monitors page layout shifts for 1.2 seconds after the window.load event. This ensures that even if heavy images or ads load slowly, the browser will snap back to the correct post.
- Vanilla JS: Removed jQuery dependency for this specific task to ensure the fastest possible execution.

#### 📦 Installation
- Download the plg_system_kunenatopic2articleimgfix.zip file.
- Go to your Joomla Admin Panel: System -> Install -> Extensions.
- Upload and install the package.
- IMPORTANT: If you are installing this plugin for the first time, don't forget to Enable it. Go to System -> Plugins, find "System - Kunenatopic2articleimgfix", and click the status icon to make it active.


## [2.0.0] - 2026-01-27

### Changed (Breaking Changes)
- **CSS embedding in articles**: CSS is now embedded directly into the HTML of each created article instead of an external link to the component file
- Each article receives an independent copy of CSS from the component's `media/` folder at creation time
- V1.x.x format articles continue to work but require the component to be installed

### Added
- Conversion script `p2a_convert_to_V2.php` for updating existing articles

---

### Migration from version 1.x.x to 2.0.0

**What has changed:**
- In V1.x.x CSS was linked as an external file — all articles had uniform styling
- In V2.0.0 CSS is embedded in the article — each article is independent and can be customized individually

**Automatic update of existing articles:**

1. Make sure the component's CSS is configured as needed (conversion applies the current version of the component's CSS to version 1 articles)
2. Download `p2a_convert_to_V2.php` from the release, place it in the site root
3. Open in browser: `http://your-site.com/p2a_convert_to_V2.php`
4. The script will update all V1.x.x articles and create a log `p2a_converted.log` with a list of modified articles (ID, title, alias)
5. Delete the script and log files

**Alternatives:**
- If there are few articles and topics haven't changed — delete old articles and recreate them
- You can skip the update, but then the component must remain installed and the article appearance will depend on the component's current CSS

## [1.0.5] - 2026-01-16

### Fixed
- Fixed update server URL in component manifest

## ⚠️ Important Update Notice

**For users of versions 1.0.4 and earlier:**

If you experience automatic update errors, please manually download and install version 1.0.5 or later. This is a one-time manual update - all future updates will work automatically via Joomla's update system.

**How to update manually:**
1. Download the latest version from [Releases](https://github.com/lemira/com_kunenatopic2article/releases/latest)
2. In Joomla: System → Install → Extensions
3. Upload and install the ZIP file (it will update your existing installation)

## [1.0.4] - 2026-01-14

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

[2.0.1]: https://github.com/lemira/com_kunenatopic2article/compare/V2.0.0...V2.0.1
[2.0.0]: https://github.com/lemira/com_kunenatopic2article/compare/V1.0.5...V2.0.0
[1.0.5]: https://github.com/lemira/com_kunenatopic2article/compare/V1.0.4...V1.0.5
[1.0.4]: https://github.com/lemira/com_kunenatopic2article/compare/V1.0.3...V1.0.4
[1.0.3]: https://github.com/lemira/com_kunenatopic2article/compare/V1.0.2...V1.0.3
[1.0.2]: https://github.com/lemira/com_kunenatopic2article/compare/V1.0.1...V1.0.2
[1.0.1]: https://github.com/lemira/com_kunenatopic2article/compare/v.0.0...V1.0.1
[1.0.0]: https://github.com/lemira/com_kunenatopic2article/releases/tag/v1.0.0
