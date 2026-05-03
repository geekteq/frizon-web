<?php

declare(strict_types=1);

class VisitProduct
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * All published Amazon products linked to a visit, ordered by sort_order.
     * Returns the same shape as AmazonProduct::getByPlaceId.
     */
    public function findByVisit(int $visitId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ap.*
            FROM amazon_products ap
            JOIN visit_products vp ON vp.product_id = ap.id
            WHERE vp.visit_id = ? AND ap.is_published = 1
            ORDER BY vp.sort_order ASC, ap.title ASC
        ');
        $stmt->execute([$visitId]);
        return $stmt->fetchAll();
    }

    /**
     * Replace all product links for a visit. Pass [] to clear.
     */
    public function syncForVisit(int $visitId, array $productIds): void
    {
        $this->pdo->prepare('DELETE FROM visit_products WHERE visit_id = ?')
                  ->execute([$visitId]);

        if (empty($productIds)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO visit_products (visit_id, product_id, sort_order) VALUES (?, ?, ?)'
        );
        foreach (array_values($productIds) as $order => $productId) {
            $stmt->execute([$visitId, (int) $productId, $order]);
        }
    }

    /**
     * Loads visit_products grouped by visit_id for a list of visits.
     * Returns: [visit_id => [product_row, ...], ...]
     */
    public function findByVisitIds(array $visitIds): array
    {
        if (empty($visitIds)) return [];

        $placeholders = implode(',', array_fill(0, count($visitIds), '?'));
        $stmt = $this->pdo->prepare("
            SELECT vp.visit_id, ap.*
            FROM amazon_products ap
            JOIN visit_products vp ON vp.product_id = ap.id
            WHERE vp.visit_id IN ($placeholders) AND ap.is_published = 1
            ORDER BY vp.visit_id, vp.sort_order ASC, ap.title ASC
        ");
        $stmt->execute(array_map('intval', $visitIds));

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $vid = (int) $row['visit_id'];
            unset($row['visit_id']);
            $grouped[$vid][] = $row;
        }
        return $grouped;
    }
}
