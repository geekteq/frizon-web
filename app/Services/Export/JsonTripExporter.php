<?php

declare(strict_types=1);

class JsonTripExporter
{
    /**
     * Generate pretty-printed JSON for a trip with ordered stops.
     *
     * @param array $trip  Trip data
     * @param array $stops Array of stops with place_name, lat, lng, stop_order, stop_type, note
     * @return string JSON content (UTF-8)
     */
    public function export(array $trip, array $stops): string
    {
        $stopsData = [];
        foreach ($stops as $i => $stop) {
            $stopsData[] = [
                'order' => $i + 1,
                'name'  => $stop['place_name'] ?? '',
                'type'  => $stop['stop_type'] ?? '',
                'lat'   => isset($stop['lat']) ? (float) $stop['lat'] : null,
                'lng'   => isset($stop['lng']) ? (float) $stop['lng'] : null,
                'note'  => $stop['note'] ?? '',
            ];
        }

        $payload = [
            'trip'        => [
                'title'      => $trip['title'] ?? '',
                'start_date' => $trip['start_date'] ?? null,
                'end_date'   => $trip['end_date'] ?? null,
            ],
            'stops'       => $stopsData,
            'exported_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c'),
            'source'      => 'Frizon.org',
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /**
     * Send JSON as a downloadable file.
     */
    public function download(array $trip, array $stops): void
    {
        $json     = $this->export($trip, $stops);
        $filename = preg_replace('/[^a-z0-9_-]/i', '_', $trip['title']) . '.json';

        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }
}
