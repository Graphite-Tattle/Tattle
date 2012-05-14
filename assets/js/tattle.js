//Taken from http://www.alessioatzeni.com/blog/simple-tooltip-with-jquery-only-text/
function attachTooltips() {
        // Tooltip only Text
        $('.masterTooltip').hover(function(e){
                var mousex = e.pageX + 20;
                var mousey = e.pageY + 10;
                // Hover over code
                var title = $(this).attr('title');
                $(this).data('tipText', title).removeAttr('title');
                $('<p class="tattletooltip"></p>')
                .text(title)
                .appendTo('body')
                .fadeIn('slow')
                .css({ top: mousey, left: mousex });
        }, function() {
                // Hover out code
                $(this).attr('title', $(this).data('tipText'));
                $('.tattletooltip').remove();
        }).mousemove(function(e) {
                var mousex = e.pageX + 20; //Get X coordinates
                var mousey = e.pageY + 10; //Get Y coordinates
                $('.tattletooltip')
                .css({ top: mousey, left: mousex })
        });
}

