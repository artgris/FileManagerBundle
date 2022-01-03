<?php

namespace Artgris\Bundle\FileManagerBundle\Twig;

use Artgris\Bundle\FileManagerBundle\Helpers\FileManager;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class OrderExtension extends AbstractExtension
{
    const ASC = 'asc';
    const DESC = 'desc';
    const ICON = [self::ASC => 'up', self::DESC => 'down'];

    /**
     * OrderExtension constructor.
     */
    public function __construct(private RouterInterface $router)
    {
    }

    public function order(Environment $environment, FileManager $fileManager, $type): string {
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
    public function getFunctions(): array {
        return [
            'order' => new TwigFunction('order', [$this, 'order'],
                ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }
}
