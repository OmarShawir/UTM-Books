<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repository\UserRepository;
use App\Repository\AuditLog;
use App\Validation\Validator;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController
{
    public function __construct(
        private UserRepository $users,
        private AuditLog       $audit
    ) {}

    public function register(Request $req, Response $res): Response
    {
        $b = (array)$req->getParsedBody();

        $errors = (new Validator())
            ->required('name', 'email', 'password')
            ->field('name',     Validator::nonEmptyString(120), 'name must be 1-120 chars')
            ->field('email',    Validator::email(),              'invalid email address')
            ->field('password', Validator::nonEmptyString(72),  'password must be 1-72 chars')
            ->validate($b);

        if ($errors) return $this->json($res, ['errors' => $errors], 400);

        if ($this->users->findByEmail($b['email'])) {
            return $this->json($res, ['error' => 'Email already registered'], 409);
        }

        $id = $this->users->create($b['name'], $b['email'], $b['password']);
        $this->audit->record('register', $id, 'users:' . $id, $this->ip($req));

        return $this->json($res, ['message' => 'Registered successfully'], 201);
    }

    public function login(Request $req, Response $res): Response
    {
        $b    = (array)$req->getParsedBody();
        $user = $this->users->findByEmail($b['email'] ?? '');

        if (!$user || !password_verify($b['password'] ?? '', $user['password_hash'])) {
            $this->audit->record('login.fail', null, $b['email'] ?? '', $this->ip($req));
            return $this->json($res, ['error' => 'Invalid credentials'], 401);
        }

        $now   = time();
        $token = JWT::encode([
            'iss' => $_ENV['JWT_ISSUER'],
            'sub' => $user['id'],
            'role'=> $user['role'],
            'iat' => $now,
            'exp' => $now + (int)$_ENV['JWT_TTL'],
        ], $_ENV['JWT_SECRET'], 'HS256');

        $this->audit->record('login.success', $user['id'], 'users:' . $user['id'], $this->ip($req));

        return $this->json($res, [
            'access_token' => $token,
            'user' => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ]);
    }

    public function me(Request $req, Response $res): Response
    {
        $auth = (array)$req->getAttribute('auth', []);
        $user = $this->users->findById((int)($auth['sub'] ?? 0));
        if (!$user) return $this->json($res, ['error' => 'Not found'], 404);
        return $this->json($res, $user);
    }

    private function json(Response $res, mixed $data, int $status = 200): Response
    {
        $res->getBody()->write(json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ));
        return $res->withHeader('Content-Type', 'application/json; charset=utf-8')
                   ->withStatus($status);
    }

    private function ip(Request $req): string
    {
        return (string)($req->getServerParams()['REMOTE_ADDR'] ?? '');
    }
}
