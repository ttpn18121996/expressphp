<?php

namespace Expressphp\Foundation;

use Expressphp\Routing\Router;
use Expressphp\Support\Facades\Route;

class Application
{
    public static Application $instance;

    public Router $router;

    public function __construct(
        protected Container $container,
    ) {
        $this->router = $container->make(Router::class);
    }

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

    public function use(string $key, $value)
    {
        if ($value instanceof Router) {
            $this->router->prefix($key)->group($value);
        }
    }

    public function run()
    {
        // $this->router->route
        $this->container->action([Route::getFacadeRoot(), 'run']);
    }
}
