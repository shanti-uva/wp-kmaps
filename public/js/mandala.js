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

})(jQuery);