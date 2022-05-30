var Validation = function () {
    'use strict';

    return {
        init: function (form) {
            this.form = form;
            this.requiredInputs = Array.prototype.slice.call(form.querySelectorAll('[data-required]'));

            this.removeHighlightOnFocus(this.requiredInputs);
        },
        removeHighlightOnFocus: function (inputs) {
            var that = this;
            ['focus', 'change', 'keyup'].forEach(function (evt) {
                inputs.forEach(function (input) {
                    input.addEventListener(evt, function () {
                        that.removeHighlight([input]);
                    });
                });
            });
        },
        validate: function () {
            var invalidInputs = this.requiredInputs.filter(function (input) {
                return !input.value;
            });
            this.removeHighlight(this.requiredInputs);

            this.highlightInvalid(invalidInputs);

            return invalidInputs.length <= 0;
        },

        highlightInvalid: function (inputs) {
            inputs.forEach(function (input) {
                input.classList.add('is-invalid-input');
            });
        },

        removeHighlight: function (inputs) {
            inputs.forEach(function (input) {
                input.classList.remove('is-invalid-input');
            });
        }
    };
};