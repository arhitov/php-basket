<?php

namespace Tests\Unit;

use HttpClient\ClientInterface;
use Tests\ClientFaker;
use Tests\TestCase;

class MainTest extends TestCase
{
    private ClientInterface|ClientFaker $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

//        $this->httpClient = $this->app->make(ClientInterface::class);
        $this->httpClient = $this->app->make(Client::class);
    }

    public function testProvider()
    {
        $this->assertInstanceOf(
            ClientInterface::class,
            $this->app->make(ClientInterface::class),
        );
    }

    public function testHttpClientGet(): void
    {
        $this->assertTrue($this->httpClient instanceof Client);

        $this->httpClient::setCallbackGet(static function(string $url) {
            return ['body' => ['test' => 'ok']];
        });

        $response = $this->httpClient->get('https://localhost/get?project=test');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"test":"ok"}', $response->getBody()->getContents());
    }

    public function testHttpClientPost(): void
    {
        $this->assertTrue($this->httpClient instanceof Client);

        $this->httpClient::setCallbackPost(static function(string $url, array $data) {
            return ['body' => ['request' => $data]];
        });

        $response = $this->httpClient->post(
            'https://localhost/post',
            [
                'project' => 'test',
                'data' => [
                    'foo' => 'bar',
                ]
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            '{"request":{"project":"test","data":{"foo":"bar"}}}',
            $response->getBody()->getContents()
        );
    }
}
