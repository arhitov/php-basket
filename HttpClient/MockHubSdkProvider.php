<?php

namespace HttpClient;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use HttpClient\ClientInterface;
use HttpClient\Client;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestFactoryInterface as PsrRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class MockHubSdkProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(PsrClientInterface::class, fn() => Psr18ClientDiscovery::find());
        $this->app->bind(PsrRequestFactoryInterface::class, fn() => Psr17FactoryDiscovery::findRequestFactory());
        $this->app->bind(StreamFactoryInterface::class, fn() => Psr17FactoryDiscovery::findStreamFactory());

        $this->app->bind(ClientInterface::class, Client::class);
    }
}
