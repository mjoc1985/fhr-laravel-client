<?php

namespace Mjoc1985\Fhr\Tests;

use Mjoc1985\Fhr\FhrServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            FhrServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('fhr.base_url', 'https://www.bookfhr.com/api');
        $app['config']->set('fhr.token', 'production-token');
        $app['config']->set('fhr.sandbox_base_url', 'https://bookfhr.dev/api');
        $app['config']->set('fhr.sandbox_token', 'sandbox-token');
        $app['config']->set('fhr.source', 'TEST');
        $app['config']->set('fhr.payment_url', 'https://www.bookfhr.com/payment');
        $app['config']->set('fhr.sandbox_payment_url', 'https://bookfhr.dev/payment');
        $app['config']->set('fhr.tenant', 'test-tenant');
        $app['config']->set('fhr.sandbox_tenant', 'sandbox-tenant');
        $app['config']->set('fhr.log_channel', 'null');
    }
}
