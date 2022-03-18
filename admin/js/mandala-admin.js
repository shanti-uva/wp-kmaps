(function($) {
    console.log("Mandala admin loaded");
    console.log($("#mandala_custom_styles"));
    $("#mandala_custom_styles").linedtextarea(
        {selectedLine: 1}
    );

    $('#setting-error-custom_styles').appendTo('#styles_messages');
})(jQuery);