(function ($) {
    // Check to see if there are multiple nav elements
    $navs = $(".flourishing-main-wrapper .elementor-widget-wrap > .elementor-widget-nav-menu");
    if ($navs.length > 1) {
        $($navs[0]).attr('style', 'display:none !important');
        $($navs[1]).css('margin-top', '-23px');
        //$($navs[1]).find('ul.elementor-nav-menu > li > a.menu-link').attr('style', 'background: inherit !important');

        // Highlight the correct subsite menu link.
        // $($navs[1]).find("a[href*='" + window.location.hash.substr(2) + "']").attr('style', 'background-color: #fff !important;color:#212529');

        // // Get parent search params if one exists and use it to highlight subsite menu.
        // let searchParams = new URLSearchParams(window.location.hash.split('?', 2)[1]);
        // if (searchParams.has('parent')) {
        //     $($navs[1]).find("a[href*='" + searchParams.get('parent') + "']").attr('style', 'background-color: #fff !important;color:#212529');
        // }

        // $(window).on('hashchange', function(e) {
        //         $($navs[1]).find("a").attr('style', 'background-color:transparent !important');
        //         $($navs[1]).find("a[href*='" + window.location.hash.substr(2) + "']").attr('style', 'background-color: #fff !important;color:#212529');
        //         // Get parent search params if one exists and use it to highlight subsite menu.
        //         searchParams = new URLSearchParams(window.location.hash.split('?', 2)[1]);
        //         $($navs[1]).find("a[href*='" + searchParams.get('parent') + "']").attr('style', 'background-color: #fff !important;color:#212529');
        // });
    }

    //Resizable script
    /*
    $(".main-content-col").resizable({
        handleSelector: ".vertical-splitter",
        resizeHeight: false
    });
*/
    // Move setting buttong into group (Do this in Mandala code)
    $(document).ready(() => {
        $('#browseSearchPortal button#advanced-site-settings').appendTo($('#browseSearchPortal .c-MainSearchToggle--group'));

    });

    $('#secondary').on('click', '.search-column-close-filters, .treeNav-header__closeButton', (e) => {
        $('#secondary').hide();
    });

    $('#browseSearchPortal').on('click', '#advanced-search-tree-toggle, #main-search-tree-toggle',
        (e) => {
            if ($('#secondary').is(":hidden")) {
                $('#secondary').show();
                window.scrollTo(0,0);
            }
            setTimeout(() => {
                if ($('#l-column__search').hasClass('closed')) {
                    $('#secondary').hide();
                } else if ($('#secondary').is(":hidden")) {
                    $('#secondary').show();
                    window.scrollTo(0,0);
                }
            }, 10);
        });

    // Function to add "mandala" class to body when a link with a mandala hash is clickec
    // This has the effect of immediately hiding the WP content before the mandala content loads
    $('body').on('click', 'a', function () {
        const ael = $(this);
        const anchor_ref = ael.data('anchor-ref');
        const href = ael.attr('href');
        if (href?.includes('#/')) {
            const he = window?.mandala?.hash_execptions; // hash exceptions are set in the admin page
            const hash = '#/' + href.split("#/")[1];
            if (!he.includes(hash)) {
                window.scrollTo(0, 0);
                if (!$('body').hasClass('mandala')) {
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

    // 'mandala' body class is added automatically by plugin php, this hides mandala page content
    // Remove it here if there is no has to load Mandala content
    setTimeout(function() {
        const hash = window.location.hash;
        const he = mandala_settings?.hash_exceptions; // hash exceptions are set in the admin page and added as a js object
        if (hash === '' || hash === '#/' || he?.includes(hash)) {
            $('body').removeClass('mandala');
            setTimeout(function() {$('body').removeClass('mandala');}, 5000); /// just in case
        }
    }, 500);

    // Use Hash Listener to determine when hash is removed and re-expose WP site by removing mandala class from body
    // Mainly for back button cases, but also for menu-highlighting
    window.addEventListener('hashchange', function() {
        const hv = window.location.hash;
        const he = window?.mandala?.hash_execptions; // hash exceptions are set in the admin page and added as a js object
        if (['', '#/', '#'].includes(hv) || he?.includes(hv)) {  // When there is no hash or its an exception
            $('body').removeClass('mandala');  // remove mandala body class allows WP content to show
            console.log("removing mandala in listenter");
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

})(jQuery);