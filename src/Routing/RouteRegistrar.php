<?php

namespace Expressphp\Routing;

class RouteRegistrar
{
    public array $middlewares = [];
    
    public string $prefix = '';

    public string $name = '';

    public function __construct(
        protected Router $router,
    ) {}

    public function middleware(array|string $middlewares): static
    {
        if (is_string($middlewares)) {
            $middlewares = [$middlewares];
        }

        $this->middlewares[] = array_merge($this->middlewares, $middlewares);

        return $this;
    }
    
    public function prefix(string $prefix): static
    {
        $this->prefix = $this->prefix.'/'.ltrim($prefix, '/');

        return $this;
    }
    
    public function name(string $name): static
    {
        $this->name = $this->name.$name;

        return $this;
    }
}
