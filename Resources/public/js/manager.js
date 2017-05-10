$(function () {

    var $renameModal = $('#js-confirm-rename');
    var $deleteModal = $('#js-confirm-delete');

    var callback = function (key, opt) {
            switch (key) {
                case 'edit':
                    var $renameModalButton = opt.$trigger.find(".js-rename-modal")
                    renameFile($renameModalButton)
                    $renameModal.modal("show");
                    break;
                case 'delete':
                    var $deleteModalButton = opt.$trigger.find(".js-delete-modal")
                    deleteFile($deleteModalButton)
                    $deleteModal.modal("show");
                    break;
                case 'download':
                    var $downloadButton = opt.$trigger.find(".js-download")
                    downloadFile($downloadButton)
                    break;
            }
        }
    ;


    $.contextMenu({
        selector: '.file',
        callback: callback,
        items: {
            "delete": {name: "{{ 'title.delete'|trans }}", icon: "fa-trash"},
            "edit": {name: "{{ 'title.rename.file'|trans }}", icon: "fa-edit"},
            "download": {name: "{{ 'title.download'|trans }}", icon: "fa-download"},
        }
    });
    $.contextMenu({
        selector: '.dir',
        callback: callback,
        items: {
            "delete": {name: "{{ 'title.delete'|trans }}", icon: "fa-trash"},
            "edit": {name: "{{ 'title.rename.file'|trans }}", icon: "fa-edit"},
        }
    });

    function renameFile($renameModalButton) {
        $('#form_name').val($renameModalButton.data('name'));
        $('#form_extension').val($renameModalButton.data('extension'));
        $renameModal.find('form').attr('action', $renameModalButton.data('href'))
    }

    function deleteFile($deleteModalButton) {
        $('#js-confirm-delete').find('form').attr('action', $deleteModalButton.data('href'));
    }

    function downloadFile($downloadButton) {
        $downloadButton[0].click();
    }

    if (tree === true) {

        // sticky kit
        $("#tree-block").stick_in_parent();

        // event list : https://www.jstree.com/api/#/?q=.jstree%20Event

        $('#tree').jstree({
//            "types": {
//                "default": {
//                    "icon": "fa fa-folder"
//                }
//            },
            'core': {
                'data': treedata,
                "check_callback": true
            },
//            "plugins": ["contextmenu", "types"]
        }).bind("select_node.jstree", function (e, data) {
            console.log(data)
//            if(data.node) {
//                document.location = data.node.a_attr.href;
//            }
        }).bind("changed.jstree", function (e, data) {
            if (data.node) {
                document.location = data.node.a_attr.href;
            }
        });

    }

    $(document)
    // checkbox select all
        .on('click', '#select-all', function () {
            var checkboxes = $('#form-multiple-delete').find(':checkbox')
            if ($(this).is(':checked')) {
                checkboxes.prop('checked', true);
            } else {
                checkboxes.prop('checked', false);
            }
        })
        // delete modal buttons
        .on('click', '.js-delete-modal', function () {
                deleteFile($(this));
            }
        )
        // rename modal buttons
        .on('click', '.js-rename-modal', function () {
                renameFile($(this));
            }
        )
        // multiple delete modal button
        .on('click', '#js-delete-multiple-modal', function () {
            var $multipleDelete = $('#form-multiple-delete').serialize();
            if ($multipleDelete) {
                var href = '{{ path('
                file_manager_delete
                ', fileManager.queryParameters )|e('
                js
                ') }}' + '&' + $multipleDelete;
                $('#js-confirm-delete').find('form').attr('action', href);
            }
        })
        // checkbox
        .on('click', '#form-multiple-delete :checkbox', function () {
            var $jsDeleteMultipleModal = $('#js-delete-multiple-modal');
            if ($(".checkbox").is(':checked')) {
                $jsDeleteMultipleModal.removeClass('disabled');
            } else {
                $jsDeleteMultipleModal.addClass('disabled');
            }
        });

    // preselected
    $renameModal.on('shown.bs.modal', function () {
        $('#form_name').select().mouseup(function () {
            $('#form_name').unbind("mouseup");
            return false;
        });
    });
    $('#addFolder').on('shown.bs.modal', function () {
        $('#rename_name').select().mouseup(function () {
            $('#rename_name').unbind("mouseup");
            return false;
        });
    });


    // Module Tiny
    if (module === 'tiny') {

        $('#form-multiple-delete').on('click', '.select', function () {
            var args = top.tinymce.activeEditor.windowManager.getParams();
            var input = args.input;
            var document = args.window.document;
            var divInputSplit = document.getElementById(input).parentNode.id.split("_");

            // set url
            document.getElementById(input).value = $(this).attr("data-path");

            // set width and height
            var baseId = divInputSplit[0] + '_';
            var baseInt = parseInt(divInputSplit[1], 10);

            divWidth = baseId + (baseInt + 3);
            divHeight = baseId + (baseInt + 5);

            document.getElementById(divWidth).value = $(this).attr("data-width");
            document.getElementById(divHeight).value = $(this).attr("data-height");

            top.tinymce.activeEditor.windowManager.close();
        });
    }

    // Global functions

    // display error alert
    function displayError(msg) {
        displayAlert('danger', msg)
    }

    // display success alert
    function displaySuccess(msg) {
        displayAlert('success', msg)
    }

    // display alert
    function displayAlert(type, msg) {
        $.notify({
            message: msg
        }, {
            type: type,
            placement: {
                from: "bottom",
                align: "left"
            },
            template: '<div data-notify="container" class="col-xs-5 col-md-4 col-lg-3 alert alert-{0}" role="alert">' +
            '<button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>' +
            '<span data-notify="icon"></span> ' +
            '<span data-notify="title">{1}</span> ' +
            '<span data-notify="message">{2}</span>' +
            '<div class="progress" data-notify="progressbar">' +
            '<div class="progress-bar progress-bar-{0}" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>' +
            '</div>' +
            '<a href="{3}" target="{4}" data-notify="url"></a>' +
            '</div>'
        });
    }

    // file upload
    $('#fileupload').fileupload({
        dataType: 'json',
        processQueue: false,
        dropZone: $('#dropzone')
    }).on('fileuploaddone', function (e, data) {
        $.each(data.result.files, function (index, file) {
            if (file.url) {
                displaySuccess('<strong>' + file.name + '</strong> ' + successMessage)
                // Ajax update view
                $.ajax({
                    dataType: "json",
                    url: url,
                    type: 'GET'
                }).done(function (data) {

                    // update file list
                    $('#form-multiple-delete').html(data.data);

                    // update treeview
                    $('#tree').treeview({
                        data: data.treeData,
                        enableLinks: true,
                        showTags: true
                    }).on('nodeSelected', function (event, data) {
                        document.location.href = data.href
                    });

                    $('#select-all').prop('checked', false);
                    $('#js-delete-multiple-modal').addClass('disabled');

                }).fail(function (jqXHR, textStatus, errorThrown) {
                    displayError('<strong>Ajax call error :</strong> ' + jqXHR.status + ' ' + errorThrown)
                });

            } else if (file.error) {
                displayError('<strong>' + file.name + '</strong> ' + file.error)
            }
        });
    }).on('fileuploadfail', function (e, data) {
        $.each(data.files, function (index) {
            displayError('File upload failed.')
        });
    });
})
;