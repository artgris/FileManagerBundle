<?php

namespace Artgris\Bundle\FileManagerBundle\Twig;

use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use Artgris\Bundle\FileManagerBundle\Service\FileTypeService;
use SplFileInfo;

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


    public function preview(FileManager $fileManager, SplFileInfo $file)
    {
        return $this->fileTypeService->preview($fileManager, $file);
    }

    public function accept($type)
    {
        return $this->fileTypeService->accept($type);
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            'preview' => new \Twig_SimpleFunction('preview', [$this, 'preview'], ['needs_environment' => false, 'is_safe' => ['html']]),
            'accept' => new \Twig_SimpleFunction('accept', [$this, 'accept'], ['needs_environment' => false, 'is_safe' => ['html']]),
        ];
    }


}