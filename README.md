# WP-Kmaps Plugin

## Dependencies
This plugin **requires** the [Mandala Proxy](https://github.com/shanti-uva/mandala-wp-proxy)
plugin to be installed and active on the same WordPress site. The React app fetches asset
detail JSON through Mandala Proxy's same-origin `/proxy/json` route rather than calling the
Mandala/D11 API hosts directly cross-origin (needed because the app can be embedded on any
WordPress site, not just ones Mandala/D11 infrastructure controls — see
[Spike 6](https://github.com/uvalib/mandala-navina/blob/main/docs/spikes/spike-06-api-compatibility.md)
in the `mandala-navina` repo for the full rationale).

WordPress enforces this via the `Requires Plugins: mandala-proxy` header in `mandala.php` (WP
6.5+) — activating this plugin without Mandala Proxy present will be blocked. **The dependency
check matches by installed folder name**, so when installing Mandala Proxy from its repo
(`shanti-uva/mandala-wp-proxy`), install it into a plugin folder literally named `mandala-proxy`
(matching its main file, `mandala-proxy.php`) rather than the repo's own name.

## Introduction
This plugin when installed will expose a React Application as Portals to be embedded in any WordPress site.
This React application will integrate content from [Mandala Kmaps &amp; Assets](https://mandala.library.virginia.edu/).
The content is invoked through hash-based urls in the format of `#/{Kmap or asset type}/{id or path}`, such as:

* `/#/places/637`
* `/#/collections/all/deck`
* `/#/images/3996`
* `/#/search/deck?filters=&searchText=meditation`

The React App must be built from the code for in the [Mandala Git Repo](https://github.com/shanti-uva/mandala-om), and 
the build of that React App must be placed in an `\app\` subfolder within this plugins home directory.

## Requirements

- **Plugin name:** Mandala React App (repository [`shanti-uva/wp-kmaps`](https://github.com/shanti-uva/wp-kmaps)).
- **WordPress** 5.2 or later; **PHP** 7.2 or later.
- A **build of the Mandala Om React app** at `app/build/` (the plugin reads `app/build/asset-manifest.json`).
- **Advanced Custom Fields (ACF)** is optional but enables the per-page `use_short_codes` and
  `custom_kmap_trees` fields described below.

## How It Works
The Wp-Kmaps Plugin places mandala content on a WordPress page through the use of custom shortcodes defined 
by the module. These shortcodes consist of the following:

* `[mandalaroot]` : This shortcode is required to insert Mandala content. It inserts the 
element `<div id="mandala-root"></div>` into which Mandala content is loaded when a
mandala hash is detected, e.g. `#/subjects/all/list`. This will load both the mandala page content and the advanced 
search/facets side-column, unless it is also accompanied by the `[madvseach]` shortcode. In that case, it only inserts 
the mandala main content page, and the side-column is inserted where ever that latter shortcode is found.
* `[madvsearch]` : This optional shortcode inserts just the advanced search side column and must be used in conjuction with 
the above `[mandalaroot]` shortcode. It inserts the element `<div id='advancedSearchPortal'></div>` wherever it is 
found.
* `[mandalaglobalsearch]` : This required shortcode inserts the searchbox to be used for searching Mandala content. 
It is not required but recommended. It inserts the element `<span id='basicSearchPortal'></span>` wherever it is 
found.

The plugin is designed to work with any theme by using theme hooks to place the shortcodes. The names of the hooks 
can be set on a site by site basis in the settings for the module. Or, automatic insertion of the shortcodes can be 
turned off, and they can be inserted directly into a custom theme or on a page by page basis by editors.

The React app listens for hashes, and when a hash matches one of the apps routes, the app will load Mandala content 
into the element `<div id="mandala-root"></div>` as described above.

The three shortcodes insert the following portal elements (mount points the React app renders into):

| Shortcode | Element inserted |
|-----------|------------------|
| `[mandalaroot]` | `<div id="mandala-root"></div>` |
| `[mandalaglobalsearch]` | `<span id="basicSearchPortal"></span>` |
| `[madvsearch]` | `<div id="advancedSearchPortal"></div>` |

### Placement methods

Mandala content can be placed three ways (combine as needed):

1. **Theme hooks** — set hook names in the plugin settings (below) and enable *Insert Shortcodes
   Automatically*; the plugin attaches the shortcodes to those hooks site-wide.
2. **Manual shortcodes** — turn automatic insertion off and place `[mandalaroot]`, `[madvsearch]`,
   and `[mandalaglobalsearch]` directly in templates or individual pages.
3. **Widget** — the bundled **Mandala Widget** inserts the advanced-search portal
   (`#advancedSearchPortal`) into any widget area (e.g. a sidebar).

When ACF is present, a per-page **`use_short_codes`** field can disable automatic insertion on
individual pages.

## Settings

The plugin registers a **Settings → Mandala Settings** page (options stored in the
`mandala_plugin_options` option). Fields:

- **Theme Hook Names** — *Insert Shortcodes Automatically* (`automatic_insert`), *Mandala Root Hook*
  (`main_hook_name`), *Global Search Hook* (`search_hook_name`), *Advanced Search Hook*
  (`advanced_search_hook_name`).
- **Other Settings** — *Sitemap Page ID* (`sitemap_page_id`), *Default Sidebar* (`default_sidebar`),
  *Hash Exceptions* (`hash_exceptions`, one path per line), *Filter Assets by Path* (`path_filter`).

Several of these options are also read by the [Mandala Kadence](https://github.com/shanti-uva/mandala-kadence)
theme (e.g. `default_sidebar`, `sitemap_page_id`).

## Configuration passed to the React app

On the front end the plugin exposes a `window.mandala_wp` object (added as an inline script) so the
React app can read WordPress-side settings:

- `hash_exceptions` — paths that should not trigger Mandala hash handling
- `sidebar_state` — initial sidebar open/closed state (from *Default Sidebar*)
- `initial_path` — the current request path
- `path_filter` — whether to filter assets by path

Additionally, on pages with an ACF **`custom_kmap_trees`** field, the plugin localizes a `treedata`
object to the app. A `mandala` (and `devtest`) body class is added so the theme's WordPress content is
hidden until the app determines there is no matching hash. Requests to the bare path `/mandala/` are
redirected back to the referrer (or the home page).

## Asset loading

The built app's scripts and styles are enqueued from `app/build/asset-manifest.json`
(`enqueue_mandala_manifest`): `main.css`, `runtime-main.js`, `main.js`, and all hashed JS/CSS chunks.
The manifest file's modification time is used as the asset version string for cache-busting, and all
`mandala-*` scripts are loaded with `async defer`. The plugin also enqueues external dependencies
(Google Maps JS, Google Fonts, Font Awesome 5, Bootstrap 4) from CDNs, plus local
`public/js/mandala.js` (jQuery UI glue for the sidebar and hash handling).

## Tibetan & Newar translation REST API

The plugin registers REST routes under `mandala/v1`, backed by the `MandalaTranslate` and
`MandalaNewar` classes in `includes/`. These power the React app's Translation Tool:

- `GET /wp-json/mandala/v1/splittib` — split Tibetan (`tib`) into phrases
- `GET /wp-json/mandala/v1/parsetib` — parse/translate Tibetan into syllables (accepts Wylie or Unicode)
- `GET /wp-json/mandala/v1/parsenewar` — parse Newar (`newar`); supports a JSONP callback via `json_wrf`

## Page templates

Two page templates are provided under `templates/`:

- **Custom Mandala Page Template** (`page-custom.php`) — a copy of the Astra `page.php` template, added
  to the template list so editors can select a page where Mandala content is *not* auto-inserted and can
  instead be positioned manually with `[mandalaroot]`.
- `mandala-page-template.php` — a minimal "mandala" page template.

# Mandala Kadence Theme
This plugin has been refactored to work with any WP theme with the hook settings for this plugin, the widget defined 
here that can be used for sidebars etc., and the shortcodes which can be placed directly in custom templates or 
individual pages. By using some or all of these methods, Mandala content can be added to any theme.

However, we have also developed a special child-theme of Kadence, specifically meant for designing sites with 
Mandala content. This theme is [Mandala Kadence](https://github.com/shanti-uva/mandala-kadence).

# Subsites
The early version of this plugin included code for creating custom "subsites", portions of a site with their own unique 
menus and titles. However, in refactoring the plugin, it made more sense to include this functionality in the
[Mandala Kadence](https://github.com/shanti-uva/mandala-kadence) theme. 

# Known issues / maintenance

- **Version drift:** the plugin header and `$version` say `1.1.1`, but development is on the
  `release/v1.2.0-rc` branch (and `MandalaTranslate` is `1.2.0`). Bump the header version on release.
- **Hardcoded Google Maps API key:** `enqueue_scripts()` embeds a real Maps JS API key in source.
  Consider moving it to a setting/environment value and rotating the exposed key.
- **Runtime CDN dependencies:** Font Awesome, Bootstrap, Google Fonts, and Google Maps are loaded from
  third-party CDNs on every front-end request (external dependency + privacy considerations). Bundling
  them would remove the external calls.
- **Leftover debug/dev code:** several `error_log()` calls remain (e.g. the `/mandala/` redirect and
  "Automatic insert disabled!"), and a `devtest` body class is always added.
- **Committed backup files:** `bak-mandala-orig.php` and `admin/bak-mandala-admin.php` are dead code and
  can be removed.
- **Template inconsistency:** `templates/mandala-page-template.php` mounts into `#root`, whereas the app
  mounts into `#mandala-root`; verify whether that template is still used.
- **Empty `languages/`:** a text domain / `Domain Path` is declared but no translations are loaded.
