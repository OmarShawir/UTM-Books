<?php
declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

final class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $req, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = $req->getHeaderLine('Authorization');
        if (!str_starts_with($auth, 'Bearer ')) {
            return $this->unauthorized('Missing token');
        }

        $token = substr($auth, 7);
        try {
            $payload = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $req = $req->withAttribute('auth', (array)$payload);
        } catch (\Throwable $e) {
            return $this->unauthorized('Invalid or expired token');
        }

        return $handler->handle($req);
    }

    private function unauthorized(string $msg): ResponseInterface
    {
        $res = new SlimResponse(401);
        $res->getBody()->write(json_encode(['error' => $msg]));
        return $res->withHeader('Content-Type', 'application/json');
    }
}
