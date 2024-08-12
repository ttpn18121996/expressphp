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

    /**
     * Thực thi callback.
     */
    public function execute(Closure $callback): void
    {
        $callback();
    }

    /**
     * Thiết lập name.
     */
    public function name(string $name = ''): static
    {
        $this->options['as'] = ($this->options['as'] ?? '').$name;

        return $this;
    }

    public function merge(array $oldData, array $newData)
    {
        $this->options['middlewares'] = array_merge($oldData['middlewares'] ?? [], $newData['middlewares'] ?? []);
        $this->options['prefix'] = ($oldData['prefix'] ?? '').($newData['prefix'] ?? '');
        $this->options['as'] = ($oldData['as'] ?? '').($newData['as'] ?? '');

        return $this;
    }

    public function toArray(): array
    {
        return $this->options;
    }

    public function __get(string $key)
    {
        if (array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }

        return null;
    }
}
