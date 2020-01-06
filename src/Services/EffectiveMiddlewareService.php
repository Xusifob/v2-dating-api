<?php

namespace App\Services;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;



class EffectiveMiddlewareService
{
    /**
     * @var Callable
     */
    protected $nextHandler;
    /**
     * @var string
     */
    protected $headerName;
    /**
     * @param callable $nextHandler
     * @param string   $headerName  The header name to use for storing effective url
     */
    public function __construct(
        callable $nextHandler,
        $headerName = 'X-GUZZLE-EFFECTIVE-URL'
    ) {
        $this->nextHandler = $nextHandler;
        $this->headerName = $headerName;
    }
    /**
     * Inject effective-url header into response.
     *
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return RequestInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->nextHandler;
        return $fn($request, $options)->then(function (ResponseInterface $response) use ($request, $options) {
            return $response->withAddedHeader($this->headerName, $request->getUri()->__toString());
        });
    }
    /**
     * Prepare a middleware closure to be used with HandlerStack
     *
     * @param string $headerName The header name to use for storing effective url
     *
     * @return \Closure
     */
    public static function middleware($headerName = 'X-GUZZLE-EFFECTIVE-URL')
    {
        return function (callable $handler) use (&$headerName) {
            return new static($handler, $headerName);
        };
    }
}