(function ($) {
    'use strict';

    var scale = 1;

    function initUnit(viewport, table) {
        var imageMarks = viewport.querySelectorAll('#viewport div.dragger');
        var imageMarksArray = Array.prototype.slice.call(imageMarks);
        var tableRows = table.querySelectorAll('tbody tr');
        var tableRowsArray = Array.prototype.slice.call(tableRows);


        tableRowsArray.forEach(function (tableRow) {
            highLight(tableRow);
        });

        imageMarksArray.forEach(function (imageMark) {
            highLight(imageMark);
        });

        jQuery('.dragger, #viewport').bind('mousewheel', function (event, delta) {
            event.preventDefault();

            var mousePosition = {x: event.pageX, y: event.pageY};
            var scale = rescaleImage(delta, mousePosition);

            if (scale < 1) {
                jQuery(this).scrollLeft(mousePosition.x - jQuery(this).offset().left);
                jQuery(this).scrollTop(mousePosition.y - jQuery(this).offset().top);
            }
        });

        jQuery('#viewport').dragscrollable({dragSelector: '.dragger, #viewport', acceptPropagatedEvent: false});
    }

    function prepareImage() {
        var img = jQuery('img.dragger');

        var width = img.innerWidth();
        var height = img.innerHeight();

        img.attr('owidth', width);
        img.attr('oheight', height);

        jQuery('div.dragger').each(function (idx) {
            var el = jQuery(this);
            el.attr('owidth', parseInt(el.css('width')));
            el.attr('oheight', parseInt(el.css('height')));
            el.attr('oleft', parseInt(el.css('margin-left')));
            el.attr('otop', parseInt(el.css('margin-top')));
        });
    }

    function rescaleImage(delta, mousePosition) {

        var img = jQuery('img.dragger');

        var original_width = img.attr('owidth');
        var original_height = img.attr('oheight');

        if (!original_width) {
            prepareImage();

            original_width = img.attr('owidth');
            original_height = img.attr('oheight');
        }

        var current_width = img.innerWidth();
        var current_height = img.innerHeight();

        var scale = current_width / original_width;

        var cont = jQuery('#viewport');

        var view_width = parseInt(cont.css('width'));
        var view_height = parseInt(cont.css('height'));

        var minScale = Math.min(view_width / original_width, view_height / original_height);

        var newscale = scale + (delta / 10);
        if (newscale < minScale)
            newscale = minScale;

        if (newscale > 1)
            newscale = 1;

        var correctX = Math.max(0, (view_width - original_width * newscale) / 2);
        var correctY = Math.max(0, (view_height - original_height * newscale) / 2);

        img.attr('width', original_width * newscale);
        img.attr('height', original_height * newscale);
        img.css('margin-left', correctX + 'px');
        img.css('margin-top', correctY + 'px');

        jQuery('div.dragger').each(function (idx) {
            var el = jQuery(this);
            el.css('margin-left', (el.attr('oleft') * newscale + correctX) + 'px');
            el.css('margin-top', (el.attr('otop') * newscale + correctY) + 'px');
            el.css('width', el.attr('owidth') * newscale + 'px');
            el.css('height', el.attr('oheight') * newscale + 'px');
        });

        return newscale;
    }

    function fitToWindow() {
        var t = jQuery('#g_container');
        var width = t.innerWidth() - (parseInt(t.css('padding-right')) || 0) - (parseInt(t.css('padding-left')) || 0);
        jQuery('#viewport, #viewtable').css('width', Math.ceil(width * 0.48));
    }

    function highLight(element) {
        element.addEventListener('mouseover', function () {
            this.classList.add('over');

            var items = document.querySelectorAll('[data-code="' + element.dataset.code + '"]');
            items.forEach(function (item) {
                item.classList.add('over');
            });
        });
        element.addEventListener('mouseleave', function () {
            this.classList.remove('over');

            var items = document.querySelectorAll('[data-code="' + element.dataset.code + '"]');
            items.forEach(function (item) {
                item.classList.remove('over');
            });
        });
    }

    var methods = {
        init: function (viewport, table) {
            initUnit(viewport, table);
        },
        rescaleImage: function (delta, mousePosition) {
            rescaleImage(delta, mousePosition);
        }
    };

    $.fn.unitHelper = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        }
    };
})(jQuery);