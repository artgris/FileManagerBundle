<?php

namespace Artgris\Bundle\FileManagerBundle\Twig;

use Artgris\Bundle\FileManagerBundle\Service\FileTypeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Arthur Gribet <a.gribet@gmail.com>
 */
class FileTypeExtension extends AbstractExtension {
    public function __construct(private FileTypeService $fileTypeService) {
    }

    public function accept($type): bool|string {
        return $this->fileTypeService->accept($type);
    }

    public function fileIcon(?string $filePath, ?string $extension = null, ?int $size = 75): array {
        return $this->fileTypeService->fileIcon($filePath, $extension, $size);
    }

    public function getFunctions(): array {
        return [
            'accept' => new TwigFunction('accept', [$this, 'accept'], ['needs_environment' => false, 'is_safe' => ['html']]),
            'fileIcon' => new TwigFunction('fileIcon', [$this, 'fileIcon'], ['needs_environment' => false, 'is_safe' => ['html']]),
        ];
    }
}
