(function ($) {
    // Check to see if there are multiple nav elements
    $navs = $(".flourishing-main-wrapper .elementor-widget-wrap > .elementor-widget-nav-menu");
    if ($navs.length > 1) {
        $($navs[0]).attr('style', 'display:none !important');
        $($navs[1]).css('margin-top', '-23px');
        $($navs[1]).find('ul.elementor-nav-menu > li > a.menu-link').attr('style', 'background: inherit !important');
        // $($navs[1]).find('ul.elementor-nav-menu > li > a.menu-link').hover(function() {
        //     $(this).attr('style', 'background-color: #af7500 !important');
        // }, function() {
        //     $(this).attr('style', 'background-color: inherit !important');
        // })

        // Highlight the correct subsite menu link.
        $($navs[1]).find("a[href*='" + location.hash.substr(2) + "']").attr('style', 'background-color: #fff !important;color:#212529');
        $(window).on('hashchange', function(e) {
                $($navs[1]).find("a").attr('style', 'background-color:transparent !important');
                $($navs[1]).find("a[href*='" + location.hash.substr(2) + "']").attr('style', 'background-color: #fff !important;color:#212529');
        });
    }
})(jQuery);