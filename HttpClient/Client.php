<?php

namespace HttpClient;

use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestFactoryInterface as PsrRequestFactoryInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @inheritDoc
 */
readonly class Client implements ClientInterface
{
    /** @inheritDoc */
    public function __construct(
        private PsrClientInterface         $httpClient,
        private PsrRequestFactoryInterface $requestFactory,
        private StreamFactoryInterface     $streamFactory,
    ) {
    }

    /**
     * @inheritDoc
     *
     * @param string $url
     * @param mixed $sink
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @see \HttpClient\ClientInterface::get()
     */
    public function get(string $url, mixed $sink = null): PsrResponseInterface
    {
        $response = $this->httpClient->sendRequest(
            $this->requestFactory->createRequest(
                'GET',
                $url,
            )
        );

        if (! empty($sink)) {
            $contents = (string) $response->getBody();
            if (\is_resource($sink)) {
                \fwrite($sink, $contents);
            } elseif (\is_string($sink)) {
                \file_put_contents($sink, $contents);
            } elseif ($sink instanceof StreamInterface) {
                $sink->write($contents);
            }
        }

        return $response;
    }

    /**
     * @inheritDoc
     *
     * @param string $url
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @see \HttpClient\ClientInterface::head()
     */
    public function head(string $url, array $options = []): PsrResponseInterface
    {
        return $this->httpClient->sendRequest(
            $this->requestFactory->createRequest(
                'HEAD',
                $url,
            )
        );
    }

    /**
     * @inheritDoc
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function post(string $url, array $data): PsrResponseInterface
    {
        // Создаем тело запроса
        $body = $this->streamFactory->createStream(json_encode($data, JSON_UNESCAPED_UNICODE));

        // Создаем объект запроса
        $request = $this->requestFactory
            ->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);

        return $this->httpClient->sendRequest($request);
    }
}
