# Video Link Processing in the Component

[⬅️ Component purpose, parameters, installation, operation](../README.md) | [📖 Post relations in Kunena, info block, parsing, languages](FUNCTIONALITY.md)
| [ℹ️ Emails about article creation, database encoding, plugin](ADDITIONAL.md)

## Overview

The component automatically recognizes and processes video links from various platforms when transferring Kunena posts to Joomla articles. Video links in articles also work with responsive design on mobile devices.

## How It Works

### The component can process video links in two ways - using the Joomla AllVideos plugin or without it. The component automatically detects whether the AllVideos plugin is enabled.

For Joomla 5, AllVideos is still recommended: it can improve video display in created articles and provides a unified player style for many platforms.

For Joomla 6, AllVideos integration is disabled automatically until JoomlaWorks officially announces Joomla 6 compatibility. In this mode the component does not generate AllVideos tags and uses its own iframes or styled links.

If the AllVideos plugin is enabled, the component converts links into AllVideos tags, which are displayed in the article as video windows within the plugin's capabilities. As a result, the corresponding videos can be played directly in the article.

If AllVideos is not installed or is installed but not enabled:
- Upon calling the component, the administrator receives a message that AllVideos is not installed or enabled in Joomla.
- For YouTube and Vimeo, custom iframes are generated for embedding videos. Thus, videos from these platforms are displayed directly in the article
- For other platforms, clickable links are created in the article in a special format, with a camera icon 📹. When hovering over such a link, a helpful tooltip is displayed.

### Special Processing Cases:

**Facebook Video:**
Always displayed as a styled link (even with AllVideos enabled), as Facebook blocks video embedding in iframes for security reasons.

**YouTube with Timestamps:**
If a link contains a timestamp (e.g., `?t=42s`), the component creates its own iframe instead of using AllVideos to preserve the playback starting point at the specified moment.

## Installing AllVideos (recommended for Joomla 5)

1. Download the AllVideos plugin from JED (https://extensions.joomla.org/)
2. Install via System → Install → Extensions
3. Activate the plugin in System → Plugins → Content - AllVideos
4. On Joomla 5, the component will automatically detect the plugin's presence and use it

Benefits of using AllVideos:
- Support for multiple video platforms
- Unified display style for all videos
- Automatic updates when platform APIs change
- Additional display settings (size, alignment, etc.)

## Platform Support

**YouTube and Vimeo:**
- Always displayed as a window via a native iframe or the AllVideos plugin

**Dailymotion and SoundCloud:**
- With AllVideos: window via plugin
- Without AllVideos: styled link

**Facebook:**
- Always styled link (due to Facebook restrictions)

## Visual Design

**Video Windows (iframe):**
- Automatically adapt to screen width
- Have rounded corners and shadow for visual emphasis
- Styling via `kun_p2a.css` file

**Styled Links:**
- Displayed with video camera icon 📹
- Have blue gradient background
- Show tooltip on hover
- Animation on interaction (lift and shadow increase)

## Troubleshooting

Due to the variety of video platforms and players, video links in articles require special attention. If it's not obvious that a link has been transferred to an article and works correctly, we recommend checking it by comparing with the original in the Kunena post.

**If video doesn't display:**
1. Check the URL correctness in the original Kunena post
2. Make sure the video hasn't been deleted by the owner
3. For Facebook: ensure the video is public (not for friends only)
4. For YouTube: check that the video isn't blocked in your region

**If YouTube timestamp doesn't work:**
- Format must be: `?t=42s`, `?t=1m30s`, or `?t=1h2m3s`

## Possible Improvements

- Adding support for other popular platforms (TikTok, Instagram, etc.)
- Configuring iframe sizes in component parameters (instead of CSS)
- Ability to choose behavior for each platform separately

## Structured List of Supported Formats

### YouTube
✅ Standard:
```
https://www.youtube.com/watch?v=VIDEO_ID
https://youtube.com/watch?v=VIDEO_ID
https://youtu.be/VIDEO_ID
```

✅ Mobile version:
```
https://m.youtube.com/watch?v=VIDEO_ID
```

✅ With timestamp (various formats):
```
https://www.youtube.com/watch?v=VIDEO_ID&t=42s
https://youtu.be/VIDEO_ID?t=90
https://youtu.be/VIDEO_ID?t=1m30s
```

✅ In BBCode tags:
```
[video]https://www.youtube.com/watch?v=VIDEO_ID[/video]
[url=https://youtu.be/VIDEO_ID]Link text[/url]
```

✅ Video from a Playlist:
```
https://www.youtube.com/watch?v=VIDEO_ID&list=PLAYLIST_ID
Only the specified video (VIDEO_ID) is processed; the playlist is ignored
```

### Vimeo
✅ Standard:
```
https://vimeo.com/VIDEO_ID
https://www.vimeo.com/VIDEO_ID
```

✅ Embed links:
```
https://player.vimeo.com/video/VIDEO_ID
```

✅ In BBCode tags:
```
[video]https://vimeo.com/VIDEO_ID[/video]
[url=https://vimeo.com/VIDEO_ID]Link text[/url]
```

### Dailymotion
✅ Standard:
```
https://www.dailymotion.com/video/VIDEO_ID
https://dailymotion.com/video/VIDEO_ID
```

✅ Short links:
```
https://dai.ly/VIDEO_ID
```

✅ Old format with title:
```
https://www.dailymotion.com/video/VIDEO_ID_video-title
```

### Facebook
✅ Standard (links only, not embed):
```
https://www.facebook.com/watch/?v=VIDEO_ID
https://www.facebook.com/username/videos/VIDEO_ID/
https://facebook.com/watch/?v=VIDEO_ID
```

✅ Mobile version:
```
https://m.facebook.com/watch/?v=VIDEO_ID
```

✅ Short links:
```
https://fb.watch/SHORT_CODE/
```

✅ In BBCode tags:
```
[url=https://www.facebook.com/watch/?v=VIDEO_ID]Link text[/url]
```

### SoundCloud
✅ Playlists:
```
https://soundcloud.com/user/sets/playlist-name
```

✅ Individual tracks:
```
https://soundcloud.com/user/track-name
```

✅ With parameters:
```
https://soundcloud.com/user/track?in=user/sets/playlist
```

### Mixed Cases
✅ Multiple links in one line:
```
YouTube: https://youtu.be/VIDEO_ID and Vimeo: https://vimeo.com/VIDEO_ID
```

✅ Link within text:
```
Watch this video https://youtube.com/watch?v=VIDEO_ID on YouTube
```

✅ BBCode with different texts:
```
[url=https://youtube.com/watch?v=VIDEO_ID]Video description[/url]
```

### Not Supported
❌ Platform homepages:
```
https://youtube.com
https://facebook.com
https://vimeo.com
```

❌ Incomplete/broken links:
```
youtube.com/watch?v=
https://youtu.be/
```

❌ Text resembling a link (without protocol):
```
Visit youtube.com to watch
```

[⬅️ Component purpose, parameters, installation, operation](../README.md) | [📖 Post relations in Kunena, info block, parsing, languages](FUNCTIONALITY.md)
| [ℹ️ Emails about article creation, database encoding, plugin](ADDITIONAL.md)
