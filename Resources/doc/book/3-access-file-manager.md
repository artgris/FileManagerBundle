Chapter 3 - Access to the File Manager
======================================

```yaml
# app/config/config.yml
artgris_file_manager:
    conf:
        public:                     # Access URL: /manager/?conf=public
            dir: "../web/uploads"
            ...
        myprivatefolder: ...        # Access URL: /manager/?conf=myprivatefolder
        onlypdf: ...                # Access URL: /manager/?conf=onlypdf
        anystring: ...              # Access URL: /manager/?conf=anystring
```

Here is a list of URL parameters:

| Param    | Type     | Required  | Possible values          | Default value | Description       | Priority (yml / url) |
| :------- |:--------:|:---------:|:------------------------:|:-------------:|:------------------|:------------------:|
| `conf`   | `String` |  **True** |                          |               | name of the conf |
| `type`   | `String` |  False    | `file`, `image`, `media` | `file`        | type (used by tinymce) | yml > url
| `module` | `String` |  False    | `tiny`, `ckeditor`                  |  `null`       | module (used by tinymce) | 
| `tree` in url   | `Interger` |  False    | `0`, `1` | `1`       | Display Folder Tree (1:Yes, 2:No) | url > yml
| `tree` in yml   | `Booleen` |  False    | `false`, `true` | `true`       | Display Folder Tree (1:Yes, 2:No) | url > yml
| `view` | `String` |  False    | `thumbnail`, `list`     |  `list`       | Display Mode Type | url > yml
| `orderby` | `String` |  False    | `name`, `date`, `size`, `dimension`     |         | Sort files |
| `order` | `String` |  False    | `asc`, `desc`     |         | Order by asc or desc | 
| `extra` | `Array` |  False    |                    |  `null`       | extra parameters (used by service configuration)
| `route` | `String` |  False    |                    |         | a folder path under the 'dir' folder ex: /subfolder

Example:

    path('file_manager', {module:'tiny', type:'image', conf:'perso', extra: {'user':'miamolex', 'allow': true}, route: '/subfolder'})
    
    # Access URL: /manager/?module=tiny&type=image&conf=perso&extra[user]=miamolex&extra[allow]=1&route=/subfolder
