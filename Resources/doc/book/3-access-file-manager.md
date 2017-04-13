Chapter 3 - Access to the File Manager
======================================


After you have defined the configuration, browse the `/manager/?conf=public` URL and you'll get access to the 
file manager defined 

Here is a list of URL parameters :

| Param    | Type     | Required  | Possible values          | Default value | Description       | Priority (yml / url) |
| :------- |:--------:|:---------:|:------------------------:|:-------------:|:------------------|:------------------:|
| `conf`   | `String` |  **True** |                          |               | name of the conf |
| `type`   | `String` |  False    | `file`, `image`, `media` | `file`        | type (used by tinymce) | yml > url
| `module` | `String` |  False    | `tiny`                   |  `null`       | module (used by tinymce) | 
| `tree`   | `Interger` |  False    | `0`, `1` | `1`       | Display Folder Tree (1:Yes, 2:No) | url > yml
| `view` | `String` |  False    | `thumbnail`, `list`     |  `list`       | Display Mode Type | url > yml
| `extra` | `Array` |  False    |                    |  `null`       | extra parameters (used by service configuration)






Exemple:

    path('file_manager', {module:'tiny', type:'image', conf:'perso', extra: {'user':'miamolex', 'allow': true}})
    
    # /manager/?module=tiny&type=image&conf=perso&extra[user]=miamolex&extra[allow]=1
