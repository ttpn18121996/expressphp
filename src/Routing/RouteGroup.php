<?php

namespace Expressphp\Routing;

class RouteGroup
{
    /**
     * Khởi tạo đối tượng.
     */
    public function __construct(
        protected array $options = [
            'middlewares' => [],
            'namespace' => '',
            'prefix' => '',
            'as' => '',
        ],
    ) {}

    /**
     * Thiết lập prefix.
     */
    public function prefix(string $prefix = ''): static
    {
        $this->options['prefix'] = ($this->options['prefix'] ?? '').'/'.ltrim($prefix, '/');

        return $this;
    }
}
