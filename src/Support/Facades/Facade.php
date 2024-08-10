<?php

namespace Expressphp\Support\Facades;

abstract class Facade
{
    /**
     * Biến lưu các đối tượng khởi tạo.
     *
     * @var array
     */
    protected static $resolvedInstance;

    /**
     * Lấy đối tượng gốc.
     */
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Khởi tạo đối tượng gốc.
     */
    protected static function resolveFacadeInstance(string|object $name)
    {
        if (is_object($name)) {
            return $name;
        }

        if (! isset(static::$resolvedInstance[$name])) {
            static::$resolvedInstance[$name] = app($name);
        }

        return static::$resolvedInstance[$name];
    }

    public static function __callStatic(string $method, array $args)
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw new RuntimeException('Facade chưa được thiết lập.');
        }

        return call_user_func_array([$instance, $method], $args);
    }
}
