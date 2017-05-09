<?php


namespace Artgris\Bundle\FileManagerBundle\Tests\Controller;

use Artgris\Bundle\FileManagerBundle\Tests\Fixtures\AbstractTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagerControllerTest extends AbstractTestCase
{


    public function setUp()
    {
        parent::setUp();
        $this->initClient(array('environment' => 'default'));
    }


    public function testManager() {


        $this->client->request('GET', '/manager/');

        $this->assertContains(
            'Hello World',
            $this->client->getResponse()->getContent()
        );
        $this->assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode()
        );



    }



}