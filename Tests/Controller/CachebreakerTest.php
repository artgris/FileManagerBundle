<?php

namespace Artgris\Bundle\FileManagerBundle\Tests\Controller;

use Artgris\Bundle\FileManagerBundle\Tests\Fixtures\AbstractTestCase;

class CachebreakerTest extends AbstractTestCase
{
    public function setUp(): void
    {
        $this->initClient(['environment' => 'cachebreaker']);
    }

    public function testImagePreviewCachebreakerIsFileModificationTime()
    {
        // A fixed date in the past, so a cachebreaker built from time() cannot match by accident
        touch(__DIR__.'/../Fixtures/web/images/image.png', 1600000000);

        $crawler = $this->getManagerPage();
        $this->assertSame('/manager/file/image.png?conf=default&time=1600000000', $crawler->filter('img.lazy')->attr('data-src'));
    }

    public function testFileIsNotResentWhenNotModified()
    {
        $image = __DIR__.'/../Fixtures/web/images/image.png';
        touch($image, 1600000000);

        // Only the 304 path is requested: a 200 guesses the MIME type, which needs ext-fileinfo (missing on the Windows CI)
        $this->client->request('GET', '/manager/file/image.png?conf=default', [], [], ['HTTP_IF_MODIFIED_SINCE' => gmdate('D, d M Y H:i:s', 1600000000).' GMT']);
        $this->assertSame(304, $this->client->getResponse()->getStatusCode());
    }
}
