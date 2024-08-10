<?php

use Expressphp\Foundation\Container;
use Expressphp\Support\Dumper;

if (! function_exists('app')) {
    /**
     * Khởi tạo Container / khởi tạo đối tượng.
     */
    function app(?string $abstract = null, array $parameters = [])
    {
        $container = Container::getInstance();
    
        if (is_null($abstract)) {
            return $container;
        }

        return $container->make($abstract, $parameters);
    }
}

if (! function_exists('dd')) {
    /**
     * Xuất kết quả trả về của các biến.
     */
    function dd(...$args): void
    {
        foreach ($args as $x) {
            (new Dumper)->dump($x);
        }

        die(1);
    }
}

if (! function_exists('dump')) {
    /**
     * Xuất kết quả trả về của các biến.
     */
    function dump(...$args): void
    {
        foreach ($args as $x) {
            (new Dumper)->dump($x);
        }
    }
}

if (! function_exists('query_string_to_array')) {
    /**
     * Chuyển chuỗi URL hoặc query string sang mảng chứa các tham số.
     */
    function query_string_to_array(string $string = ''): array
    {
        if (empty($string)) {
            return [];
        }

        if (filter_var($string, FILTER_VALIDATE_URL)) {
            $string = (string) parse_url($string, PHP_URL_QUERY);
        }

        if (preg_match('/(.+=.*){1,}/', $string)) {
            parse_str($string, $result);
        } else {
            $result = [];
        }

        return $result;
    }
}

if (! function_exists('value')) {
    /**
     * Trả về giá trị mặc định của giá trị đã cho.
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
