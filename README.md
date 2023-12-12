FileManagerBundle
=================

[![Symfony 2.x, 3.x, 4.x, 5.x, 6.x, 7.x][7]][8]

FileManager is a simple Multilingual File Manager Bundle for Symfony

<img src="https://raw.githubusercontent.com/artgris/FileManagerBundle/master/Resources/doc/images/filemanager-promo.png" alt="Symfony Filemanager created with FileManagerBundle" align="center" />

* [Documentation](#documentation)
* [Installation](#installation)
* [Creating Your First File Manager](#creating-your-first-file-manager)


**Features**
*  Upload, delete (multiple), rename, download and sort files
*  Create, rename and delete folders
*  Manage **Public** and **Private** folders
*  File Names **Sanitizer** / **Slugger** ([Look Documentation](Resources/doc/book/1-basic-configuration.md))
*  **Multilingual** (English, French, Catalan, German, Spanish, Dutch, Portuguese, Romanian, Russian, Turkish)
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
    * [Exhaustive options](https://github.com/blueimp/jQuery-File-Upload/blob/master/server/php/UploadHandler.php)
* Compatible with [**FOSCKEditorBundle**](https://github.com/FriendsOfSymfony/FOSCKEditorBundle)

Documentation
-------------

#### The Book

  * [Chapter 0 - Installation and your first File Manager](Resources/doc/book/0-installation.md)
  * [Chapter 1 - Basic Configuration](Resources/doc/book/1-basic-configuration.md)
  * [Chapter 2 - Service Configuration](Resources/doc/book/2-service-configuration.md)
  * [Chapter 3 - Access to the File Manager](Resources/doc/book/3-access-file-manager.md)
  * [Chapter 4 - Security | Hide and/or block access to specific files or folders](Resources/doc/book/4-security.md)
  
#### Tutorials

  * [How to integrate FileManagerBundle into Tinymce](Resources/doc/tutorials/integrate-tinymce.md)
  * [How to integrate FileManagerBundle into FOSCKEditorBundle](Resources/doc/tutorials/integrate-fos-ckeditor.md)
  * [How to add a button that open the File manager to fill out an input field with the file URL](Resources/doc/tutorials/input-button.md)
  

Installation
------------

### Step 1: Download the Bundle

```bash
$ composer require artgris/filemanager-bundle
```

### Step 2: Load the Routes


```yaml
# app/config/routes.yaml
artgris_bundle_file_manager:
    resource: "@ArtgrisFileManagerBundle/Controller"
    type:     attribute
    prefix:   /manager
```
### Step 3:  Enable the translator service

```yml
# app/config/packages/translation.yaml
framework:
    translator: { fallbacks: [ "en" ] }
```    
    
Creating Your First File Manager
---------------------------------

Create a folder **uploads** in **public**.
 
#### Add following configuration:

```yaml
# app/config/packages/artgris_file_manager.yaml
artgris_file_manager:
    conf:
        default:
            dir: '%kernel.project_dir%/public/uploads'
```

Browse the `/manager/?conf=default` URL and you'll get access to your 
file manager
 
[7]: https://img.shields.io/badge/symfony-2.x%2C%203.x%2C%204.x%2C%205.x,%206.x%20and%207.x-green.svg
[8]: https://symfony.com/


#### Run tests:

    ./vendor/bin/simple-phpunit
    
#### Demo Application

[FileManagerDemo](https://github.com/artgris/FileManagerBundleDemo) is a complete Symfony application (Symfony 4.4 and 5.0) created to showcase FileManagerBundle features.
