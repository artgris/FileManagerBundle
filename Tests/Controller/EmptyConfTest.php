<?php

namespace Artgris\Bundle\FileManagerBundle\Tests\Controller;

use Artgris\Bundle\FileManagerBundle\Tests\Fixtures\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class EmptyConfTest extends AbstractTestCase
{
    public function setUp(): void
    {
        $this->initClient(['environment' => 'empty']);
    }

    public function testUndefinedConfManager()
    {
        $this->getManagerPage();
        $content = html_entity_decode(
            $this->client->getResponse()->getContent(),
            ENT_QUOTES | ENT_HTML5
        );
        $this->assertStringContainsString(
            $content,
            $this->client->getResponse()->getContent()
        );
        $this->assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $this->client->getResponse()->getStatusCode()
        );
    }
}
