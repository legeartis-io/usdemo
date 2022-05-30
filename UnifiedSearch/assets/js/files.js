(function ($) {
    'use strict';

    var options;

    options = {
        fileProgressUrl: '',
        uploadFileUrl: '',
        removeUrl: '',
        dropArea: '',
        activeIconTemplate: ''
    };

    function setOptions(params) {
        options = params;
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function handleDrop(e) {
        /** @namespace e.dataTransfer */
        var dt = e.dataTransfer;
        var files = dt.files;
        handleFiles(files);
    }

    function handleFiles(files) {
        uploadFile(files[0]);
    }

    function uploadFile(file) {
        var url = options.uploadFileUrl;
        var formData = new FormData();
        formData.append('file', file);
        jQuery.ajax({
            url: url,
            data: formData,
            processData: false,
            contentType: false,
            type: 'POST',
            dataType: 'json',
            complete: function (data) {
                /** @namespace data.responseJSON */
                var jsonData = data.responseJSON || null;
                if (!jsonData) {
                    showError(data.responseText || data.statusText + ': ' + data.status);
                }

                /** @namespace  jsonData.reasonPhrase */
                if (jsonData && jsonData.reasonPhrase) {
                    window.location.reload();
                }
            }
        });
    }

    function showError(text) {
        var elem = document.createElement('div');
        elem.classList.add('callout');
        elem.classList.add('alert');
        elem.innerText = text.replace(/<(?:.|\n)*?>/gm, '');
        document.querySelector('#service-message').appendChild(elem);
        setTimeout(function () {
            elem.remove();
        }, 5000);
    }

    function initDropAreaEvents() {
        var dropArea = options.dropArea;
        var events = ['dragenter', 'dragover', 'dragleave', 'drop'];

        events.forEach(function (eventName) {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            dropArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropArea.classList.add('highlight');
        }

        function unhighlight() {
            dropArea.classList.remove('highlight');
        }

        dropArea.addEventListener('drop', handleDrop, false);
    }

    function checkProgress(fileRow) {
        var finishStatuses = [
            'FAILED',
            'FINISHED',
            'ABORTED'
        ];

        if (finishStatuses.indexOf(fileRow.dataset.taskStatus) !== -1) {
            return false;
        }

        var cProgress = parseInt(fileRow.dataset.progress || 0);

        if (fileRow) {
            var id = fileRow.querySelector('.file-name').dataset.id;

            jQuery.ajax({
                url: options.fileProgressUrl,
                data: {
                    id: id
                },
                type: 'GET',
                dataType: 'json',
                complete: function (data) {
                    var task = data.responseJSON || null;
                    fileRow.dataset.taskStatus = task.status;
                    if (task) {
                        var progress = task.progressPercent;
                        if (finishStatuses.indexOf(task.status) !== -1) {
                            jQuery(fileRow).trigger('loadingFinished', [task.status]);
                        } else {
                            var bar = fileRow.querySelector('.progress-meter');
                            var progressTextBlock = fileRow.querySelector('.progress-meter-text');

                            if (progress && progress > cProgress) {
                                fileRow.dataset.progress = progress;
                                bar.style.width = progress + '%';
                                if (progressTextBlock) {
                                    progressTextBlock.innerText = progress + '% ' + task.status;
                                }
                            }
                        }


                    }
                }
            });
        }
    }

    function drawProgress(filesRows) {
        var row =  filesRows[0];
        //filesRows.forEach(function (row) {
            if (row.dataset.progress && parseInt(row.dataset.progress) < 100) {
                var name = row.querySelector('.file-name').innerText;
                var t    = setInterval(checkProgress, 3000*10, row);

                jQuery(row).on('loadingFinished', function (event, status) {
                    var row         = event.target;
                    var progressBar = row.querySelector('.progress');
                    var activity    = row.querySelector('.activity');

                    filesRows.forEach(function (item) {
                        var activity = item.querySelector('.activity');
                        if (activity && !row.dataset.active) {
                            activity.innerHTML = '';
                        }
                    });

                    if (progressBar && (status !== 'FAILED' && status !== 'ABORTED')) {
                        progressBar.classList.add('success');
                        progressBar.querySelector('.progress-meter-text').innerText = '100% ' + status;
                    }

                    clearInterval(t);

                    if (activity) {
                        activity.innerHTML = options.activeIconTemplate;
                    }

                    if (status !== 'FAILED' && status !== 'ABORTED') {
                        window.location.reload();
                    }
                });
            }
       // });
    }

    function removeFile(element) {
        const id = $(element).data('id');
        const removeUrl = options.removeUrl + '&id=' + id;
        jQuery.ajax({
            url: removeUrl,
            complete: function () {
                    window.location.reload();
            }
        });
    }

    var methods = {
        init: function (params) {
            setOptions(params);
            initDropAreaEvents();
        },
        removeFile: function (element) {
            removeFile(element);
        },
        drawProgress: function (fileRows) {
              drawProgress(fileRows);
        },
        handleFiles: function (files) {
            handleFiles(files);
        }
    };

    $.fn.filesHelper = function (method, params) {
        var data = [];
        data.push(params);
        if (methods[method]) {
            return methods[method](params);
        }
    };
})(jQuery);