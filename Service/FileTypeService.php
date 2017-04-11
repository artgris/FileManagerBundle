<?php


namespace Artgris\Bundle\FileManagerBundle\Service;

use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Asset\Packages;

class FileTypeService
{
    /**
     * @var Router
     */
    private $router;
    /**
     * @var Packages
     */
    private $packages;


    /**
     * FileTypeService constructor.
     * @param Router $router
     * @param Packages $packages
     */
    public function __construct(Router $router, Packages $packages)
    {
        $this->router = $router;
        $this->packages = $packages;
    }

    public function preview(FileManager $fileManager, SplFileInfo $file)
    {
        if ($fileManager->getImagePath()) {
            $filePath = htmlentities($fileManager->getImagePath() . rawurlencode($file->getFilename()));
        } else {
            $filePath = $this->router->generate('file_manager_file', array_merge($fileManager->getQueryParameters(), ['fileName' => rawurlencode($file->getFilename())]));
        }
        $extension = $file->getExtension();
        switch (true) {
            case preg_match('/(gif|png|jpe?g|svg)$/i', $extension):
                return [
                    "path" => $filePath,
                    "html" => "<img class=\"img-rounded\" src=\"{$filePath}\" height='22px' width='22px'>"
                ];
            case preg_match('/(mp4|ogg|webm)$/i', $extension):
                $fa = 'fa-file-video-o';
                break;
            case preg_match('/(pdf)$/i', $extension):
                $fa = 'fa-file-pdf-o';
                break;
            default :
                $fa = 'fa-file';
        }

        return [
            "path" => $filePath,
            "html" => "<i class='fa {$fa}' aria-hidden='true'></i>"
        ];
    }

    public function accept($type)
    {
        switch ($type) {
            case "image":
                $accept = "image/*";
                break;
            case "media":
                $accept = "video/*";
                break;
            case "file":
                return false;
            default :
                return false;
        }

        return $accept;
    }

}