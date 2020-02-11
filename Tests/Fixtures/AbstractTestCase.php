<?php

namespace Artgris\Bundle\FileManagerBundle\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractTestCase extends WebTestCase
{
    /** @var KernelBrowser */
    protected $client;

    protected function setUp(): void
    {
        $this->initClient();
    }

    protected function initClient(array $options = [])
    {
        $this->client = static::createClient($options);
    }

    protected function getBackendPage(array $queryParameters)
    {
        return $this->client->request('GET', '/manager/?'.http_build_query($queryParameters, '', '&'));
    }

    protected function getManagerPage()
    {
        return $this->getBackendPage(['conf' => 'default']);
    }

    protected function getManagerSubDir()
    {
        return $this->getBackendPage(['conf' => 'default', 'route' => '/SubDir']);
    }
}
