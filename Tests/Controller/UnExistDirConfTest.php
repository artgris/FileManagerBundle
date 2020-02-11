<?php

namespace Artgris\Bundle\FileManagerBundle\Tests\Controller;

use Artgris\Bundle\FileManagerBundle\Tests\Fixtures\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class UnExistDirConfTest extends AbstractTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->initClient(['environment' => 'unexist']);
    }

    public function testUnExistDirConfManager()
    {
        $this->getManagerPage();
        $this->assertContains(
            'Directory does not exist.',
            $this->client->getResponse()->getContent()
        );
        $this->assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $this->client->getResponse()->getStatusCode()
        );
    }
}
