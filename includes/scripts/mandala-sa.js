(function($) {
    /** old
    function mandala_check() {
        const href = window.location.href;
        if (href.includes('/mandala')) {
            const newhref = href.replace('/mandala', '/');
            window.location.replace(newhref);
        }
        const currhash = window.location.hash;
        if (currhash === '#' || currhash === '#/') {
            $('#mandala-root').hide();
            $('#mandala-root ~ article').show();
        } else {
            $('#mandala-root').show();
            $('#mandala-root ~ article').hide();
        }
    }

    $(document).ready(function() {
        mandala_check();
        console.log("checking");
    });

    $( window ).on( 'hashchange', function( e ) {
        mandala_check();
    } );
**/
})(jQuery);