<?php

declare(strict_types=1);

class GoogleMapsLinkExporter
{
    /**
     * Generate a plain-text file with one Google Maps link per stop
     * plus a combined route link at the end.
     *
     * @param array $trip  Trip data
     * @param array $stops Array of stops with place_name, lat, lng
     * @return string Plain-text content (UTF-8)
     */
    public function export(array $trip, array $stops): string
    {
        $title     = $trip['title'] ?? '';
        $separator = str_repeat('=', 32);

        $lines   = [];
        $lines[] = 'Resans hållplatser: ' . $title;
        $lines[] = $separator;
        $lines[] = '';

        foreach ($stops as $i => $stop) {
            $nr   = $i + 1;
            $name = $stop['place_name'] ?? '';
            $lat  = $stop['lat'] ?? '';
            $lng  = $stop['lng'] ?? '';

            $lines[] = $nr . '. ' . $name;
            $lines[] = '   https://www.google.com/maps?q=' . $lat . ',' . $lng;
            $lines[] = '';
        }

        // Build combined route link if there are at least 2 stops
        if (count($stops) >= 2) {
            $waypoints = array_map(
                fn($s) => $s['lat'] . ',' . $s['lng'],
                $stops
            );

            $lines[] = 'Komplett rutt:';
            $lines[] = 'https://www.google.com/maps/dir/' . implode('/', $waypoints);
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Send the plain-text links file as a download.
     */
    public function download(array $trip, array $stops): void
    {
        $content  = $this->export($trip, $stops);
        $filename = preg_replace('/[^a-z0-9_-]/i', '_', $trip['title']) . '_google_maps.txt';

        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }
}
