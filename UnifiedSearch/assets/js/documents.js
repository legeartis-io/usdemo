var Documents = function () {
    'use strict';

    var module = {
        sectionNodes: [],
        sections: [],
        startSearchLength: 3,
        searchVal: '',
        init: function (sectionNodes) {
            this.sectionNodes = sectionNodes;
            this.createSections();

            jQuery('#result').append(this.createAccordion());
        },
        createSections: function () {
            var that     = this;
            var section  = {};
            var sections = [];
            Array.prototype.slice.call(this.sectionNodes).forEach(function (node) {
                section = {};
                section.node = node;
                section.name = node.querySelector('h2').innerText;
                sections.push(section);
            });

            this.sections = sections;

            this.sections.forEach(function (section) {
                var markInstance = new Mark(section.node);

                var keyword = that.searchVal;

                var options = {};

                markInstance.unmark({
                    done: function () {
                        markInstance.mark(keyword, options);
                    }
                });
            });

        },
        createAccordion: function () {
            var template = [
                '<div class="columns">',
                '<ul class="accordion" data-accordion data-multi-expand="true" data-allow-all-closed="true">'
            ];

            var searchVal = this.searchVal;
            var that = this;

            this.sections.forEach(function (section, index) {
                if (section.node.innerText.toLowerCase().indexOf(searchVal.toLowerCase()) !== -1) {
                    var content = section.node.innerHTML;
                    var itemClass = searchVal.length ? 'accordion-item is-active' : 'accordion-item';
                    var item = [
                        '<li class="' + itemClass + '" data-accordion-item>',
                        '<a href="javascript:void(0)" class="accordion-title"><h5>' + section.name + '</h5></a>',
                        '<div class="accordion-content" data-tab-content>',
                        '<div id="' + index + '-acc">' + content + '</div>',
                        '</div>',
                        '</li>'
                    ];

                    template.push(item.join(''));
                }

            });

            template.push(['</ul>', '</div>'].join(''));

            return jQuery(template.join(''));
        },
        search: function (searchVal) {
            this.searchVal = searchVal.length > this.startSearchLength ? searchVal : '';
            this.filterElements();
        },
        filterElements: function () {
            jQuery('#result').html('');
            this.init(this.sectionNodes);
            /** @namespace Foundation.Accordion */
            var acc = new Foundation.Accordion(jQuery('ul.accordion'), {});
            acc.destroy();
            acc = new Foundation.Accordion(jQuery('ul.accordion'), {});
        }
    };

    return module;
};