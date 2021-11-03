<?php
declare(strict_types=1);

namespace ClanCats\Station\PHPServer;


class Request
{
    /**
     * The request method
     *
     * @var string
     */
    public string $method;

    /**
     * The requested uri
     *
     * @var string
     */
    public string $uri;

    /**
     * The request params
     *
     * @var array
     */
    public array $parameters = [];

    /**
     * The request params
     *
     * @var array
     */
    public array $headers = [];

    /**
     * Request constructor
     *
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @return void
     */
    public function __construct(string $method, string $uri, array $headers = [])
    {
        $this->headers = $headers;
        $this->method = strtoupper( $method );

        // split uri and parameters string
        $uriExploded = explode( '?', $uri );
        $this->uri = $uriExploded[0] ?? '';
        $params = $uriExploded[1] ?? '';

        // parse the parameters
        if($params !== null) {
            parse_str($params, $this->parameters);
        }
    }

    /**
     * Create new request instance using a string header
     *
     * @param string $header
     * @return Request
     */
    public static function getRequestWithHeaderString(string $header): Request
    {
        $lines = explode("\n", $header);

        // method and uri
        list($method, $uri) = explode(' ', array_shift($lines));

        $headers = [];

        foreach ($lines as $line) {
            // clean the line
            $line = trim($line);

            if (str_contains($line, ': ')) {
                list($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }

        // create new request object
        return new static($method, $uri, $headers);
    }

    /**
     * Return the request method
     *
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Return the request uri
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Return a request header
     *
     * @param string $key
     * @return string|null
     */
    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Return a request parameter
     *
     * @param string $key
     * @return string|null
     */
    public function param(string $key): ?string
    {
        return $this->parameters[$key] ?? null;
    }
}
