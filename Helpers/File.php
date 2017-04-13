<?php


namespace Artgris\Bundle\FileManagerBundle\Helpers;


use Artgris\Bundle\FileManagerBundle\Service\FileTypeService;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Translation\TranslatorInterface;

class File
{
    /**
     * @var SplFileInfo
     */
    private $file;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var FileTypeService
     */
    private $fileTypeService;
    /**
     * @var FileManager
     */
    private $fileManager;
    private $preview;


    /**
     * File constructor.
     * @param SplFileInfo $file
     * @param TranslatorInterface $translator
     * @param FileTypeService $fileTypeService
     * @param FileManager $fileManager
     * @internal param $module
     */
    public function __construct(SplFileInfo $file, TranslatorInterface $translator, FileTypeService $fileTypeService, FileManager $fileManager)
    {
        $this->file = $file;
        $this->translator = $translator;
        $this->fileTypeService = $fileTypeService;
        $this->fileManager = $fileManager;
        $this->preview = $this->fileTypeService->preview($this->fileManager, $this->file);
    }


    private function getDimension()
    {
        return preg_match('/(gif|png|jpe?g|svg)$/i', $this->file->getExtension()) ?
            $imageSize[$this->file->getFilename()] = getimagesize($this->file->getPathname()) : '';
    }

    public function getHTMLDimension()
    {

        $dimension = $this->getDimension();
        if ($dimension) {
            return "{$dimension[0]} × {$dimension[1]}";
        }
    }

    public function getHTMLSize()
    {
        if ($this->getFile()->getType() == 'file') {
            $size = $this->file->getSize() / 1000;
            $kb = $this->translator->trans("size.kb");
            $mb = $this->translator->trans("size.mb");
            return $size > 1000 ? number_format(($size / 1000), 1, '.', '') . " " . $mb : number_format($size, 1, '.', '') . " " . $kb;
        }
    }

    public function getAttribut($module)
    {
        if ($module) {
            $attr = '';
            if ($this->getDimension()) {

                $width = $this->getDimension()[0];
                $height = $this->getDimension()[0];
                $attr .= "data-width=\"{$width}\" data-height=\"{$height}\" ";
            }

            if ($this->fileManager->getModule() && $this->file->getType() === 'file') {
                $attr .= "data-path=\"{$this->getPreview()['path']}\"";
            }

            $attr .= ' class="select"';
            return $attr;
        }
    }


    /**
     * @return SplFileInfo
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param SplFileInfo $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return array
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * @param array $preview
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;
    }

}