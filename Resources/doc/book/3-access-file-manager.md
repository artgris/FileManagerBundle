Chapter 3 - Access to the File Manager
======================================


After you have defined the configuration, browse the `/manager/?conf=public` URL and you'll get access to the 
file manager defined 

Here is a list of URL parameters :

| Param    | Type     | Required  | Possible values          | Default value | Description       |
| :------- |:--------:|:---------:|:------------------------:|:-------------:|:------------------|
| `conf`   | `String` |  **True** |                          |               | name of the conf
| `type`   | `String` |  False    | `file`, `image`, `media` | `file`        | type (used by tinymce)
| `module` | `String` |  False    | `tiny`                   |  `null`       | module (used by tinymce)


Exemple:

    {{ path('file_manager', {conf:'tiny', module:'tiny'}) }} 
    
    # /manager/?conf=tiny&module=tiny
