<?php

namespace HttpClient;

use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestFactoryInterface as PsrRequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Интерфейс адаптера клиента для работы с HTTP
 */
interface ClientInterface
{
    /**
     * @param \Psr\Http\Client\ClientInterface $httpClient
     * @param \Psr\Http\Message\RequestFactoryInterface $requestFactory
     * @param \Psr\Http\Message\StreamFactoryInterface $streamFactory
     */
    public function __construct(
        PsrClientInterface         $httpClient,
        PsrRequestFactoryInterface $requestFactory,
        StreamFactoryInterface     $streamFactory,
    );

    /**
     * Отправка запроса методом GET
     *
     * @param string $url
     * @param mixed $sink В какой файл сохранять контент
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get(string $url, mixed $sink = null): ResponseInterface;

    /**
     * Отправка запроса методом HEAD
     *
     * @param string $url
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function head(string $url): ResponseInterface;

    /**
     * Отправка запроса методом POSt
     *
     * @param string $url
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post(string $url, array $data): ResponseInterface;
}
