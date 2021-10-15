(function ($) {
    // Check to see if there are multiple nav elements
    $navs = $(".flourishing-main-wrapper .elementor-widget-wrap > .elementor-widget-nav-menu");
    if ($navs.length > 1) {
        $($navs[0]).attr('style', 'display:none !important');
        $($navs[1]).css('margin-top', '-23px');
        $($navs[1]).find('ul.elementor-nav-menu > li > a.menu-link').attr('style', 'background: inherit !important');
        $($navs[1]).find('ul.elementor-nav-menu > li > a.menu-link').hover(function() {
            $(this).attr('style', 'background-color: #af7500 !important');
        }, function() {
            $(this).attr('style', 'background-color: inherit !important');
        })
    }
})(jQuery);