# WP-Kmaps Plugin

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
* `mandalaglobalsearch` : This required shortcode inserts the searchbox to be used for searching Mandala content. 
It is not required but recommended. It inserts the element `<span id='basicSearchPortal'></span>` wherever it is 
found.

The plugin is designed to work with any theme by using theme hooks to place the shortcodes. The names of the hooks 
can be set on a site by site basis in the settings for the module. Or, automatic insertion of the shortcodes can be 
turned off, and they can be inserted directly into a custom theme or on a page by page basis by editors.

The React app listens for hashes, and when a hash matches one of the apps routes, the app will load Mandala content 
into the element `<div id="mandala-root"></div>` as described above.


