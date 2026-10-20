<?php

namespace Artgris\Bundle\FileManagerBundle\Tests\Controller;

use Artgris\Bundle\FileManagerBundle\Tests\Fixtures\AbstractTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ThumbnailsTest extends AbstractTestCase
{
    private const DIR = __DIR__.'/../Fixtures/thumbnails/';

    // Files created by the tests, removed in tearDown() so the fixture directory stays as committed
    private const TEMPORARY_FILES = ['delete.png', 'rename.png', 'renamed.png', 'command.png'];

    public function setUp(): void
    {
        $this->initClient(['environment' => 'thumbnails']);
    }

    protected function tearDown(): void
    {
        foreach (self::TEMPORARY_FILES as $file) {
            @unlink(self::DIR.$file);
            @unlink(self::DIR.'thumbnail/'.$file);
        }
        parent::tearDown();
    }

    public function testVersionDirectoryIsNotListed()
    {
        $crawler = $this->getBackendPage(['conf' => 'default', 'tree' => 0]);
        $this->assertCount(1, $crawler->filter('.list-blk tbody tr'));

        $this->getBackendPage(['conf' => 'default', 'json' => 1]);
        $treeData = json_decode($this->client->getResponse()->getContent(), true)['treeData'];
        $this->assertNull($treeData[0]['children']);
    }

    public function testPreviewUsesThumbnail()
    {
        $crawler = $this->getManagerPage();
        $this->assertStringStartsWith('/manager/file/image.png?conf=default&route=/thumbnail&time=', $crawler->filter('img.lazy')->attr('data-src'));
        // The modal preview keeps the original
        $this->assertSame('/manager/file/image.png?conf=default', $crawler->filter('.js-open-modal')->attr('data-href'));
    }

    public function testPublicPreviewUsesThumbnailUrl()
    {
        $crawler = $this->getBackendPage(['conf' => 'public']);
        $this->assertStringStartsWith('/thumbnails/thumbnail/image.png?time=', $crawler->filter('img.lazy')->attr('data-src'));
        $this->assertSame('/thumbnails/image.png', $crawler->filter('.js-open-modal')->attr('data-href'));
    }

    public function testWithoutThumbnailVersionNothingChanges()
    {
        $crawler = $this->getBackendPage(['conf' => 'no_thumbnail', 'tree' => 0]);
        $this->assertCount(2, $crawler->filter('.list-blk tbody tr'));
        $this->assertStringStartsWith('/manager/file/image.png?conf=no_thumbnail&tree=0&time=', $crawler->filter('img.lazy')->attr('data-src'));
    }

    public function testDeleteRemovesVersions()
    {
        $this->createImage('delete.png');

        $this->client->request('POST', '/manager/delete/?conf=default&delete[]=delete.png', ['delete_f' => ['DELETE' => '']]);

        $this->assertFileDoesNotExist(self::DIR.'delete.png');
        $this->assertFileDoesNotExist(self::DIR.'thumbnail/delete.png');
    }

    public function testRenameRenamesVersions()
    {
        $this->createImage('rename.png');

        $this->client->request('POST', '/manager/rename/rename.png?conf=default', ['rename_f' => ['name' => 'renamed', 'extension' => 'png', 'send' => '']]);

        $this->assertFileExists(self::DIR.'renamed.png');
        $this->assertFileExists(self::DIR.'thumbnail/renamed.png');
        $this->assertFileDoesNotExist(self::DIR.'thumbnail/rename.png');
    }

    public function testCommandCreatesMissingVersions()
    {
        if (!\extension_loaded('gd') && !\extension_loaded('imagick')) {
            $this->markTestSkipped('Scaling images needs ext-gd or ext-imagick.');
        }
        copy(self::DIR.'image.png', self::DIR.'command.png');

        $tester = new CommandTester((new Application(static::$kernel))->find('artgris:filemanager:thumbnails'));
        $tester->execute(['conf' => 'default']);

        // image.png already has its thumbnail, only command.png gets one
        $this->assertStringContainsString('1 image version(s) created, 0 failed.', $tester->getDisplay());
        $this->assertSame([2, 2], \array_slice(getimagesize(self::DIR.'thumbnail/command.png'), 0, 2));
    }

    private function createImage(string $name): void
    {
        copy(self::DIR.'image.png', self::DIR.$name);
        copy(self::DIR.'thumbnail/image.png', self::DIR.'thumbnail/'.$name);
    }
}
