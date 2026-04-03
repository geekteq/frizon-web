<?php
/**
 * Test: AmazonFetcher utility logic (no network calls).
 * Run: php tests/test_amazon_shop.php
 */

require_once dirname(__DIR__) . '/app/Services/AmazonFetcher.php';

$fetcher = new AmazonFetcher('frizonse-21', '/tmp/test-uploads');

$passed = 0;
$failed = 0;

function check(string $name, bool $ok, string $got = ''): void
{
    global $passed, $failed;
    if ($ok) {
        printf("PASS: %s\n", $name);
        $passed++;
    } else {
        printf("FAIL: %s%s\n", $name, $got ? " — got: $got" : '');
        $failed++;
    }
}

// --- isAmazonUrl ---
check('amazon.se is valid',     $fetcher->isAmazonUrl('https://www.amazon.se/dp/B08N5WRWNW'));
check('amazon.com is valid',    $fetcher->isAmazonUrl('https://amazon.com/dp/B00TEST'));
check('amazon.co.uk is valid',  $fetcher->isAmazonUrl('https://www.amazon.co.uk/dp/B00TEST'));
check('evil.com not valid',     !$fetcher->isAmazonUrl('https://evil.com/amazon.se'));
check('notamazon.se not valid', !$fetcher->isAmazonUrl('https://notamazon.se/product'));

// --- buildAffiliateUrl: appends tag ---
$url1 = $fetcher->buildAffiliateUrl('https://www.amazon.se/dp/B08N5WRWNW');
check('tag appended',           str_contains($url1, 'tag=frizonse-21'), $url1);

// --- buildAffiliateUrl: replaces existing tag ---
$url2 = $fetcher->buildAffiliateUrl('https://www.amazon.se/dp/B08N5WRWNW?tag=oldtag-20&ref=xyz');
check('old tag replaced',       str_contains($url2, 'tag=frizonse-21') && !str_contains($url2, 'oldtag-20'), $url2);
check('ref param preserved',    str_contains($url2, 'ref=xyz'), $url2);

// --- buildAffiliateUrl: preserves existing query params ---
$url3 = $fetcher->buildAffiliateUrl('https://www.amazon.se/dp/B08N5WRWNW?keywords=test');
check('keywords preserved',     str_contains($url3, 'keywords=test'), $url3);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
