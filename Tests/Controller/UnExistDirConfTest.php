<?php

namespace Artgris\Bundle\FileManagerBundle\Tests\Controller;

use Artgris\Bundle\FileManagerBundle\Tests\Fixtures\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class UnExistDirConfTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->initClient(array('environment' => 'unexist'));
    }

    public function testUnExistDirConfManager()
    {
        $this->getManagerPage();
        $this->assertContains(
            'You are not allowed to access this folder',
            $this->client->getResponse()->getContent()
        );
        $this->assertSame(
            Response::HTTP_UNAUTHORIZED,
            $this->client->getResponse()->getStatusCode()
        );
    }
}
