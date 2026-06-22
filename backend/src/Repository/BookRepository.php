<?php
declare(strict_types=1);

namespace App\Repository;

final class BookRepository
{
    public function __construct(private \PDO $pdo) {}

    public function all(?string $q = null): array
    {
        if ($q) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM books WHERE title LIKE :q OR author LIKE :q ORDER BY id DESC'
            );
            $stmt->execute([':q' => "%$q%"]);
        } else {
            $stmt = $this->pdo->query('SELECT * FROM books ORDER BY id DESC');
        }
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM books WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $b, int $createdBy): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO books (title, author, year, genre, created_by) VALUES (:title, :author, :year, :genre, :owner)'
        );
        $stmt->execute([
            ':title'  => trim($b['title']),
            ':author' => trim($b['author']),
            ':year'   => (int)$b['year'],
            ':genre'  => trim($b['genre'] ?? 'Uncategorised'),
            ':owner'  => $createdBy,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $b): void
    {
        $fields = [];
        $params = [':id' => $id];
        foreach (['title', 'author', 'year', 'genre'] as $col) {
            if (array_key_exists($col, $b)) {
                $fields[]      = "$col = :$col";
                $params[":$col"] = $col === 'year' ? (int)$b[$col] : trim((string)$b[$col]);
            }
        }
        if (!$fields) return;
        $this->pdo->prepare('UPDATE books SET ' . implode(', ', $fields) . ' WHERE id = :id')
                  ->execute($params);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM books WHERE id = :id')->execute([':id' => $id]);
    }
}
