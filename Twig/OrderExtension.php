<?php

namespace Artgris\Bundle\FileManagerBundle\Twig;

use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use Symfony\Component\Routing\Router;

class OrderExtension extends \Twig_Extension
{
    const ASC = 'asc';
    const DESC = 'desc';

    /**
     * @var Router
     */
    private $router;

    /**
     * OrderExtension constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function order(\Twig_Environment $environment, FileManager $fileManager, $type)
    {
        $order = $fileManager->getQueryParameter('order') === self::ASC;
        $active = $fileManager->getQueryParameter('orderby') === $type ? 'actived' : null;
        $orderBy = array();
        $orderBy['orderby'] = $type;
        $orderBy['order'] = $active ? ($order ? self::DESC : self::ASC) : self::ASC;
        $parameters = array_merge($fileManager->getQueryParameters(), $orderBy);

        $glyphicon = $active ? '-'.($order ? self::ASC : self::DESC) : '';

        $href = $this->router->generate('file_manager', $parameters);

        return $environment->render('@ArtgrisFileManager/extension/_order.html.twig', array(
            'active' => $active,
            'href' => $href,
            'glyphicon' => $glyphicon,
            'type' => $type,
            'islist' => $fileManager->getView() === 'list',
        ));
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'order' => new \Twig_SimpleFunction('order', array($this, 'order'), array('needs_environment' => true, 'is_safe' => array('html'))),
        );
    }
}
