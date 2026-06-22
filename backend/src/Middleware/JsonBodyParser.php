<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JsonBodyParser implements MiddlewareInterface
{
    public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
    {
        $ct = $req->getHeaderLine('Content-Type');
        if (str_contains($ct, 'application/json')) {
            $body = (string) $req->getBody();
            if ($body !== '') {
                $req = $req->withParsedBody(json_decode($body, true));
            }
        }
        return $handler->handle($req);
    }
}
