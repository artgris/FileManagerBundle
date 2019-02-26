How to integrate FileManagerBundle into TinyMCE
===============================================

Tinymce has [`file_browser_callback`](https://www.tinymce.com/docs/configure/file-image-upload/) option who enables you to add your own file or image browser to TinyMCE.


### Step 1 - Create a `tiny` conf

```yml  
artgris_file_manager:
    conf:
        tiny:
            dir: "../web/uploads"
```

### Step 2 - Add TinyMCE textarea
```html
<textarea name="" cols="30" rows="10" class="tinymce"></textarea>
```  

### Step 3 - Init TinyMCE with `file_browser_callback: myFileBrowser,` option :

TinyMCE v4  
```javascript  
    <script src="{{ asset('tinymce/js/tinymce4/tinymce.min.js') }}"></script>
    <script>

        function myFileBrowser(field_name, url, type, win) {

            var cmsURL = "{{ path('file_manager', {module:'tiny', conf:'tiny'}) }}";
            if (cmsURL.indexOf("?") < 0) {
                cmsURL = cmsURL + "?type=" + type;
            }
            else {
                cmsURL = cmsURL + "&type=" + type;
            }

            tinyMCE.activeEditor.windowManager.open({
                file: cmsURL,
                title: 'File Manager',
                width: 1024,
                height: 500
            }, {
                window: win,
                input: field_name
            });

            return false;
        }

        tinymce.init({
            selector: '.tinymce',
            file_browser_callback: myFileBrowser,
            height: 300,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor textcolor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table contextmenu paste code help wordcount'
            ],
            toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        });

    </script>
```
TinyMCE v5  
```javascript 
    <script src="{{ asset('tinymce/js/tinymce5/tinymce.min.js') }}"></script>
    <script>
        function myFileBrowser(callback, value, meta) {

            var type = meta.filetype;
            var cmsURL = "{{ path('file_manager', {module:'tiny', conf:'tiny'}) }}";
            if (cmsURL.indexOf("?") < 0) {
                cmsURL = cmsURL + "?type=" + type;
            }
            else {
                cmsURL = cmsURL + "&type=" + type;
            }

            var windowManagerCSS = '<style type="text/css">' +
                '.tox-dialog {max-width: 100%!important; width:97.5%!important; overflow: hidden; height:95%!important; border-radius:0.25em;}' +
                '.tox-dialog__body { padding: 0!important; }' +
                '.tox-dialog__body-content > div { height: 100%; overflow:hidden}' +
                '</style > ';

            window.tinymceCallBackURL = '';
            window.tinymceWindowManager = tinymce.activeEditor.windowManager;
            tinymceWindowManager.open({
                title: 'File Manager',
                body: {
                    type: 'panel',
                    items: [{
                        type: 'htmlpanel',
                        html: windowManagerCSS + '<iframe src="' + cmsURL + '"  frameborder="0" style="width:100%; height:100%"></iframe>'
                    }]
                },
                buttons: [],
                onClose: function () {
                    if (tinymceCallBackURL != '')
                        callback(tinymceCallBackURL, {}); //to set selected file path
                }
            });
        }

        tinymce.init({
            selector: '.tinymce',
            height: 300,
            plugins: [
                'advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker',
                'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking',
                'save table directionality emoticons template paste'
            ],
            toolbar: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | print preview media fullpage | forecolor backcolor emoticons',
            file_picker_callback: myFileBrowser,
        });

    </script>
```