# Beats Upload & Player

Beats Upload & Player is a WordPress plugin built for producers and beat stores that need a quick way to upload audio, manage artwork, and publish a browsable catalog with a global player.

## Highlights
- Front-end infinite scroll grid that groups beats by genre and streams audio through a sticky global player.
- AJAX-powered loader that keeps fetching categories as listeners scroll the page.
- Optional category search bar that jumps straight to the matching section of the catalog.
- Secure upload form shortcode for logged-in users with audio + cover image requirements.
- Beats Manager admin screen with pagination, live search, inline editing, image replace/remove, and one-click delete.
- Metadata stored as JSON alongside uploaded assets inside the WordPress uploads directory—no custom tables required.

## Requirements
- WordPress 6.0 or newer.
- PHP 7.4+ with `fileinfo` enabled (needed for the upload validation helpers).
- An account with `manage_options` capability to access the Beats Manager dashboard.

## Installation
1. Copy the `beats-upload-player` folder into `wp-content/plugins/` or upload the ZIP via **Plugins → Add New → Upload Plugin**.
2. Activate **Beats Upload & Player** from the WordPress Plugins screen.
3. Visit **Beats Manager** in the admin menu to start uploading beats or to manage the library.

## Shortcodes
- `[beats_display_home]` – Renders the AJAX catalog container. The plugin auto-loads batches of categories, builds beat cards, and fires a `beats-loaded` event after each fetch.
- `[beats_global_player]` – Outputs the glassmorphic global player that listens for card interactions and keeps playback in sync across the catalog.
- `[beats_category_search]` – Adds a smart search bar that scrolls to matching categories and highlights the section briefly.
- `[beats_upload_form]` – Shows the front-end upload form. Only logged-in users see the fields; visitors are prompted to log in first. Each upload expects an MP3/WAV/M4A file, a cover image, price (optional, CAD), and genre selection.

Add the catalog + player shortcodes to any page to create a front-end beat store experience:

```html
[beats_category_search]
[beats_global_player]
[beats_display_home]
```

## Admin Beats Manager
- Upload new beats with enforced audio/image validation and optional CAD pricing.
- Browse the library with live search, pagination, inline audio playback, and edit fields.
- Replace or remove cover art without touching the filesystem; uploads are deduplicated when possible.
- Delete beats with a confirmation prompt—unused audio and artwork files are removed automatically.

## File Storage & Data Model
- Audio files live in `wp-content/uploads/beats/audio/`.
- Cover images live in `wp-content/uploads/beats/images/`.
- Metadata for every beat is stored in `wp-content/uploads/beats/beats.json`.
- The helper in `includes/beats-categories.php` defines the default genre list; adjust it if you need bespoke categories.

## Development Notes
- Public scripts (`beats-loader.js`, `beats-player.js`) communicate via custom DOM events, so you can listen for `beats-loaded` to trigger custom UI.
- Admin tooling relies on WordPress’ bundled jQuery. Extend the dashboard by hooking into the same AJAX actions (`beats_list`, `beats_update`, etc.).
- Remember to back up the uploads directory before deploying to production or migrating between environments.
