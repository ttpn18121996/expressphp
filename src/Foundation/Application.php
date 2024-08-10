<?php

namespace Expressphp\Foundation;

use Expressphp\Routing\Router;
use Expressphp\Support\Facades\Route;

class Application
{
    public static Application $instance;

    public function __construct(
        protected Container $container,
    ) {}

    public static function getInstance(): static
    {
        if (! isset(static::$instance)) {
            static::$instance = new static(Container::getInstance());
        }

        return static::$instance;
    }

    public function bindDefaultClass()
    {
        $this->container->singleton('route', Router::class);
    }

    public function create()
    {
        $this->bindDefaultClass();

        return $this;
    }

    public function run()
    {
        $this->container->action([Route::getFacadeRoot(), 'run']);
    }
}
