<?php

declare(strict_types=1);

class GpxTripExporter
{
    /**
     * Generate GPX XML for a trip with ordered stops.
     *
     * @param array $trip Trip data
     * @param array $stops Array of stops with place_name, lat, lng, stop_order
     * @return string GPX XML content
     */
    public function export(array $trip, array $stops): string
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');

        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('gpx');
        $xml->writeAttribute('version', '1.1');
        $xml->writeAttribute('creator', 'Frizon.org');
        $xml->writeAttribute('xmlns', 'http://www.topografix.com/GPX/1/1');
        $xml->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->writeAttribute('xsi:schemaLocation', 'http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd');

        // Metadata
        $xml->startElement('metadata');
        $xml->writeElement('name', $trip['title']);
        if (!empty($trip['intro_text'])) {
            $xml->writeElement('desc', $trip['intro_text']);
        }
        $xml->writeElement('author', 'Frizon of Sweden');
        $xml->writeElement('time', date('c'));
        $xml->endElement(); // metadata

        // Waypoints for each stop
        foreach ($stops as $stop) {
            $xml->startElement('wpt');
            $xml->writeAttribute('lat', (string) $stop['lat']);
            $xml->writeAttribute('lon', (string) $stop['lng']);
            $xml->writeElement('name', $stop['place_name']);
            if (!empty($stop['note'])) {
                $xml->writeElement('desc', $stop['note']);
            }
            $xml->writeElement('sym', 'Flag');
            $xml->endElement(); // wpt
        }

        // Route with ordered route points
        $xml->startElement('rte');
        $xml->writeElement('name', $trip['title']);
        foreach ($stops as $stop) {
            $xml->startElement('rtept');
            $xml->writeAttribute('lat', (string) $stop['lat']);
            $xml->writeAttribute('lon', (string) $stop['lng']);
            $xml->writeElement('name', $stop['place_name']);
            $xml->endElement(); // rtept
        }
        $xml->endElement(); // rte

        $xml->endElement(); // gpx
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * Send GPX as a downloadable file.
     */
    public function download(array $trip, array $stops): void
    {
        $gpx = $this->export($trip, $stops);
        $filename = preg_replace('/[^a-z0-9_-]/i', '_', $trip['title']) . '.gpx';

        header('Content-Type: application/gpx+xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($gpx));
        echo $gpx;
        exit;
    }
}
