<?php

namespace Expressphp\Routing;

use Closure;
use Expressphp\Http\Request;
use Expressphp\Http\Response;
use ReflectionFunction;

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
     */
    protected array $groupStack = [];

    public function get(string $uri, Closure $callback): Route
    {
        return $this->addRoute(['GET'], $uri, $callback);
    }

    public function addRoute(array $methods, string $uri, Closure $callback)
    {
        $route = new Route($methods, $uri, $callback);

        if (count($this->groupStack)) {
            $routeGroupOptions = end($this->groupStack);
            $route->configRoute($routeGroupOptions);
        }
        
        foreach ($methods as $method) {
            $this->routes[$method][] = $route;
        }

        return $route;
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
    private function executeRouteCallback(\Closure|callable $callback, array $args): void
    {
        $reflection = new ReflectionFunction($callback);
        $newParameters = [];

        foreach ($reflection->getParameters() as $parameter) {
            if (! is_null($parameter->getType())) {
                $newParameters[] = app($parameter->getType()->getName());
            } elseif (isset($args[$parameter->name])) {
                $newParameters[] = $args[$parameter->name];
            }
        }

        $parameters = $newParameters;

        app(Response::class, ['data' => call_user_func_array($callback, $parameters)])->send();
    }
}
