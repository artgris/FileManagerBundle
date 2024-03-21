<?php

namespace Artgris\Bundle\FileManagerBundle\Helpers;

use Artgris\Bundle\FileManagerBundle\Event\FileManagerEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Arthur Gribet <a.gribet@gmail.com>
 */
class FileManager
{
    const VIEW_THUMBNAIL = 'thumbnail';
    const VIEW_LIST = 'list';

    /**
     * FileManager constructor.
     */
    public function __construct(private array $queryParameters, private array $configuration, private RouterInterface $router, private EventDispatcherInterface $dispatcher, private string $webDir)
    {
        // Check Security
        $this->checkSecurity();
    }

    public function getDirName(): string
    {
        return \dirname($this->getBasePath());
    }

    public function getBaseName(): string
    {
        return basename($this->getBasePath());
    }

    public function getRegex(): string
    {
        if (isset($this->configuration['regex'])) {
            return '/' . $this->configuration['regex'] . '/i';
        }

        return match ($this->getType()) {
            'media' => '/\.(mp4|ogg|webm)$/i',
            'image' => '/\.(gif|png|jpe?g|svg|webp)$/i',
            default => '/.+$/i',
        };
    }

//    public function getCurrentRoute(): ?string {
//        if ($this->getRoute()) {
//            return urldecode($this->getRoute());
//        }
//
//        return null;
//    }

    public function getCurrentPath(): bool|string
    {
        return realpath($this->getBasePath() . $this->getRoute());
    }

    // parent url
    public function getParent(): ?string
    {
        $queryParentParameters = $this->queryParameters;

        if ($this->getRoute()) {

            $parentRoute = \dirname($this->getRoute());
            if (\DIRECTORY_SEPARATOR !== $parentRoute) {
                $queryParentParameters['route'] = \dirname($this->getRoute());
            } else {
                unset($queryParentParameters['route']);
            }

            $parentRoute = $this->router->generate('file_manager', $queryParentParameters);

            return $this->getRoute() ? $parentRoute : null;
        }

        return null;
    }

    public function getImagePath(): bool|string
    {
        $baseUrl = $this->getBaseUrl();

        if ($baseUrl) {
            $routePath = $this->getRoutePath();
            return $baseUrl . $routePath . '/';
        }

        return false;
    }

    private function getBaseUrl(): bool|string
    {
        $webPath = $this->webDir;
        $dirl = new \SplFileInfo($this->getConfiguration()['dir']);
        $base = $dirl->getPathname();

        if (str_starts_with($base, $webPath)) {
            return mb_substr($base, mb_strlen($webPath));
        }

        return false;
    }

    private function checkSecurity(): void
    {
        if (!isset($this->configuration['dir'])) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Please define a "dir" parameter in your config.yml');
        }
        $dir = $this->configuration['dir'];

        $fileSystem = new Filesystem();
        $exist = $fileSystem->exists($dir);
        if (false === $exist) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Directory does not exist.');
        }

        $currentPath = $this->getCurrentPath();

        // check Path security
        if (false === $currentPath || !str_starts_with($currentPath, $this->getBasePath())) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'You are not allowed to access this folder.');
        }
        $event = new GenericEvent($this, ['path' => $currentPath]);
        $this->dispatcher->dispatch($event, FileManagerEvents::POST_CHECK_SECURITY);

    }

    public function getModule(): ?string
    {
        return $this->getQueryParameters()['module'] ?? null;
    }

    public function getType(): ?string
    {
        return $this->mergeConfAndQuery('type');
    }

    public function getRoute(): ?string
    {
        return isset($this->getQueryParameters()['route']) && '/' !== $this->getQueryParameters()['route'] ? $this->getQueryParameters()['route'] : null;
    }

    public function getRoutePath(): ?string
    {
        return implode('/', array_map('rawurlencode', explode('/', $this->getRoute())));
    }

    public function getBasePath(): bool|string
    {
        return realpath($this->getConfiguration()['dir']);
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    public function setRouter(RouterInterface $router): void
    {
        $this->router = $router;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getTree(): bool
    {
        return $this->mergeQueryAndConf('tree', true);
    }

    public function getView(): string
    {
        return $this->mergeQueryAndConf('view', 'list');
    }

    public function getQueryParameter(string $parameter)
    {
        return $this->getQueryParameters()[$parameter] ?? null;
    }

    public function getConfigurationParameter(string $parameter)
    {
        return $this->getConfiguration()[$parameter] ?? null;
    }

    private function mergeQueryAndConf(string $parameter, $default = null)
    {
        if (null !== $this->getQueryParameter($parameter)) {
            return $this->getQueryParameter($parameter);
        }
        if (null !== $this->getConfigurationParameter($parameter)) {
            return $this->getConfigurationParameter($parameter);
        }

        return $default;
    }

    private function mergeConfAndQuery(string $parameter, $default = null)
    {
        if (null !== $this->getConfigurationParameter($parameter)) {
            return $this->getConfigurationParameter($parameter);
        }
        if (null !== $this->getQueryParameter($parameter)) {
            return $this->getQueryParameter($parameter);
        }

        return $default;
    }
}
