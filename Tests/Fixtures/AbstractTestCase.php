<?php


namespace Artgris\Bundle\FileManagerBundle\Tests\Fixtures;


use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractTestCase extends WebTestCase
{



    /** @var Client */
    protected $client;

    protected function setUp()
    {
        $this->initClient();
    }

    protected function initClient(array $options = array())
    {
        $this->client = static::createClient($options);
    }

    protected function getBackendPage(array $queryParameters)
    {
        return $this->client->request('GET', '/manager/?' . http_build_query($queryParameters, '', '&'));
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