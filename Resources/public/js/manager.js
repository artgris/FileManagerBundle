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
    };

    $.contextMenu({
        selector: '.file',
        callback: callback,
        items: {
            "delete": {name: deleteMessage, icon: "fa-trash"},
            "edit": {name: renameMessage, icon: "fa-edit"},
            "download": {name: downloadMessage, icon: "fa-download"},
        }
    });
    $.contextMenu({
        selector: '.dir',
        callback: callback,
        items: {
            "delete": {name: deleteMessage, icon: "fa-trash"},
            "edit": {name: renameMessage, icon: "fa-edit"},
        }
    });


    function ajaxMoveFile(fileName, newPath){
        $.ajax({
            data: {
                json: 'true',
                conf: 'basic_user',
                fileName: fileName,
                newPath: newPath
            },
            url: urlmove,
            success: function(data){
                window.location.reload();
            },
            error: function (a, b, c) {
            }
        });
    }


    function moveFile(destFolder, file){

        let newPath = '';
        let dataHref = destFolder.attr('href').split('&');
        dataHref.forEach(function (queryFragment, index) {
            let queryNVP = queryFragment.split('=');
            if (queryNVP[0] === 'route') {
                newPath = queryNVP[1];
            }
        });
        if(newPath == '') {
            newPath = '/';
        }
        let fileName = file.attr('data-name');
        ajaxMoveFile(fileName, newPath);
    }


    function setDroppables() {
        $('.dir').droppable({
            tolerance: "touch",
            over: function(event, ui) {
                ui.helper.find('.jstree-icon').removeClass('jstree-er').addClass('jstree-ok');
            },
            out: function(event, ui){
                ui.helper.find('.jstree-icon').removeClass('jstree-ok').addClass('jstree-er');
            },
            drop: function (event, ui) {
                let file = $(event.target).find('p > a');
                moveFile(file, $(ui.draggable))
            }
        });
    }

    function getHelper($element) {
        let item = $("<div>", {
            id: "jstree-dnd",
            class: "jstree-default"
        });
        $("<i>", {
            class: "jstree-icon jstree-er"
        }).appendTo(item);
        item.append($element.text());
        return item;
    }

    function setDraggables() {

        let draggableOpts = {
            revert: "invalid",
            iframeFix: true,
            cursor: "crosshair",
            cursorAt: { top: 5, left: 5 },
            opacity: 0.7,
            helper:"clone",
            start: function (event, ui){
                if($('#tree').length){
                    let item = getHelper($(this));
                    return $.vakata.dnd.start(
                        event,
                        {
                            jstree: true,
                            obj: ui.helper,
                            nodes: [{
                                id: true,
                                text: $(this).text(),
                                icon: "fa fa-flag-o"
                            }]
                        },
                        item
                    );
                }
            }
        };
        if($('#tree').length){
            draggableOpts.helper = "clone";
        } else {
            draggableOpts.helper = function() {
                return getHelper($(this));
            }
        }
        $('.file').draggable(draggableOpts);
    }

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

    function initTree(treedata) {
        $('#tree').jstree({
            'plugins':["dnd"],
            'core': {
                'data': treedata,
                "check_callback": function (operation, node, node_parent, node_position, more) {
                    if (more) {
                        if (more.dnd) {
                            return false;
                        }
                    }
                    return true;
                }
            }
        }).bind("changed.jstree", function (e, data) {
            if (data.node) {
                document.location = data.node.a_attr.href;
            }
            setDraggables();
        });
        $(document).on('dnd_stop.vakata', function(ev, data) {
            ev.stopPropagation();
            ev.preventDefault();

            let target = $(data.event.target);
            let file = $(data.element);

            if(target.closest('.jstree-hovered:not(.jstree-clicked)').length) {
                moveFile(target, file);
            }
            return false;
        }).on('dnd_move.vakata', function(e, data) {
            let target = $(data.event.target);
            if(target.closest('.jstree-hovered:not(.jstree-clicked)').length) {
                data.helper.find('.jstree-icon').removeClass('jstree-er').addClass('jstree-ok');
            } else {
                data.helper.find('.jstree-icon').removeClass('jstree-ok').addClass('jstree-er');
            }
        });
    }

    if (tree === true) {

        // sticky kit
        $("#tree-block").stick_in_parent();

        initTree(treedata);

    } else {

        setDraggables();
        setDroppables();
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
                var href = urldelete + '&' + $multipleDelete;
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
        }).on('click', 'button.js-move-modal', function(){
            let target = $(this);
            let fileName = target.attr('data-name')+'.'+target.attr('data-extension');
            let currentPath = '';
            let dataHref = target.attr('data-href').split('&');
            dataHref.forEach(function (queryFragment, index) {
                let queryNVP = queryFragment.split('=');
                if (queryNVP[0] === 'route') {
                    currentPath = queryNVP[1];
                }
            });
            $.ajax({
                data: {
                    json: 'true',
                    conf: 'basic_user',
                    filename: fileName,
                    currentPath: currentPath == '' ? '/': currentPath,
                },
                url: urlfolderlist,
                success: function (data, textStatus, jqXHR) {
                    let modal = $('#moveFile .modal-body');
                    modal.html(data.view);
                },
                error: function (a, b, c) {
                }
            });
        }).on('click', 'li.ypsa-medialibrary-move span', function(){
            let target = $(this);
            let newPath = target.attr('data-path');
            let parent = target.parents('div#ypsa-move-select');
            let fileName = parent.attr('data-file');

            $('#moveFile .modal-body').html('');
            ajaxMoveFile(fileName, newPath);
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
    if (moduleName === 'tiny') {

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
                    if (tree === true) {
                        $('#tree').data('jstree', false).empty();
                        initTree(data.treeData);
                    }

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
        $.each(data.files, function (index, file) {
            displayError('File upload failed.')
        });
    });
})
;