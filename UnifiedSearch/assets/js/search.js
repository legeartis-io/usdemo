var searchModule = function () {
    'use strict';

    var module = {
        form: null,
        searchInput: null,
        submitButton: null,
        tagToggler: null,
        tagContainer: null,
        addTagInput: null,
        addTagButton: null,
        indexProgressBars: [],
        autocompleteUrl: null,
        autocopleteTemplate: '',
        init: function (form) {
            this.form              = form;
            this.searchInput       = form.querySelector('input[name="query"]');
            this.resetButton       = form.querySelector('a.reset-input');
            this.submitButton      = form.querySelector('[type="submit"]');
            this.tagToggler        = form.querySelector('#filter-by-tag');
            this.tagContainer      = form.querySelector('#tag-container');
            this.addTagInput       = form.querySelector('#add-tag');
            this.addTagButton      = form.querySelector('#add-tag-button');
            this.indexProgressBars = document.querySelectorAll('.index-progress');
            this.autocompleteUrl   = this.searchInput.dataset.autocompleteUrl;
            this.tagContainer.style.display = this.tagToggler.checked ? 'block' : 'none';

            var removeLinks = form.querySelectorAll('fieldset .tags .badge > a');
            Array.prototype.slice.call(removeLinks).forEach(function (link) {
                link.addEventListener('click', function () {
                    var block = this.closest('.badge-block');
                    block.remove();
                });
            });

            this.addEvents();
        },
        addEvents: function () {
            var tagContainer = this.tagContainer;
            var addTagInput = this.addTagInput;
            var that = this;

            this.form.addEventListener('submit', this.submitForm);
            this.searchInput.addEventListener('keyup', function () {
                that.keyupInput();
            });
            this.submitButton.addEventListener('click', this.clickSubmit);
            this.resetButton.addEventListener('click', function () {
                that.resetSearchInput();
            });
            this.addTagButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (addTagInput.value.length) {
                    var block = document.createElement('span');
                    block.classList.add('badge-block');
                    var removeLink = document.createElement('a');
                    removeLink.innerHTML = '<i class="fas fa-times-circle"></i>';
                    block.innerHTML = [
                        '<input type="hidden" name="tags[]" value="' + addTagInput.value + '">',
                        '<span class="badge primary">' + addTagInput.value + ' </span>'
                    ].join('');

                    block.querySelector('.badge').appendChild(removeLink);

                    tagContainer.querySelector('fieldset .tags').appendChild(block);
                    addTagInput.value = '';

                    removeLink.addEventListener('click', function (e) {
                        e.preventDefault();
                        block.remove();
                    });
                }
            });

            this.searchInput.addEventListener('input', function() {
                that.getAutocomplete();
            });

            this.tagToggler.addEventListener('change', function () {
                tagContainer.style.display = this.checked ? 'block' : 'none';
            });

            if (this.indexProgressBars.length) {
                this.getIndexationProgress();
            }
        },
        getAutocomplete: function() {
            var that = this;

            var wrapper = this.searchInput.closest('.input-group');
            var oldList = wrapper.querySelector('.autocomplete-list');

            if (oldList) {
                oldList.remove();
            }

            if (this.searchInput.value.length > 3) {
                fetch(this.autocompleteUrl + '&query=' + encodeURI(this.searchInput.value)).then(function (resp) {
                        return resp.json();
                    }).then(function (data) {
                        /** @namespace data.queryCompletions */
                        if (data.queryCompletions.length) {
                            that.drawAutocompleteList(data.queryCompletions);
                        }
                    });
            }
        },
        drawAutocompleteList: function(queryCompletions) {
            var elem = document.createElement('ul');
            var that = this;

            var documentListener = document.addEventListener('click', function () {
                elem.remove();
            });

            elem.classList.add('autocomplete-list');

            queryCompletions.forEach(function (item) {
                var itemElem = document.createElement('li');
                itemElem.innerText = item;
                itemElem.addEventListener('click', function () {
                    that.searchInput.value = this.innerText;
                    elem.remove();
                    document.removeEventListener('click', documentListener);
                });

                elem.appendChild(itemElem);
            });

            var wrapper = this.searchInput.closest('.input-group');
            var oldList = wrapper.querySelector('.autocomplete-list');

            if (oldList) {
                oldList.remove();
            }

            wrapper.appendChild(elem);
        },
        resetSearchInput: function() {
            this.searchInput.value = '';
            this.keyupInput();
        },
        getIndexationProgress: function() {
            Array.prototype.slice.call(this.indexProgressBars).forEach(function (progressBarNode) {
                var progressMeter = progressBarNode.querySelector('.progress-meter');
                var progressMeterText = progressBarNode.querySelector('.progress-meter-text');
                var percent = 0;
                var interval = setInterval(function () {
                    jQuery.ajax({
                        url: progressBarNode.dataset.getProgressUrl,
                        complete: function (data) {
                            percent = data.responseJSON.indexationProgress <= 0 ? 0 : data.responseJSON.indexationProgress;
                            progressMeter.style.width   = percent + '%';
                            progressMeterText.innerText = percent + '%';
                            if (percent >= 100) {
                                clearInterval(interval);
                                progressBarNode.remove();

                                var url = new URL(window.location.href);
                                url.searchParams.delete('ssd');
                                window.location.href = url.toString();
                            }
                        }
                    });
                }, 1000);
            });
        },
        submitForm: function (event) {},
        keyupInput: function () {
            if (this.searchInput.value) {
                this.resetButton.style.display = 'block';
            } else {
                this.resetButton.style.display = 'none';
            }
        },
        clickSubmit: function (event) {}
    };

    return module;
};