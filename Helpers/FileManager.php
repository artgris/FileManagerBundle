<?php


namespace Artgris\Bundle\FileManagerBundle\Helpers;


use Symfony\Bundle\FrameworkBundle\Routing\Router;

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
        $this->kernelRoute = $kernelRoute;
        $this->router = $router;
        // Check Security
        $this->checkSecurity();
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
            return '/' . $this->configuration['regex'] . '/i';
        }

        switch ($this->getType()) {
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
        return urldecode($this->getRoute());
    }

    public function getCurrentPath()
    {
        return realpath($this->getBasePath() . $this->getCurrentRoute());
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

        return $this->getRoute() ? $parentRoute : null;
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

        if (0 === strpos($this->getBasePath(), $webPath)) {
            return substr($this->getBasePath(), strlen($webPath));
        }
        return false;

    }

    private function checkSecurity()
    {
        $currentPath = $this->getCurrentPath();
        // check Path security
        if ($currentPath === false || strpos($currentPath, $this->getBasePath()) !== 0 || !isset($this->configuration['dir'])) {
            throw new \Exception();
        }
    }

    /**
     * @return null
     */
    public function getModule()
    {
        return isset($this->getQueryParameters()['module']) ? $this->getQueryParameters()['module'] : null;
    }

    /**
     * @return null
     */
    public function getType()
    {
        return isset($this->getConfiguration()['type']) ? $this->getConfiguration()['type'] : (isset($this->getQueryParameters()['type']) ? $this->getQueryParameters()['type'] : null);
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

    /**
     * @return int
     */
    public function getTree()
    {
        return isset($this->getQueryParameters()['tree']) ? $this->getQueryParameters()['tree'] : (isset($this->getConfiguration()['tree']) ? $this->getConfiguration()['tree'] : true);

    }


    /**
     * @return int
     */
    public function getView()
    {
        return isset($this->getQueryParameters()['view']) ? $this->getQueryParameters()['view'] : (isset($this->getConfiguration()['view']) ? $this->getConfiguration()['view'] : 'list');

    }


}