<?php

namespace Artgris\Bundle\FileManagerBundle\Helpers;

use Artgris\Bundle\FileManagerBundle\Service\FileTypeService;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Contracts\Translation\TranslatorInterface;

class File
{
    public const IMAGE_EXTENSIONS = ['gif', 'png', 'jpg', 'jpeg', 'svg', 'webp'];

    private ?array $preview = null;

    private array|false|string|null $dimension = null;

    /**
     * File constructor.
     */
    public function __construct(private SplFileInfo $file,private TranslatorInterface $translator,private FileTypeService $fileTypeService,private FileManager $fileManager)
    {
    }

    /**
     * Memoized: sorting by dimension calls this for every comparison.
     *
     * @return array|false|string
     */
    public function getDimension(): bool|array|string {
        return $this->dimension ??= $this->readDimension();
    }

    private function readDimension(): array|false|string {
        if (!\in_array(strtolower($this->file->getExtension()), self::IMAGE_EXTENSIONS, true)) {
            return '';
        }

        try {
            // Turns the warning emitted for unreadable or corrupt images into an exception
            return ErrorHandler::call('getimagesize', $this->file->getPathname());
        } catch (\ErrorException) {
            return false;
        }
    }

    public function getHTMLDimension(): ?string {
        $dimension = $this->getDimension();
        if ($dimension) {
            return "{$dimension[0]} × {$dimension[1]}";
        }

        return null;
    }

    public function getHTMLSize(): ?string {
        if ('file' === $this->getFile()->getType()) {
            $size = $this->file->getSize() / 1000;
            $kb = $this->translator->trans('size.kb');
            $mb = $this->translator->trans('size.mb');

            return $size > 1000 ? number_format(($size / 1000), 1, '.', '').' '.$mb : number_format($size, 1, '.', '').' '.$kb;
        }
        return null;
    }

    public function getAttribut(): ?string {
        if ($this->fileManager->getModule()) {
            $attr = '';
            $dimension = $this->getDimension();
            if ($dimension) {
                $width = $dimension[0];
                $height = $dimension[1];
                $attr .= "data-width=\"{$width}\" data-height=\"{$height}\" ";
            }

            if ('file' === $this->file->getType()) {
                $attr .= "data-path=\"{$this->getPreview()['path']}\"";
                $attr .= ' class="select"';
            }

            return $attr;
        }

        return null;
    }

    public function isImage(): bool {
        return \array_key_exists('image', $this->getPreview());
    }

    public function getFile(): SplFileInfo {
        return $this->file;
    }

    public function setFile(SplFileInfo $file) :void
    {
        $this->file = $file;
    }

    public function getPreview(): array {
        // Lazy: the preview renders a Twig template, so build it only for files that are displayed
        return $this->preview ??= $this->fileTypeService->preview($this->fileManager, $this->file);
    }

    public function setPreview(array $preview) :void
    {
        $this->preview = $preview;
    }
}
