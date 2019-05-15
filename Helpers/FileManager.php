<?php

namespace Artgris\Bundle\FileManagerBundle\Helpers;

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

    private $queryParameters;
    private $kernelRoute;
    private $router;
    private $configuration;
    private $webDir;

    /**
     * FileManager constructor.
     *
     * @param $queryParameters
     * @param $configuration
     * @param $kernelRoute
     * @param RouterInterface $router
     * @param $webDir
     *
     * @internal param $basePath
     */
    public function __construct($queryParameters, $configuration, $kernelRoute, RouterInterface $router, $webDir)
    {
        $this->queryParameters = $queryParameters;
        $this->configuration = $configuration;
        $this->kernelRoute = $kernelRoute;
        $this->router = $router;
        // Check Security
        $this->checkSecurity();
        $this->webDir = $webDir;
    }

    public function getDirName()
    {
        return dirname($this->getBasePath());
    }

    public function getBaseName()
    {
        return basename($this->getBasePath());
    }

    public function getRegex()
    {
        if (isset($this->configuration['regex'])) {
            return '/'.$this->configuration['regex'].'/i';
        }

        switch ($this->getType()) {
            case 'media':
                return '/\.(mp4|ogg|webm)$/i';
                break;
            case 'image':
                return '/\.(gif|png|jpe?g|svg)$/i';
            case 'file':
            default:
                return '/.+$/i';
        }
    }

    public function getCurrentRoute()
    {
        return urldecode($this->getRoute());
    }

    public function getCurrentPath()
    {
        return realpath($this->getBasePath().$this->getCurrentRoute());
    }

    // parent url
    public function getParent()
    {
        $queryParentParameters = $this->queryParameters;
        $parentRoute = dirname($this->getCurrentRoute());

        if (DIRECTORY_SEPARATOR !== $parentRoute) {
            $queryParentParameters['route'] = dirname($this->getCurrentRoute());
        } else {
            unset($queryParentParameters['route']);
        }

        $parentRoute = $this->router->generate('file_manager', $queryParentParameters);

        return $this->getRoute() ? $parentRoute : null;
    }

    public function getImagePath()
    {
        $baseUrl = $this->getBaseUrl();
        if ($baseUrl) {
            return $baseUrl.$this->getCurrentRoute().'/';
        }

        return false;
    }

    private function getBaseUrl()
    {
        $webPath = '../'.$this->webDir;
        $dirl = new \SplFileInfo($this->getConfiguration()['dir']);
        $base = $dirl->getPathname();
        if (0 === mb_strpos($base, $webPath)) {
            return mb_substr($base, mb_strlen($webPath));
        }

        return false;
    }

    private function checkSecurity()
    {
        if (!isset($this->configuration['dir'])) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR,
                'Please define a "dir" parameter in your config.yml');
        }
        $dir = $this->configuration['dir'];

        $fileSystem = new Filesystem();
        $exist = $fileSystem->exists($dir);
        if (false === $exist) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Directory does not exist.');
        }

        $currentPath = $this->getCurrentPath();

        // check Path security
        if (false === $currentPath || 0 !== mb_strpos($currentPath, $this->getBasePath())) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'You are not allowed to access this folder.');
        }
    }

    public function getModule()
    {
        return isset($this->getQueryParameters()['module']) ? $this->getQueryParameters()['module'] : null;
    }

    public function getType()
    {
        return $this->mergeConfAndQuery('type');
    }

    /**
     * @param null $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    public function getRoute()
    {
        return isset($this->getQueryParameters()['route']) && '/' !== $this->getQueryParameters()['route'] ? $this->getQueryParameters()['route'] : null;
    }

    /**
     * @return bool|string
     */
    public function getBasePath()
    {
        return realpath($this->getConfiguration()['dir']);
    }

    /**
     * @return mixed
     */
    public function getQueryParameters()
    {
        return $this->queryParameters;
    }

    /**
     * @param mixed $queryParameters
     */
    public function setQueryParameters($queryParameters)
    {
        $this->queryParameters = $queryParameters;
    }

    /**
     * @return mixed
     */
    public function getKernelRoute()
    {
        return $this->kernelRoute;
    }

    /**
     * @param mixed $kernelRoute
     */
    public function setKernelRoute($kernelRoute)
    {
        $this->kernelRoute = $kernelRoute;
    }

    /**
     * @return RouterInterface
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @return mixed
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param mixed $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    public function getTree()
    {
        return $this->mergeQueryAndConf('tree', true);
    }

    public function getView()
    {
        return $this->mergeQueryAndConf('view', 'list');
    }

    public function getQueryParameter($parameter)
    {
        return isset($this->getQueryParameters()[$parameter]) ? $this->getQueryParameters()[$parameter] : null;
    }

    public function getConfigurationParameter($parameter)
    {
        return isset($this->getConfiguration()[$parameter]) ? $this->getConfiguration()[$parameter] : null;
    }

    private function mergeQueryAndConf($parameter, $default = null)
    {
        if ($this->getQueryParameter($parameter) !== null) {
            return $this->getQueryParameter($parameter);
        }
        if ($this->getConfigurationParameter($parameter) !== null) {
            return $this->getConfigurationParameter($parameter);
        }

        return $default;
    }

    private function mergeConfAndQuery($parameter, $default = null)
    {
        if ($this->getConfigurationParameter($parameter) !== null) {
            return $this->getConfigurationParameter($parameter);
        }
        if ($this->getQueryParameter($parameter) !== null) {
            return $this->getQueryParameter($parameter);
        }

        return $default;
    }
}
