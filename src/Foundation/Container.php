<?php

namespace Expressphp\Foundation;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class Container
{
    /**
     * Danhs sách các kiểu dữ liệu.
     */
    protected const array TYPE_VARIABLE = ['int', 'string', 'boolean', 'array', 'float', ''];

    /**
     * Instance singleton của Container.
     */
    public static Container $instance;

    /**
     * Danh sách các đối tượng đã đăng ký cho Container.
     */
    protected array $bindings = [];

    /**
     * Danh sách các instance đã được khởi tạo, dùng cho singleton.
     */
    protected array $instances = [];

    /**
     * Danh sách chứa danh sách các tham số dùng để khởi tạo đối tượng khi resolve.
     */
    protected array $with = [];

    public function bind(string $abstract, string|Closure|null $concrete = null, bool $shared = false): static
    {
        unset($this->instances[$abstract]);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (! $concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        if (! $shared) {
            $this->bindings[$abstract] = compact('concrete', 'shared');
        } else {
            $this->bindings[$abstract] ??= compact('concrete', 'shared');
        }

        return $this;
    }

    /**
     * Tạo Closure để sử dụng khi build. Khi Container build, nó sẽ thực thi Closure này.
     */
    public function getClosure(string $abstract, string $concrete): Closure
    {
        return function (Container $container, array $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve($concrete, $parameters);
        };
    }

    /**
     * Tương tự như bind nhưng bind 1 lần duy nhất, không ghi đè những gì đã bind trước đó.
     */
    public function singleton(string $abstract, string|Closure|null $concrete = null): static
    {
        return $this->bind($abstract, $concrete, true);
    }

    /**
     * Thực hiện tự động truyền các dependency và khởi tạo đối tượng.
     */
    public function build(string|Closure $concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        $reflector = new ReflectionClass($concrete);
        $dependencies = $this->resolveDependencies($reflector);

        return $reflector->newInstanceArgs($dependencies);
    }

    public function getLastParameterOverride(): array
    {
        return count($this->with) ? end($this->with) : [];
    }

    /**
     * Phân tích lấy danh sách các dependencies (tham số các lớp phụ thuộc).
     */
    public function resolveDependencies(ReflectionClass $reflector)
    {
        if (! $reflector->isInstantiable()) {
            throw new ReflectionException('Không thể thực hiện khởi tạo');
        }

        if (! ($constructor = $reflector->getConstructor())) {
            return [];
        }

        return $this->resolveParameters($constructor->getParameters(), $this->getLastParameterOverride());
    }

    /**
     * Lấy danh sách tham số và ghi đè nếu có.
     */
    public function resolveParameters(array $parameters, array $parameterOverride = []): array
    {
        $instances = [];

        foreach ($parameters as $parameter) {
            if (isset($parameterOverride[$parameter->getName()])) {
                $instances[] = $parameterOverride[$parameter->getName()];
                continue;
            }

            $parameterType = $parameter->getType();

            if (! $parameterType) {
                if (! $parameter->isDefaultValueAvailable()) {
                    throw new RuntimeException('Không xác định được kiểu dữ liệu của tham số truyền.');
                }

                $instances[] = $parameter->getDefaultValue();
            } elseif (in_array($parameterType->getName(), static::TYPE_VARIABLE)) {
                if ($parameter->isDefaultValueAvailable()) {
                    $instances[] = $parameter->getDefaultValue();
                } elseif ($parameterType->allowsNull()) {
                    $instances[] = null;
                }
            } else {
                $resolved = $this->resolve($parameterType->getName());
                $instances[] = ($resolved instanceof Closure) ? $resolved($this) : $resolved;
            }
        }

        return $instances;
    }

    /**
     * Giải quyết các điều kiện để khởi tạo đối tượng và trả về đối tượng đã khởi tạo.
     */
    public function resolve(string $abstract, array $parameters = [])
    {
        $concrete = $this->getConcrete($abstract);

        $this->with[] = $parameters;

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            $object = $this->resolve($concrete);
        }

        if ($this->isShared($abstract)) {
            $this->instances[] = $object;
        }

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     */
    protected function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Xác định xem concrete có build được không.
     */
    protected function isBuildable($concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Kiểm tra đối tượng có phải singleton.
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract])
                || (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Tương tự như resolve.
     */
    public function make(string $abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Thực thi phương thức của một đối tượng hoặc một Closure.
     */
    public function call(string|array|Closure $callback, array $parameterOverride = [])
    {
        if ($callback instanceof Closure) {
            $method = new ReflectionFunction($callback);
            $action = $callback;
        } else {
            [$class, $action] = is_string($callback) ? explode($callback, '@') : $callback;

            $instance = is_string($class) ? $this->make($class) : $class;

            $method = new ReflectionMethod($instance, $action);
            $action = [$instance, $action];
        }

        $parameters = $this->resolveParameters($method->getParameters(), $parameterOverride);

        return call_user_func_array($action, $parameters);
    }

    /**
     * Tương tự call.
     */
    public function action($callback, array $parameterOverride = [])
    {
        return $this->call($callback, $parameterOverride);
    }

    /**
     * Khởi tạo singleton.
     */
    public static function getInstance(): static
    {
        if (! isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
