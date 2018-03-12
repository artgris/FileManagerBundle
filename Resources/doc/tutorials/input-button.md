How to add a button that open the File manager to fill out an input field with the file URL 
==========================================================================================


> Required: bootstrap and jquery

### Step 1 - Create a `button` conf

```yml
artgris_file_manager:
    conf:
        button:
            dir: "../web/uploads"
```

### Step 2 - Create input and button

```html  
<form>
    <div class="form-group">
        <div class="col-sm-10">
            <input class="form-control" id="path" type="text">
        </div>
        <div class="col-sm-2">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#myModal">
                <span class="glyphicon glyphicon-folder-open"></span>
            </button>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-6 col-md-3">
            <img src="" class="img-responsive" id="image" alt="">
        </div>
    </div>
</form>
```

### Step 3 - Add modal with an iframe of your file manager; use `module:1`

```html 
 <!-- Modal -->
    <div class="modal  fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">File Manager</h4>
                </div>
                <div class="modal-body">
                    <iframe id="myframe" src="{{ path('file_manager', {module:1, conf:'button'}) }}" width="100%" height="500"
                            frameborder="0"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
```

### Step 4 - Add following js

```js 
    <script>
        $('#myframe').on('load', function () {
            $(this).contents().on('click','.select',function () {
                var path = $(this).attr('data-path')
                $('#path').val(path);
                $('#image').attr('src', path)
                $('#myModal').modal('hide')
            });
        });
    </script>
```
