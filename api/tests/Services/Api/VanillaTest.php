<?php

use App\Services\Api\Vanilla\Client;

class VanillaTest extends TestCase
{
    public function testInvalidApiGroup()
    {
        $this->setExpectedExceptionRegExp(
            InvalidArgumentException::class,
            '/foobar/i',
            0
        );

        $client = App::make(Client::class);
        $test = $client->api('foobar');
    }

    public function testConfigurationCurrent()
    {
        $client = App::make(Client::class);

        $current = $client->api('configuration')->current();

        $this->assertContains('Title', $current);
    }

    public function testDiscussionsAll()
    {
        $client = App::make(Client::class);

        $all = $client->api('discussions')->all();

        $array = json_decode($all, true);

        $this->assertArrayHasKey('Discussions', $array);
    }

    public function testDiscussionsCreate()
    {
        $client = App::make(Client::class);

        $all = $client->api('discussions')->create('Foo', 'Bar', 1);

        $array = json_decode($all, true);

        $this->assertArrayHasKey('Discussion', $array);
        $this->assertEquals('Discussion', $array['Type']);
    }
}
