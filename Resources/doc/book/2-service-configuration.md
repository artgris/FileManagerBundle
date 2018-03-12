Chapter 2 - Service Configuration
=================================

If basic configuration is not enough for you and you need more control, then use the following service configuration:

you only need one option:


## `service` The service that will return configuration
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


This service need to implement `CustomConfServiceInterface`

```php 
<?php

namespace AppBundle\Service;

use Artgris\Bundle\FileManagerBundle\Service\CustomConfServiceInterface;

class CustomService implements CustomConfServiceInterface
{
   public function getConf($extra = []) {
   
    ... 
   
   }
}
```

`getconf($extra)` must return an Array of the configuration:

```php 
   public function getConf($extra = []) {
   
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

#### Extra URL parameters injections

You can inject `extra` parameters in your service via URL:

Example:

    path('file_manager', {module:'tiny', type:'image', conf:'perso', extra: {'user':'miamolex', 'allow': true}})


Here, I add 2 extra parameters, which I recover in my Service:

```php
public function getConf($extra = []) {     

 $user = $extra['user'] # miamolex
 $allow = $extra['allow'] # true
 
 ...
```    
With this `service` configuration, you can define (for example) a folder for each user

```php 
<?php


namespace AppBundle\Service;


use Artgris\Bundle\FileManagerBundle\Service\CustomConfServiceInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class CustomService implements CustomConfServiceInterface
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


    public function getConf($extra = [])
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

You can include all the options of `jQuery File Upload` in `return` (to make it easier than .yml):

[Exhaustive options](https://github.com/blueimp/jQuery-File-Upload/blob/master/server/php/UploadHandler.php)

Example 

```php 
<?php

namespace AppBundle\Service;

use Artgris\Bundle\FileManagerBundle\Service\CustomConfServiceInterface;

class CustomService implements CustomConfServiceInterface
{
      public function getConf($extra = [])
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
