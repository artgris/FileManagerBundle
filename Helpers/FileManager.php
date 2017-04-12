<?php


namespace Artgris\Bundle\FileManagerBundle\Helpers;


use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * @author Arthur Gribet <a.gribet@gmail.com>
 */
class FileManager
{

    private $module;
    private $theme;
    private $th;
    private $type;
    private $route;
    private $basePath;
    private $queryParameters;
    private $kernelRoute;
    private $router;
    private $configuration;


    /**
     * FileManager constructor.
     * @param $queryParameters
     * @param $configuration
     * @param $kernelRoute
     * @param Router $router
     * @internal param $basePath
     */
    public function __construct($queryParameters, $configuration, $kernelRoute, Router $router)
    {
        $this->queryParameters = $queryParameters;
        $this->configuration = $configuration;
        $this->route = isset($queryParameters['route']) ? $queryParameters['route'] : null;
        $this->module = isset($queryParameters['module']) ? $queryParameters['module'] : null;
        $this->theme = isset($queryParameters['theme']) ? $queryParameters['theme'] : 1;
        $this->th = isset($queryParameters['th']) ? $queryParameters['th'] : 1;
        $this->type = isset($configuration['type']) ? $configuration['type'] : (isset($queryParameters['type']) ? $queryParameters['type'] : null);
        $this->basePath = realpath($configuration['dir']);
        $this->kernelRoute = $kernelRoute;
        $this->router = $router;
        // Check Security
        $this->checkSecurity();
    }

    public function getDirName()
    {
        return dirname($this->basePath);
    }

    public function getBaseName()
    {
        return basename($this->basePath);
    }

    public function getRegex()
    {
        if (isset($this->configuration['regex'])) {
            return '/' . $this->configuration['regex'] . '/i';
        }

        switch ($this->type) {
            case 'media':
                return '/\.(mp4|ogg|webm)$/i';
                break;
            case 'image':
                return '/\.(gif|png|jpe?g|svg)$/i';
            case 'file':
            default :
                return "/.+$/i";
        }
    }

    public function getCurrentRoute()
    {
        return urldecode($this->route);
    }

    public function getCurrentPath()
    {
        return realpath($this->basePath . $this->getCurrentRoute());
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

        $parentRoute = $this->router->generate("file_manager", $queryParentParameters);

        return $this->route ? $parentRoute : null;
    }


    public function getImagePath()
    {
        $baseUrl = $this->getBaseUrl();
        if ($baseUrl) {
            return $baseUrl . $this->getCurrentRoute() . DIRECTORY_SEPARATOR;
        }
        return false;
    }

    private function getBaseUrl()
    {

        $webPath = realpath($this->kernelRoute . '/../web');

        if (0 === strpos($this->basePath, $webPath)) {
            return substr($this->basePath, strlen($webPath));
        }
        return false;

    }

    private function checkSecurity()
    {
        $currentPath = $this->getCurrentPath();
        // check Path security
        if ($currentPath === false || strpos($currentPath, $this->basePath) !== 0 || !isset($this->configuration['dir'])) {
            throw new \Exception();
        }
    }

    /**
     * @return null
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param null $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * @return null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param null $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return null
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param null $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @return bool|string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param bool|string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
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

    /**
     * @return int
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * @param int $theme
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    /**
     * @return int
     */
    public function getTh()
    {
        return $this->th;
    }

    /**
     * @param int $th
     */
    public function setTh($th)
    {
        $this->th = $th;
    }

}