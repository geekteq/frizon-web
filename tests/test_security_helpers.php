<?php
/**
 * Test: security helper proxy matching logic.
 * Run: php tests/test_security_helpers.php
 */

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void {
    global $passed, $failed;
    if ($condition) { echo "PASS: {$name}\n"; $passed++; }
    else { echo "FAIL: {$name}\n"; $failed++; }
}

require_once dirname(__DIR__) . '/app/Helpers/security.php';

check('Exact IPv4 proxy match', app_ip_matches_proxy('192.168.1.10', '192.168.1.10') === true);
check('IPv4 CIDR positive match', app_ip_matches_proxy('10.0.5.12', '10.0.0.0/8') === true);
check('IPv4 CIDR negative match', app_ip_matches_proxy('192.168.5.12', '10.0.0.0/8') === false);
check('IPv6 exact match', app_ip_matches_proxy('2001:db8::1', '2001:db8::1') === true);
check('IPv6 CIDR positive match', app_ip_matches_proxy('2001:db8::abcd', '2001:db8::/32') === true);
check('Invalid CIDR rejected', app_ip_matches_proxy('10.0.0.1', '10.0.0.0/99') === false);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
