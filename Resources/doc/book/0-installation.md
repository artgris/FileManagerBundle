Chapter 0. Installation and your first File Manager
===================================================

FileManager Project is a simple Multilingual File Manager Bundle for symfony

Installation
------------

### Step 1: Download the Bundle

```bash
$ composer require artgris/filemanager-bundle
```

### Step 2: Load the Routes


```yaml
# app/config/routing.yml
artgris_bundle_file_manager:
    resource: "@ArtgrisFileManagerBundle/Controller"
    type:     annotation
    prefix:   /manager
```

### Step 3:  Enable the translator service 

```yml
# app/config/config.yml
framework:
    translator: { fallbacks: [ "en" ] }
```    
    
Creating Your First File Manager
---------------------------------

Create a folder **uploads** in **public**.
 
#### Add following minimal configuration :

```yaml
# app/config/config.yml
artgris_file_manager:
    conf:
        default:
            dir: "%kernel.project_dir%/public/uploads"
```

Browse the `/manager/?conf=default` URL and you'll get access to your file manager
    
    
<img src="https://raw.githubusercontent.com/artgris/FileManagerBundle/master/Resources/doc/images/filemanager-promo.png" alt="Symfony Filemanager created with FileManagerBundle" />


-------------------------------------------------------------------------------

[Chapter 1. Basic Configuration](1-basic-configuration.md) &rarr;
