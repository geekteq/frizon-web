<?php

declare(strict_types=1);

class AmazonProduct
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** All products, optionally filtered by category, published status, or search. */
    public function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM amazon_products';
        $where = [];
        $params = [];

        if (isset($filters['is_published'])) {
            $where[] = 'is_published = ?';
            $params[] = (int) $filters['is_published'];
        }
        if (!empty($filters['category'])) {
            $where[] = 'category = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'title LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // Featured first, then sort_order, then newest
        $sql .= ' ORDER BY is_featured DESC, sort_order ASC, updated_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allPublished(): array
    {
        return $this->all(['is_published' => 1]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM amazon_products WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM amazon_products WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO amazon_products
                (slug, title, amazon_url, affiliate_url, image_path, amazon_description,
                 our_description, seo_title, seo_description, category, sort_order,
                 is_featured, is_published)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['slug'],
            $data['title'],
            $data['amazon_url'],
            $data['affiliate_url'],
            $data['image_path'] ?? null,
            $data['amazon_description'] ?? null,
            $data['our_description'] ?? null,
            $data['seo_title'] ?? null,
            $data['seo_description'] ?? null,
            $data['category'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            (int) ($data['is_featured'] ?? 0),
            (int) ($data['is_published'] ?? 0),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE amazon_products SET
                title = ?, amazon_url = ?, affiliate_url = ?, image_path = ?,
                amazon_description = ?, our_description = ?, seo_title = ?,
                seo_description = ?, category = ?, sort_order = ?,
                is_featured = ?, is_published = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $data['title'],
            $data['amazon_url'],
            $data['affiliate_url'],
            $data['image_path'] ?? null,
            $data['amazon_description'] ?? null,
            $data['our_description'] ?? null,
            $data['seo_title'] ?? null,
            $data['seo_description'] ?? null,
            $data['category'] ?? null,
            (int) ($data['sort_order'] ?? 0),
            (int) ($data['is_featured'] ?? 0),
            (int) ($data['is_published'] ?? 0),
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM amazon_products WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Latest N published products, for homepage teaser. */
    public function latestPublished(int $limit = 3): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM amazon_products
            WHERE is_published = 1
            ORDER BY created_at DESC
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /** All distinct categories used by published products. */
    public function publishedCategories(): array
    {
        $stmt = $this->pdo->query('
            SELECT DISTINCT category
            FROM amazon_products
            WHERE is_published = 1 AND category IS NOT NULL AND category != \'\'
            ORDER BY category ASC
        ');
        return array_column($stmt->fetchAll(), 'category');
    }

    /** Update only the provided columns. Keys must be trusted/whitelisted by caller. */
    public function updatePartial(int $id, array $data): void
    {
        $allowed = ['image_path', 'amazon_description', 'our_description',
                    'seo_title', 'seo_description', 'is_published', 'is_featured'];
        $sets = [];
        $vals = [];
        foreach ($data as $col => $val) {
            if (!in_array($col, $allowed, true)) {
                continue;
            }
            $sets[] = $col . ' = ?';
            $vals[] = $val;
        }
        if (!$sets) {
            return;
        }
        $vals[] = $id;
        $this->pdo->prepare(
            'UPDATE amazon_products SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = ?'
        )->execute($vals);
    }

    /** Toggle is_published for one product. */
    public function togglePublished(int $id): void
    {
        $this->pdo->prepare('
            UPDATE amazon_products SET is_published = 1 - is_published, updated_at = NOW()
            WHERE id = ?
        ')->execute([$id]);
    }

    /** All distinct categories (published + unpublished), for admin dropdowns. */
    public function allCategories(): array
    {
        $stmt = $this->pdo->query('
            SELECT DISTINCT category
            FROM amazon_products
            WHERE category IS NOT NULL AND category != \'\'
            ORDER BY category ASC
        ');
        return array_column($stmt->fetchAll(), 'category');
    }

    /** Published products in the same category, excluding the given id. */
    public function relatedPublished(string $category, int $excludeId, int $limit = 3): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM amazon_products
            WHERE is_published = 1 AND category = ? AND id != ?
            ORDER BY is_featured DESC, sort_order ASC
            LIMIT ?
        ');
        $stmt->execute([$category, $excludeId, $limit]);
        return $stmt->fetchAll();
    }

    /** Generate a URL-safe slug from a title. */
    public static function generateSlug(string $title): string
    {
        $slug = mb_strtolower($title, 'UTF-8');
        $slug = str_replace(['å', 'ä', 'ö', 'Å', 'Ä', 'Ö'], ['a', 'a', 'o', 'a', 'a', 'o'], $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}
