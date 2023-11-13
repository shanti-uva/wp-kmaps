(function ($) {
    // Check to see if there are multiple nav elements
    // TODO: Is this still necessary?
    $navs = $(".flourishing-main-wrapper .elementor-widget-wrap > .elementor-widget-nav-menu");
    if ($navs.length > 1) {
        $($navs[0]).attr('style', 'display:none !important');
        $($navs[1]).css('margin-top', '-23px');
    }

    // ****  Sidebar Setup **** //
    // Move setting button into group (Do this in Mandala code?)
    $(document).ready(() => {
        $('#browseSearchPortal button#advanced-site-settings').appendTo($('#browseSearchPortal .c-MainSearchToggle--group'));
    });

    // Hide sidebar on circle-x button clicks
    $('#secondary').on('click', '.search-column-close-filters, .treeNav-header__closeButton', (e) => {
        $('#secondary').hide();
    });

    // Open sidebar when buttons in navbar are clicked
    $('#browseSearchPortal').on('click', '#advanced-search-tree-toggle, #main-search-tree-toggle',
        (e) => {
            if ($('#secondary').is(":hidden")) {
                $('#secondary').show();
                window.scrollTo(0,0);
            }
            setTimeout(() => {
                if ($('#l-column__search, #l-column__search--treeNav').hasClass('closed')) {
                    $('#secondary').hide();
                } else if ($('#secondary').is(":hidden")) {
                    $('#secondary').show();
                    window.scrollTo(0,0);
                }
            }, 10);
        });

    // **** Hash Processing ****//
    // Function to add "mandala" class to body when a link with a mandala hash is clicked
    // This has the effect of immediately hiding the WP content before the mandala content loads
    // TODO: check if this is still necessary or if it can be merged with "hashchange" code below
    $('body').on('click', 'a', function (e) {
        const ael = $(this);
        const anchor_ref = ael.data('anchor-ref');
        const href = ael.attr('href');
        if (href?.includes('#/')) {
            const he = window?.mandala?.hash_execptions; // hash exceptions are set in the admin page
            const hash = '#/' + href.split("#/")[1];
            if (!he || !he.includes(hash)) {
                if (!$('body').hasClass('mandala') && e.originalEvent.which === 1 && e.originalEvent.metaKey === false) {
                    window.scrollTo(0, 0);
                    $('body').addClass('mandala');
                }
            }
        } else if (anchor_ref?.length > 0) {
            if ($('#shanti-texts-body')?.length === 1) {
                // For Footnotes in texts use anchors to scroll not as paths.
                const curroff = ael.offset().top;
                const currscroll = $('#shanti-texts-body').scrollTop();
                let offtop = $(anchor_ref).offset().top;
                // console.log(`curroff: ${curroff}, anchoroff: ${offtop}, curr scroll: ${currscroll}`);
                offtop = currscroll + offtop - 175;
                if (!isNaN(offtop)) {
                    $('#shanti-texts-body').animate({
                        scrollTop: offtop
                    }, 800);
                }
                return false;
            } else {
                $(anchor_ref).get(0).scrollIntoView();
            }
        }
    });

    // 'mandala' body class is initially added automatically by plugin php, this hides mandala page content
    // Remove it here if there is no hash to load Mandala content
    setTimeout(function() {
        const hash = window.location.hash;
        const he = window?.mandala_wp?.hash_exceptions; // hash exceptions are set in the admin page and added as a js object
        const setFilterQuery = hash?.includes('#/?'); // if the hash include '/#/?...' this is to set filters in advanced search sidebar but we still want the WP page to show so remove mandala class
        if (hash === '' || hash === '#/' || he?.includes(hash) || setFilterQuery) {
            $('body').removeClass('mandala');
        }

        // Remove loading class from body that hides both #primary and #secondary via styles.
        $('body.loading').removeClass('loading');
    }, 500);

    // Use Hash Listener to determine when hash is removed and re-expose WP site by removing mandala class from body
    // Mainly for back button cases, but also for menu-highlighting
    window.addEventListener('hashchange', function(e) {
        const hv = window.location.hash;
        // console.log('hv is', hv);
        const he = window?.mandala?.hash_execptions; // hash exceptions are set in the admin page and added as a js object
        if (['', '#/', '#'].includes(hv) || he?.includes(hv)) {  // When there is no hash or its an exception
            $('body').removeClass('mandala');  // remove mandala body class allows WP content to show
            // console.log("removing mandala in listener");
            // Highlight the home menu item and remove any previous highlighted items
            $('#primary-menu .current-menu-item').removeClass('current-menu-item');
            $('#primary-menu .menu-item-home').addClass('current-menu-item');
        } else {
            // When there is a hash, add mandala class and highlight appropriate menu item
            if (!$('body').hasClass('mandala')) { $('body').addClass('mandala'); }
            // Look for submenu items and highlight the parent
            let mi = false;
            $('#primary-menu li.menu-item a').each(function (i) {
                let eh = $(this).attr('href');
                if (eh.includes('/#/')) {
                    // remove the domain or path if there is one to just compare hashes
                    const ehpts = eh.split('/#/');
                    eh = '#/' + ehpts[1];
                }
                // If menu item found with href same as current hash, then highlight it
                if (eh == hv) {
                    if ($(this).parents('.sub-menu').length > 0) {
                        $('#primary-menu .current-menu-item').removeClass('current-menu-item');
                        $(this).parents('.sub-menu').eq(0).parent().addClass('current-menu-item');
                    }
                }
            });
        }
    }, false);

    // **** Handle WP Sidebar Menu **** //
    const menu_id = $('#secondary .widget_nav_menu').eq(0).attr('id');
    if (menu_id) {
        if (window?.mandala_wp) {
            window.mandala_wp.navmenu = menu_id;  // Add menu id to mandala_wp settings in window and use in react.
        }
    }
})(jQuery);
