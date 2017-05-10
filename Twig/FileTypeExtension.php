<?php

namespace Artgris\Bundle\FileManagerBundle\Twig;

use Artgris\Bundle\FileManagerBundle\Service\FileTypeService;

/**
 * @author Arthur Gribet <a.gribet@gmail.com>
 */
class FileTypeExtension extends \Twig_Extension
{
    private $fileTypeService;

    public function __construct(FileTypeService $fileTypeService)
    {
        $this->fileTypeService = $fileTypeService;
    }

    public function accept($type)
    {
        return $this->fileTypeService->accept($type);
    }

    public function fileIcon($filePath, $extension = null, $size = 75)
    {
        return $this->fileTypeService->fileIcon($filePath, $extension, $size);
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            'accept' => new \Twig_SimpleFunction('accept', [$this, 'accept'], ['needs_environment' => false, 'is_safe' => ['html']]),
            'fileIcon' => new \Twig_SimpleFunction('fileIcon', [$this, 'fileIcon'], ['needs_environment' => false, 'is_safe' => ['html']]),
        ];
    }
}
