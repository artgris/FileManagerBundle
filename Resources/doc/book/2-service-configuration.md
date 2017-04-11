Chapter 2 - Service Configuration
=================================

If basic configuration is not sufficient for you, and you need more control you can used the service conf


for this conf you just need on option :


#### `service` The service that will return configuration 
| Option | Type     | Required |
| :---  |:--------:|:--------:|
| `service`  | `String` |  True   |


Example:
```yml  
artgris_file_manager:
    conf:
        perso:
            service: "custom_service"            
```


This service need to implement CustomConfService

```php 
<?php

namespace AppBundle\Service;

use Artgris\Bundle\FileManagerBundle\Service\CustomConfService;

class CustomService implements CustomConfService
{
   public function getConf() {
   
    ... 
   
   }
}
```

`getconf()` must return an Array of the configuration :

```php 
   public function getConf() {
   
     return [
     'dir' => '../web/public'
     ... 
     ];
   
   }
```   

Do not forget to configure your services.yml

```yml 
services:
    custom_service:
      class: AppBundle\Service\CustomService
```    
   
>Browse the `/manager/?conf=perso` URL to get access to this File Manager

With service configuration, you can for example defined a folder for each user login

```php 
<?php


namespace AppBundle\Service;


use Artgris\Bundle\FileManagerBundle\Service\CustomConfService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class CustomService implements CustomConfService
{
    /**
     * @var TokenStorage
     */
    private $tokenStorage;


    /**
     * CustomService constructor.
     * @param TokenStorage $tokenStorage
     */
    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }


    public function getConf()
    {
        $folder = 'user/' . $this->tokenStorage->getToken()->getUser();
        $fs = new Filesystem();
        if (!$fs->exists($folder)) {
            $fs->mkdir($folder);
        }
        return ['dir' => $folder];

    }

}
```

with 

```yml 
custom_service:
      class: AppBundle\Service\CustomService
      arguments: ['@security.token_storage']
```
  
 
## `upload` Exhaustive options (file upload widget)

in the return Array you can include all option of the file upload widget :

[Exhaustive options](https://github.com/blueimp/jQuery-File-Upload/blob/master/server/php/UploadHandler.php)

Example 

```php 
<?php

namespace AppBundle\Service;

use Artgris\Bundle\FileManagerBundle\Service\CustomConfService;

class CustomService implements CustomConfService
{
      public function getConf()
      {
          return [
              'dir' => '../web/perso',
              'upload' => [
                  'image_versions' => [
                      'medium' => [
                          'auto_orient' => true,
                          'max_width' => 10
                      ]
                  ],
              ]
          ];
      }
}
```

-------------------------------------------------------------------------------

[Chapter 3 - Access to the File Manager](3-access-file-manager.md) &rarr;