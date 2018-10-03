FileManagerBundle
=================

[![Tests][1]][2] [![Code Quality][3]][4] [![Code Coverage][5]][6] [![Symfony 2.x, 3.x and 4.x][7]][8]

FileManager is a simple Multilingual File Manager Bundle for Symfony

<img src="https://raw.githubusercontent.com/artgris/FileManagerBundle/master/Resources/doc/images/filemanager-promo.png" alt="Symfony Filemanager created with FileManagerBundle" align="center" />

* [Documentation](#documentation)
* [Installation](#installation)
* [Creating Your First File Manager](#creating-your-first-file-manager)


**Features**
*  Upload, delete (multiple), rename, download and sort files
*  Create, rename and delete folders
*  Manage **Public** and **Private** folders
*  **Multilingual** (English, French)
*  **Fully responsive design** (bootstrap)
*  Multilple view modes (list, thumbnail, with tree or not)
*  Easy integration with [**Tinymce**](https://www.tinymce.com/)
*  **Preview images** (even with a Private folder)
*  Create **multilple configurations**
*  **Advanced configuration** (ex : ACL, ...) with your own **service**
*  **File restriction** based on patterns
*  File Upload widget used : [blueimp/jQuery-File-Upload](https://github.com/blueimp/jQuery-File-Upload)
    * **Multiple uploads support**
    * **Drag & Drop support**
    * **Min/Max file size restriction**
    * **Thumbnails generation**
    * **Client-side image resizing/crop**
    * [Exhaustive options](https://github.com/blueimp/jQuery-File-Upload/blob/master/server/php/UploadHandler.php)


Documentation
-------------

#### The Book

  * [Chapter 0 - Installation and your first File Manager](Resources/doc/book/0-installation.md)
  * [Chapter 1 - Basic Configuration](Resources/doc/book/1-basic-configuration.md)
  * [Chapter 2 - Service Configuration](Resources/doc/book/2-service-configuration.md)
  * [Chapter 3 - Access to the File Manager](Resources/doc/book/3-access-file-manager.md)
  * [Chapter 4 - Security](Resources/doc/book/4-security.md)
  
#### Tutorials

  * [How to integrate FileManagerBundle into Tinymce](Resources/doc/tutorials/integrate-tinymce.md)
  * [How to add a button that open the File manager to fill out an input field with the file URL](Resources/doc/tutorials/input-button.md)
  

Installation
------------

### Step 1: Download the Bundle

```bash
$ composer require artgris/filemanager-bundle
```

### Step 2: Enable the Bundle

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Artgris\Bundle\FileManagerBundle\ArtgrisFileManagerBundle(),
        );
    }

    // ...
}
```
### Step 3: Load the Routes


```yaml
# app/config/routing.yml
artgris_bundle_file_manager:
    resource: "@ArtgrisFileManagerBundle/Controller"
    type:     annotation
    prefix:   /manager
```

### Step 4: Prepare the Web Assets

```cli
# Symfony 3
php bin/console assets:install --symlink
```

### Step 5:  Enable the translator service 

```yml
# app/config/config.yml
framework:
    translator: { fallbacks: [ "en" ] }
```    
    
Creating Your First File Manager
---------------------------------

Create a folder **uploads** in **web**.
 
#### Add following configuration (symfony4) :

```yaml
# app/config/config.yml
artgris_file_manager:
    web_dir: public                 # set your public Directory (not required, default value: web)
    conf:
        default:
            dir: "../public/uploads"
```

Browse the `/manager/?conf=default` URL and you'll get access to your 
file manager
 
[1]: https://travis-ci.org/artgris/FileManagerBundle.svg?branch=master
[2]: https://travis-ci.org/artgris/FileManagerBundle
[3]: https://insight.sensiolabs.com/projects/701afcd5-edde-421a-ab6c-0188bfa7e7dc/mini.png
[4]: https://insight.sensiolabs.com/projects/701afcd5-edde-421a-ab6c-0188bfa7e7dc
[5]: https://coveralls.io/repos/artgris/FileManagerBundle/badge.svg?branch=master
[6]: https://coveralls.io/r/artgris/FileManagerBundle?branch=master
[7]: https://img.shields.io/badge/symfony-2.x%2C%203.x%20and%204.x-green.svg
[8]: https://symfony.com/
