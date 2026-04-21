<?php

declare(strict_types=1);

class FrizzeEvent
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(int $vehicleId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT e.*, d.title AS document_title
            FROM frizze_events e
            LEFT JOIN frizze_documents d ON d.id = e.document_id
            WHERE e.vehicle_id = ?
            ORDER BY e.event_date DESC, e.event_time DESC, e.id DESC
        ');
        $stmt->execute([$vehicleId]);

        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT e.*, d.title AS document_title
            FROM frizze_events e
            LEFT JOIN frizze_documents d ON d.id = e.document_id
            WHERE e.id = ?
        ');
        $stmt->execute([$id]);
        $event = $stmt->fetch();

        return $event ? $this->hydrate($event) : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO frizze_events (
                vehicle_id, document_id, event_type, event_date, event_time, title,
                supplier, odometer_km, amount_total, currency, description, details_json, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['vehicle_id'],
            $data['document_id'] ?? null,
            $data['event_type'],
            $data['event_date'],
            $data['event_time'] ?? null,
            $data['title'],
            $data['supplier'] ?? null,
            $data['odometer_km'] ?? null,
            $data['amount_total'] ?? null,
            $data['currency'] ?? 'SEK',
            $data['description'] ?? null,
            $this->encodeDetails($data['details'] ?? []),
            $data['created_by'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE frizze_events
            SET document_id = ?,
                event_type = ?,
                event_date = ?,
                event_time = ?,
                title = ?,
                supplier = ?,
                odometer_km = ?,
                amount_total = ?,
                currency = ?,
                description = ?,
                details_json = ?,
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $data['document_id'] ?? null,
            $data['event_type'],
            $data['event_date'],
            $data['event_time'] ?? null,
            $data['title'],
            $data['supplier'] ?? null,
            $data['odometer_km'] ?? null,
            $data['amount_total'] ?? null,
            $data['currency'] ?? 'SEK',
            $data['description'] ?? null,
            $this->encodeDetails($data['details'] ?? []),
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM frizze_events WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function hydrate(array $event): array
    {
        $event['details'] = [];

        if (!empty($event['details_json'])) {
            $decoded = json_decode((string) $event['details_json'], true);
            $event['details'] = is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
        }

        return $event;
    }

    private function encodeDetails(array $details): ?string
    {
        $clean = array_values(array_filter(array_map(
            static fn($line) => trim((string) $line),
            $details
        )));

        if ($clean === []) {
            return null;
        }

        return json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
