How to integrate FileManagerBundle into FOSCKEditorBundle
==========================================================

[FOSCKEditorBundle Installation](https://symfony.com/doc/master/bundles/FOSCKEditorBundle/installation.html)

### Step 1 - Create your artgris_file_manager conf:

```yml  
artgris_file_manager:
    conf:
        files:
          dir: "../public/uploads"
          type: 'file'
        images:
          dir: "../public/uploads"
          type: 'image'
```

### Step 2 - Add fos_ck_editor conf:
```yaml
fos_ck_editor:
    default_config: basic_config
    configs:
        basic_config:
            toolbar: full
            filebrowserBrowseRoute: file_manager
            filebrowserBrowseRouteParameters:
                conf: files
                module: ckeditor
            filebrowserImageBrowseRoute: file_manager
            filebrowserImageBrowseRouteParameters:
                conf: images
                module: ckeditor
```  

### Step 3 - Add a new field

```php
    use FOS\CKEditorBundle\Form\Type\CKEditorType;

    $builder->add('ckeditor', CKEditorType::class, array(
        'config_name' => 'basic_config',
    ));
```  