<?php

namespace Artgris\Bundle\FileManagerBundle\Service;

use Artgris\Bundle\FileManagerBundle\Helpers\File;
use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use SplFileInfo;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class FileTypeService {
    public const IMAGE_SIZE = [
        FileManager::VIEW_LIST => '22',
        FileManager::VIEW_THUMBNAIL => '100',
    ];

    /**
     * FileTypeService constructor.
     */
    public function __construct(private RouterInterface $router, private Environment $twig) {
    }

    public function preview(FileManager $fileManager, SplFileInfo $file) {

        if ($fileManager->getImagePath()) {
            $filePath = $fileManager->getImagePath().rawurlencode($file->getFilename());
        } else {
            $filePath = $this->router->generate(
                'file_manager_file',
                array_merge($fileManager->getQueryParameters(), ['fileName' => $file->getFilename()])
            );
        }
        $extension = $file->getExtension();
        $type = $file->getType();
        if ('file' === $type) {
            $size = $this::IMAGE_SIZE[$fileManager->getView()];

            return $this->fileIcon($filePath, $extension, $size, true, $fileManager->getConfigurationParameter('twig_extension'), $fileManager->getConfigurationParameter('cachebreaker'));
        }
        if ('dir' === $type) {

            $href = $this->router->generate(
                'file_manager', array_merge(
                $fileManager->getQueryParameters(),
                ['route' => $fileManager->getRoute().'/'.$file->getFilename()]
            )
            );

            return [
                'path' => $filePath,
                'html' => "<i class='fas fa-folder-open' aria-hidden='true'></i>",
                'folder' => '<a  href="'.$href.'">'.$file->getFilename().'</a>',
            ];
        }
    }

    public function accept($type): bool|string {
        switch ($type) {
            case 'image':
                $accept = 'image/*';
                break;
            case 'media':
                $accept = 'video/*';
                break;
            default:
                return false;
        }

        return $accept;
    }

    public function fileIcon(string $filePath,?string $extension = null, ?int $size = 75, ?bool $lazy = false, ?string $twigExtension = null, ?bool $cachebreaker = null): array {

        $imageTemplate = null;

        // Strip the query string before reading the extension, then match it case-insensitively
        $extension = strtolower($extension ?? (new SplFileInfo(explode('?', $filePath)[0]))->getExtension());
        switch (true) {
            case $this->isYoutubeVideo($filePath):
            case \in_array($extension, ['mp4', 'ogg', 'webm', 'avi', 'wmv', 'mov'], true):
                $fa = 'far fa-file-video';
                break;
            case \in_array($extension, ['mp3', 'wav'], true):
                $fa = 'far fa-file-audio';
                break;
            case \in_array($extension, File::IMAGE_EXTENSIONS, true):

                $fileName = $filePath;
                if ($cachebreaker) {
                    $query = parse_url($filePath, PHP_URL_QUERY);
                    $time = 'time='.time();
                    $fileName = $query ? $filePath.'&'.$time : $filePath.'?'.$time;
                }

                if ($twigExtension) {
                    $imageTemplate = str_replace('$IMAGE$', 'file_path', $twigExtension);
                }

                $html = $this->twig->render('@ArtgrisFileManager/views/preview.html.twig', [
                    'filename' => $fileName,
                    'size' => $size,
                    'lazy' => $lazy,
                    'twig_extension' => $twigExtension,
                    'image_template' => $imageTemplate,
                    'file_path' => $filePath,

                ]);

                return [
                    'path' => $filePath,
                    'html' => $html,
                    'image' => true,
                ];
            case 'pdf' === $extension:
                $fa = 'far fa-file-pdf';
                break;
            case \in_array($extension, ['doc', 'docx'], true):
                $fa = 'far fa-file-word';
                break;
            case \in_array($extension, ['xls', 'xlsx', 'csv'], true):
                $fa = 'far fa-file-excel';
                break;
            case \in_array($extension, ['ppt', 'pptx'], true):
                $fa = 'far fa-file-powerpoint';
                break;
            case \in_array($extension, ['zip', 'rar', 'gz', 'tgz'], true):
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

    public function isYoutubeVideo($url): bool|int {
        $rx = '~
              ^(?:https?://)?                            
               (?:www[.])?                               
               (?:youtube[.]com/watch[?]v=|youtu[.]be/)  
               ([^&]{11})                                
                ~x';

        return preg_match($rx, $url, $matches);
    }
}
