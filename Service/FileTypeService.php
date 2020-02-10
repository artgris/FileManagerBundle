<?php

namespace Artgris\Bundle\FileManagerBundle\Service;

use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use SplFileInfo;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;

class FileTypeService
{
    const IMAGE_SIZE = [
        FileManager::VIEW_LIST => '22',
        FileManager::VIEW_THUMBNAIL => '100',
    ];

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * FileTypeService constructor.
     *
     * @param RouterInterface $router
     * @param Packages        $packages
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function preview(FileManager $fileManager, SplFileInfo $file)
    {
        if ($fileManager->getImagePath()) {
            $filePath = htmlentities($fileManager->getImagePath().rawurlencode($file->getFilename()));
        } else {
            $filePath = $this->router->generate('file_manager_file',
                array_merge($fileManager->getQueryParameters(), ['fileName' => rawurlencode($file->getFilename())]));
        }
        $extension = $file->getExtension();
        $type = $file->getType();
        if ('file' === $type) {
            $size = $this::IMAGE_SIZE[$fileManager->getView()];

            return $this->fileIcon($filePath, $extension, $size, true);
        }
        if ('dir' === $type) {
            $href = $this->router->generate('file_manager', array_merge($fileManager->getQueryParameters(),
                ['route' => $fileManager->getRoute().'/'.rawurlencode($file->getFilename())]));

            return [
                'path' => $filePath,
                'html' => "<i class='fas fa-folder-open' aria-hidden='true'></i>",
                'folder' => '<a  href="'.$href.'">'.$file->getFilename().'</a>',
            ];
        }
    }

    public function accept($type)
    {
        switch ($type) {
            case 'image':
                $accept = 'image/*';
                break;
            case 'media':
                $accept = 'video/*';
                break;
            case 'file':
                return false;
            default:
                return false;
        }

        return $accept;
    }

    public function fileIcon($filePath, $extension = null, $size = 75, $lazy = false)
    {
        if (null === $extension) {
            $filePathTmp = strtok($filePath, '?');
            $extension = pathinfo($filePathTmp, PATHINFO_EXTENSION);
        }
        switch (true) {
            case $this->isYoutubeVideo($filePath):
            case preg_match('/(mp4|ogg|webm|avi|wmv|mov)$/i', $extension):
                $fa = 'far fa-file-video';
                break;
            case preg_match('/(mp3|wav)$/i', $extension):
                $fa = 'far fa-file-audio';
                break;
            case preg_match('/(gif|png|jpe?g|svg)$/i', $extension):
                $query = parse_url($filePath, PHP_URL_QUERY);
                $time = 'time='.time();
                $fileName = $query ? $filePath.'&'.$time : $filePath.'?'.$time;

                if ($lazy) {
                    $html = "<img class=\"lazy\" data-src=\"{$fileName}\" height='{$size}'>";
                } else {
                    $html = "<img src=\"{$fileName}\" height='{$size}'>";
                }

                return [
                    'path' => $filePath,
                    'html' => $html,
                    'image' => true,
                ];
            case preg_match('/(pdf)$/i', $extension):
                $fa = 'far fa-file-pdf';
                break;
            case preg_match('/(docx?)$/i', $extension):
                $fa = 'far fa-file-word';
                break;
            case preg_match('/(xlsx?|csv)$/i', $extension):
                $fa = 'far fa-file-excel';
                break;
            case preg_match('/(pptx?)$/i', $extension):
                $fa = 'far fa-file-powerpoint';
                break;
            case preg_match('/(zip|rar|gz)$/i', $extension):
                $fa = 'far fa-file-archive';
                break;
            case filter_var($filePath, FILTER_VALIDATE_URL):
                $fa = 'fab fa-internet-explorer';
                break;
            default:
                $fa = 'far fa-file';
        }

        return [
            'path' => $filePath,
            'html' => "<i class='{$fa}' aria-hidden='true'></i>",
        ];
    }

    public function isYoutubeVideo($url)
    {
        $rx = '~
              ^(?:https?://)?                            
               (?:www[.])?                               
               (?:youtube[.]com/watch[?]v=|youtu[.]be/)  
               ([^&]{11})                                
                ~x';

        return $has_match = preg_match($rx, $url, $matches);
    }
}
