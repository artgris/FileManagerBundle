<?php

namespace Artgris\Bundle\FileManagerBundle\Helpers;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
     * @param Router $router
     * @param $webDir
     *
     * @internal param $basePath
     */
    public function __construct($queryParameters, $configuration, $kernelRoute, Router $router, $webDir)
    {
        $this->queryParameters = $queryParameters;
        $this->configuration = $configuration;
        $this->kernelRoute = $kernelRoute;
        $this->router = $router;
        // Check Security
        $this->checkDirectoryExists();
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

        if ($parentRoute !== DIRECTORY_SEPARATOR) {
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

    private function checkDirectoryExists()
    {
        $fileSystem = new Filesystem();
        $dir = $this->getConfiguration()['dir'];
        $exist = $fileSystem->exists($dir);
        if ($exist === false) {
            throw new HttpException(500, "The directory '{$dir}' does not exist.");
        }
    }

    private function checkSecurity()
    {
        $currentPath = $this->getCurrentPath();

        // check Path security
        if ($currentPath === false || mb_strpos($currentPath, $this->getBasePath()) !== 0) {
            throw new HttpException(401, 'You are not allowed to access this folder.');
        }

        if (!isset($this->configuration['dir'])) {
            throw new HttpException(500, 'Please define a "dir" parameter in your config.yml');
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
        return isset($this->getQueryParameters()['route']) ? $this->getQueryParameters()['route'] : null;
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
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param Router $router
     */
    public function setRouter(Router $router)
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
        return $this->getQueryParameter($parameter) !== null ? $this->getQueryParameter($parameter) : ($this->getConfigurationParameter($parameter) ? $this->getConfigurationParameter($parameter) : $default);
    }

    private function mergeConfAndQuery($parameter, $default = null)
    {
        return $this->getConfigurationParameter($parameter) !== null ? $this->getConfigurationParameter($parameter) : ($this->getQueryParameter($parameter) ? $this->getQueryParameter($parameter) : $default);
    }
}
