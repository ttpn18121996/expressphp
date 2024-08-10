<?php

namespace Expressphp\Routing;

use Closure;
use ReflectionFunction;

class Route
{
    /**
     * Tên của Route.
     */
    protected ?string $name = null;
    
    /**
     * Tiền tố của route.
     */
    protected ?string $prefix = null;

    /**
     * Các middleware của route.
     */
    public array $middlewares = [];

    /**
     * Danh sách tham số cần truyền khi khởi tạo controller.
     */
    public array $params = [];

    public function __construct(
        public array $methods,
        public string $uri,
        public Closure $callback,
    ) {
        $this->uri = $uri != '/' ? trim($uri, '/') : '/';
    }

    /**
     * Cấu hình các thông số cho route.
     */
    public function configRoute(RouteGroup $routeGroup): void
    {
        $this->prefix = $routeGroup->prefix ?? '';

        if (! empty($routeGroup->namespace)) {
            $this->namespace($routeGroup->namespace);
        }

        if (isset($routeGroup->as)) {
            $this->name = $routeGroup->as.$this->name;
        }
    }

    /**
     * Thiết lập tên route.
     */
    public function name(string $name): static
    {
        $this->name = ($this->name ?? '').$name;

        return $this;
    }

    /**
     * Lấy tên route.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Thiếp lập các middleware cho route.
     */
    public function middleware(string|array $middlewares): static
    {
        $this->middlewares = array_merge($this->middlewares, (array) $middlewares);

        return $this;
    }

    /**
     * So sánh URI với URI của route.
     */
    public function compare(string $uri): bool
    {
        $routeUri = $this->getUri();
        $pattern_regex = preg_replace("/\{([a-zA-Z_]+[a-zA-Z_\-]*?)\}/", "(?P<$1>[\w-]*)", $routeUri);
        $pattern_regex = "#^{$pattern_regex}$#";

        if (preg_match($pattern_regex, $uri, $matches)) {
            $this->setParameters($matches);

            return true;
        }

        return false;
    }

    /**
     * Thiết lập tham số.
     */
    private function setParameters(array $matches): void
    {
        foreach ($matches as $key => $value) {
            if (! is_integer($key)) {
                $this->params[$key] = $value;
            }
        }
    }

    /**
     * Lấy URI nguyên mẫu.
     */
    private function getBaseUri(): string
    {
        return ltrim($this->uri, '/');
    }

    /**
     * Lấy URI của route.
     */
    public function getUri(): string
    {
        $uri = trim($this->prefix, '/').'/'.$this->getBaseUri();

        return $uri == '/' ? '/' : trim($uri, '/') ;
    }
}
