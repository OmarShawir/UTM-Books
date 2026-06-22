<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repository\BookRepository;
use App\Repository\AuditLog;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class BookController
{
    public function __construct(
        private BookRepository $books,
        private AuditLog       $audit
    ) {}

    public function index(Request $req, Response $res): Response
    {
        $q = $req->getQueryParams()['q'] ?? null;
        return $this->json($res, ['data' => $this->books->all($q ? (string)$q : null)]);
    }

    public function show(Request $req, Response $res, array $args): Response
    {
        $book = $this->books->find((int)$args['id']);
        if (!$book) return $this->json($res, ['error' => 'Not found'], 404);
        return $this->json($res, $book);
    }

    public function create(Request $req, Response $res): Response
    {
        $b = (array)$req->getParsedBody();

        $errors = (new Validator())
            ->required('title', 'author', 'year')
            ->field('title',  Validator::nonEmptyString(200), 'title must be 1-200 chars')
            ->field('author', Validator::nonEmptyString(150), 'author must be 1-150 chars')
            ->field('year',   Validator::intRange(1000, (int)date('Y')), 'year must be 1000..now')
            ->field('genre',  Validator::nonEmptyString(80),  'genre must be ≤ 80 chars')
            ->validate($b);

        if ($errors) return $this->json($res, ['errors' => $errors], 400);

        $auth = (array)$req->getAttribute('auth', []);
        $id   = $this->books->create($b, (int)($auth['sub'] ?? 0));
        $this->audit->record('book.create', (int)($auth['sub'] ?? 0), 'books:' . $id, $this->ip($req));

        return $this->json($res, ['id' => $id, 'message' => 'Book created'], 201);
    }

    public function update(Request $req, Response $res, array $args): Response
    {
        $id   = (int)$args['id'];
        $book = $this->books->find($id);
        if (!$book) return $this->json($res, ['error' => 'Not found'], 404);

        $auth    = (array)$req->getAttribute('auth', []);
        $isOwner = (int)$book['created_by'] === (int)($auth['sub'] ?? 0);
        $isAdmin = ($auth['role'] ?? 'member') === 'admin';

        if (!$isOwner && !$isAdmin) {
            return $this->json($res, ['error' => 'Forbidden'], 403);
        }

        $b = (array)$req->getParsedBody();

        $errors = (new Validator())
            ->field('title',  Validator::nonEmptyString(200), 'title must be 1-200 chars')
            ->field('author', Validator::nonEmptyString(150), 'author must be 1-150 chars')
            ->field('year',   Validator::intRange(1000, (int)date('Y')), 'year must be 1000..now')
            ->field('genre',  Validator::nonEmptyString(80),  'genre must be ≤ 80 chars')
            ->validate($b, true); // partial = true

        if ($errors) return $this->json($res, ['errors' => $errors], 400);

        $this->books->update($id, $b);
        $this->audit->record('book.update', (int)($auth['sub'] ?? 0), 'books:' . $id, $this->ip($req));

        return $this->json($res, ['message' => 'Book updated']);
    }

    public function delete(Request $req, Response $res, array $args): Response
    {
        $id   = (int)$args['id'];
        $book = $this->books->find($id);
        if (!$book) return $this->json($res, ['error' => 'Not found'], 404);

        $auth    = (array)$req->getAttribute('auth', []);
        $isAdmin = ($auth['role'] ?? 'member') === 'admin';

        if (!$isAdmin) {
            return $this->json($res, ['error' => 'Forbidden — admin only'], 403);
        }

        $this->books->delete($id);
        $this->audit->record('book.delete', (int)($auth['sub'] ?? 0), 'books:' . $id, $this->ip($req));

        return $this->json($res, ['message' => 'Book deleted']);
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
