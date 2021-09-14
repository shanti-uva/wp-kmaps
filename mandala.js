(function ($) {
    const href = window.location.href;
    if (href.includes('/mandala/')) {
        window.location.href = href.replace('/mandala/', '/');
    }
})(jQuery);