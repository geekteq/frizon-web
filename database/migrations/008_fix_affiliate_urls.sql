-- Fix affiliate URLs to canonical Amazon format:
-- https://www.amazon.se/dp/{ASIN}/ref=nosim?tag=frizonofswede-21
--
-- This format is required by Amazon's affiliate program and qualifies
-- for the direct-link bonus (per GP38PJ6EUR6PFBEC).

UPDATE amazon_products
SET affiliate_url = CONCAT(
    'https://',
    REGEXP_SUBSTR(amazon_url, 'amazon\\.[a-z.]+'),
    '/dp/',
    UPPER(REGEXP_SUBSTR(amazon_url, '[A-Z0-9]{10}')),
    '/ref=nosim?tag=frizonofswede-21'
)
WHERE amazon_url REGEXP '/dp/[A-Z0-9]{10}';
