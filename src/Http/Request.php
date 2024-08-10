<?php

namespace Expressphp\Http;

use Expressphp\Support\Arr;

class Request
{
    protected array $server;

    protected array $headers;

    protected string $method;

    protected array $data;

    protected array $query;

    protected array $files;

    protected Route $route;

    public function __construct()
    {
        $this->bootstrap();
    }

    protected function bootstrap(): void
    {
        $this->initServer();

        $this->initHeaders();

        $strRequestInput = urldecode(file_get_contents('php://input'));
        parse_str($strRequestInput, $requestInput);

        $this->method = $this->server('REQUEST_METHOD');

        if (
            isset($requestInput['_method'])
            && in_array($requestInput['_method'], ['PUT', 'PATCH', 'DELETE', 'OPTIONS'])
            && Str::upper($this->method) == 'POST'
        ) {
            $this->method = $requestInput['_method'];
        }

        if (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            foreach($requestInput as $key => $value) {
                $this->data[$key] = $value;
            }

            if ($_FILES) {
                foreach($_FILES as $inputName => $files) {
                    foreach ($files as $key => $value) {
                        $this->files[$inputName][$key] = $value;
                    }
                }
            }
        }

        $this->query = query_string_to_array($this->server['QUERY_STRING'] ?? '');
    }

    protected function initServer(): void
    {
        foreach ($_SERVER as $key => $value) {
            $this->server[$key] = $value;
        }
    }

    protected function initHeaders(): void
    {
        foreach (getallheaders() as $name => $value) {
            $this->headers['header'][$name] = $value;
        }

        if (isset($this->server['HTTP_CACHE_CONTROL'])) {
            if (str_contains($this->server['HTTP_CACHE_CONTROL'], '=')) {
                $httpCacheControl = explode('=', $this->server['HTTP_CACHE_CONTROL']);

                $this->headers['cacheControl'] = [
                    $httpCacheControl[0] => $httpCacheControl[1],
                ];
            } else {
                $this->headers['cacheControl'] = $this->server['HTTP_CACHE_CONTROL'];
            }
        }
    }

    /**
     * Lấy URI nguyên mẫu.
     */
    public function uri(): string
    {
        $uriGetParam = $this->server('REQUEST_URI', '/');
        $uriGetParam = $uriGetParam != '/' ? trim($uriGetParam, '/') : '/';
        $result = explode('?', $uriGetParam)[0];

        return $result === '' ? '/' : $result;
    }

    public function server(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->server;
        }

        return Arr::get($this->server, strtoupper($key), $default);
    }
    
    public function header(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->headers['header'];
        }

        return Arr::get($this->headers['header'], $key, $default);
    }

    public function url(): string
    {
        return $this->server('REQUEST_SCHEME') . '://' . $this->server('SERVER_NAME') . $this->server('REQUEST_URI');
    }

    public function method(): string
    {
        return $this->method;
    }
}
