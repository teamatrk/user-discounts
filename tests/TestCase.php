<?php

namespace teamatrk\UserDiscounts\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use teamatrk\UserDiscounts\DiscountServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [DiscountServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}