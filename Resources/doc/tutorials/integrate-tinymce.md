How to integrate FileManagerBundle into TinyMCE (4.5.6)
=======================================================

Tinymce has [`file_browser_callback`](https://www.tinymce.com/docs/configure/file-image-upload/) option who enables you to add your own file or image browser to TinyMCE.


### Step 1 - Create a `tiny` conf

```yml  
artgris_file_manager:
    conf:
        tiny:
            dir: "../web/uploads"
```

### Step 3 - Add TinyMCE textarea
```html
<textarea name="" cols="30" rows="10" id="mytextarea"></textarea>
```  

### Step 4 - Init TinyMCE with `file_browser_callback: myFileBrowser,` option :

```javascript  
    <script type="text/javascript">
        tinymce.init({
            selector: '#mytextarea',
            file_browser_callback: myFileBrowser,
            theme: 'modern',
            height: 300,
            plugins: [
                'advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker',
                'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking',
                'save table contextmenu directionality emoticons template paste textcolor'
            ],
            toolbar: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | print preview media fullpage | forecolor backcolor emoticons'
        });
        ...
```    
  
### Step 5 - Add `myFileBrowser()` function with the right URL:
  
```javascript     
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
    
```
