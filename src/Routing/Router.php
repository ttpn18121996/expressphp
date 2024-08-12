<?php

namespace Expressphp\Routing;

use Closure;
use Expressphp\Http\Request;
use Expressphp\Http\Response;

class Router
{
    /**
     * Danh sách Route đã đăng ký.
     */
    public array $routes = [];

    /**
     * Các phương thức truyền cho router.
     */
    public array $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    /**
     * Danh sách các group đã đăng ký.
     *
     * @var \Expressphp\Routing\RouteGroup[]
     */
    protected array $groupStack = [];

    public function get(string $uri, Closure $callback): Route
    {
        return $this->addRoute(['GET'], $uri, $callback);
    }
    
    public function post(string $uri, Closure $callback): Route
    {
        return $this->addRoute(['POST'], $uri, $callback);
    }
    
    public function put(string $uri, Closure $callback): Route
    {
        return $this->addRoute(['PUT'], $uri, $callback);
    }
    
    public function patch(string $uri, Closure $callback): Route
    {
        return $this->addRoute(['PATCH'], $uri, $callback);
    }
    
    public function delete(string $uri, Closure $callback): Route
    {
        return $this->addRoute(['DELETE'], $uri, $callback);
    }
    
    public function options(string $uri, Closure $callback): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $callback);
    }

    public function all(string $uri, Closure $callback): Route
    {
        return $this->addRoute($this->verbs, $uri, $callback);
    }
    
    public function match(array $methods, string $uri, Closure $callback): Route
    {
        return $this->addRoute($methods, $uri, $callback);
    }

    public function addRoute(array $methods, string $uri, Closure $callback)
    {
        $route = new Route($methods, $uri, $callback);

        if (count($this->groupStack)) {
            $routeGroupOptions = end($this->groupStack);

            if ($routeGroupOptions) {
                $route->configRoute($routeGroupOptions);
            }
        }
        
        foreach ($methods as $method) {
            $this->routes[strtoupper($method)][] = $route;
        }

        return $route;
    }

    public function group(array $options, Closure $callback): void
    {
        $this->updateGroupStack($options);

        $callback($this);

        array_pop($this->groupStack);
    }

    public function updateGroupStack(array $options): void
    {
        $routeGroup = new RouteGroup($options);

        if (empty($this->groupStack)) {
            $this->groupStack[] = $routeGroup;
        } else {
            $lastGroupStack = end($this->groupStack);
            $this->groupStack[] = $routeGroup->merge($lastGroupStack->toArray(), $options);
        }
    }

    public function run(Request $request)
    {
        $uri = $request->uri();
        $httpRequestMethod = $request->method();

        if (in_array($httpRequestMethod, $this->verbs) && isset($this->routes[$httpRequestMethod])) {
            foreach ($this->routes[$httpRequestMethod] as $route) {
                if ($route->compare($uri)) {
                    return app()->call($route->callback, $route->params);
                }
            }
        }

        return app(Response::class)->status(404)->send();
    }

    /**
     * Thực thi closure.
     */
    public function mergeRouter(array|Router $router)
    {
        if ($router instanceof Router) {
            $router = $router->routes;
        }

        //
    }
}
