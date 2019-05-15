<?php

namespace Artgris\Bundle\FileManagerBundle\Twig;

use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use Symfony\Component\Routing\RouterInterface;

class OrderExtension extends \Twig_Extension
{
    const ASC = 'asc';
    const DESC = 'desc';
    const ICON = [self::ASC => 'up', self::DESC => 'down'];

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * OrderExtension constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function order(\Twig_Environment $environment, FileManager $fileManager, $type)
    {
        $order = self::ASC === $fileManager->getQueryParameter('order');
        $active = $fileManager->getQueryParameter('orderby') === $type ? 'actived' : null;
        $orderBy = [];
        $orderBy['orderby'] = $type;
        $orderBy['order'] = $active ? ($order ? self::DESC : self::ASC) : self::ASC;
        $parameters = array_merge($fileManager->getQueryParameters(), $orderBy);

        $icon = $active ? '-'.($order ? self::ICON[self::ASC] : self::ICON[self::DESC]) : '';

        $href = $this->router->generate('file_manager', $parameters);

        return $environment->render('@ArtgrisFileManager/extension/_order.html.twig', [
            'active' => $active,
            'href' => $href,
            'icon' => $icon,
            'type' => $type,
            'islist' => 'list' === $fileManager->getView(),
        ]);
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            'order' => new \Twig_SimpleFunction('order', [$this, 'order'],
                ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }
}
