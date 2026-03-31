<?php

declare(strict_types=1);

class CsvTripExporter
{
    // Swedish labels for stop types
    private const STOP_TYPE_LABELS = [
        'stellplatz'   => 'Ställplats',
        'camping'      => 'Camping',
        'wild_camping' => 'Fricamping',
        'fika'         => 'Fika',
        'lunch'        => 'Lunch',
        'dinner'       => 'Middag',
        'breakfast'    => 'Frukost',
        'sight'        => 'Sevärdhet',
        'shopping'     => 'Shopping',
    ];

    /**
     * Generate semicolon-separated CSV content for a trip.
     * Includes UTF-8 BOM for Excel compatibility.
     *
     * @param array $trip Trip data
     * @param array $stops Array of stops with place_name, lat, lng, stop_order, stop_type, note
     * @return string CSV content
     */
    public function export(array $trip, array $stops): string
    {
        $lines = [];

        // UTF-8 BOM so Excel opens the file correctly
        $bom = "\xEF\xBB\xBF";

        // Header row
        $lines[] = 'Nr;Plats;Typ;Latitud;Longitud;Anteckning';

        foreach ($stops as $i => $stop) {
            $nr        = $i + 1;
            $plats     = $this->escape($stop['place_name'] ?? '');
            $typ       = $this->escape($this->labelForType($stop['stop_type'] ?? ''));
            $lat       = $stop['lat'] ?? '';
            $lng       = $stop['lng'] ?? '';
            $anteckning = $this->escape($stop['note'] ?? '');

            $lines[] = implode(';', [$nr, $plats, $typ, $lat, $lng, $anteckning]);
        }

        return $bom . implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Send CSV as a downloadable file.
     */
    public function download(array $trip, array $stops): void
    {
        $csv      = $this->export($trip, $stops);
        $filename = preg_replace('/[^a-z0-9_-]/i', '_', $trip['title']) . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv));
        echo $csv;
        exit;
    }

    /**
     * Translate internal stop_type value to Swedish label.
     */
    private function labelForType(string $type): string
    {
        return self::STOP_TYPE_LABELS[$type] ?? $type;
    }

    /**
     * Escape a value for CSV: wrap in double quotes if it contains
     * a semicolon, double quote, or newline, and escape inner quotes.
     */
    private function escape(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $needsQuoting = str_contains($value, ';')
            || str_contains($value, '"')
            || str_contains($value, "\n")
            || str_contains($value, "\r");

        if ($needsQuoting) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }
}
