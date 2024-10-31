<?php

namespace Tests;

use GuzzleHttp\Psr7\Response;
use HttpClient\ClientInterface;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestFactoryInterface as PsrRequestFactoryInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ClientFaker implements ClientInterface
{
    private static \Closure $callbackGet;
    private static \Closure $callbackHead;
    private static \Closure $callbackPost;

    /**
     * @param callable{url: string} $callback
     * @return void
     */
    public static function setCallbackGet(callable $callback): void
    {
        self::$callbackGet = $callback;
    }

    public static function setCallbackHead(callable $callback): void
    {
        self::$callbackHead = $callback;
    }

    public static function setCallbackPost(callable $callback): void
    {
        self::$callbackPost = $callback;
    }

    public function __construct(
        protected readonly PsrClientInterface         $httpClient,
        protected readonly PsrRequestFactoryInterface $requestFactory,
        protected readonly StreamFactoryInterface     $streamFactory,
    ) {
    }

    public function get(string $url, mixed $sink = null): PsrResponseInterface
    {
        $callbackGet = self::$callbackGet;
        [
            'status' => $status,
            'body' => $body,
        ] = $callbackGet($url, $sink) + ['status' => 200, 'body' => ''];

        return new Response(
            status: $status,
            body: match (true) {
                is_array($body) => $this->streamFactory->createStream(json_encode($body, JSON_UNESCAPED_UNICODE)),
                default => $body
            },
        );
    }

    public function head(string $url): PsrResponseInterface
    {
        $callbackHead = self::$callbackHead;
        [
            'status' => $status,
        ] = $callbackHead($url) + ['status' => 200];

        return new Response(
            status: $status,
        );
    }

    public function post(string $url, array $data): PsrResponseInterface
    {
        $callbackPost = self::$callbackPost;
        [
            'status' => $status,
            'body'   => $body,
        ] = $callbackPost($url, $data) + ['status' => 200, 'body' => ''];

        return new Response(
            status: $status,
            body: match (true) {
                is_array($body) => $this->streamFactory->createStream(json_encode($body, JSON_UNESCAPED_UNICODE)),
                default => $body
            },
        );
    }
}
