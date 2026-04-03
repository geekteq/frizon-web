-- amazon_products_seed.sql
-- 6 example products across different categories.
-- Replace amazon_url / affiliate_url with real product links before use.
-- image_path is NULL — images will be fetched by AmazonFetcher on first edit.

INSERT INTO amazon_products
    (slug, title, amazon_url, affiliate_url, amazon_description, our_description,
     seo_title, seo_description, category, sort_order, is_featured, is_published)
VALUES
(
    'weber-q1200-gasolgrill-abc123',
    'Weber Q1200 Gasolgrill',
    'https://www.amazon.se/dp/B00004RALEN',
    'https://www.amazon.se/dp/B00004RALEN?tag=frizonofswede-21',
    'Kompakt gasolgrill för balkonger och camping. 8 200 BTU effekt, gjutjärnsgaller.',
    'Weber Q1200 är vår go-to grill på resan. Den ryms enkelt i bakluckan och levererar perfekta grillresultat varje gång. Vi har använt den i över tre år och den håller fortfarande som ny.',
    'Weber Q1200 Gasolgrill — Frizon rekommenderar',
    'Vi rekommenderar Weber Q1200 för husbilsresor. Kompakt, kraftfull och enkel att ta med.',
    'Kök & Matlagning', 1, 1, 1
),
(
    'arlo-pro-4-kamera-def456',
    'Arlo Pro 4 Övervakningskamera',
    'https://www.amazon.se/dp/B08CQHKQDH',
    'https://www.amazon.se/dp/B08CQHKQDH?tag=frizonofswede-21',
    'Trådlös 2K HDR-kamera med färgnattseende och inbyggd spotlight.',
    'Vi hänger upp Arlo Pro 4 utanför Frizze när vi är borta. Appen funkar bra även på mobilnätet och vi har blivit tryggare tack vare den.',
    'Arlo Pro 4 — säkerhetskamera för husbil',
    'Arlo Pro 4 ger trygghet när ni lämnar husbilen. Trådlös 2K-kamera med app-notiser.',
    'Säkerhet', 2, 1, 1
),
(
    'anker-powerbank-737-ghi789',
    'Anker 737 PowerBank 24000mAh',
    'https://www.amazon.se/dp/B09VPHVT2Z',
    'https://www.amazon.se/dp/B09VPHVT2Z?tag=frizonofswede-21',
    '24 000 mAh, 140W snabbladdning, laddningstid 1,5 timmar.',
    'Den här powerbanken laddar allt — telefoner, laptop och till och med vår lilla fläkt. Ovärderlig på ställplatser utan el.',
    'Anker 737 PowerBank — laddning på resan',
    'Anker 737 med 24 000 mAh och 140W snabbladdning — perfekt för husbilsresor.',
    'Elektronik', 3, 0, 1
),
(
    'osprey-farpoint-40-ryggsack-jkl012',
    'Osprey Farpoint 40 Ryggsäck',
    'https://www.amazon.se/dp/B07GNQNCPF',
    'https://www.amazon.se/dp/B07GNQNCPF?tag=frizonofswede-21',
    '40-liters ryggsäck med avtagbart dagsacksfack och ergonomisk ryggpanel.',
    'När vi lämnar Frizze för en dagstur är Osprey Farpoint vår ryggsäck. Den bär bekvämt hela dagen och passar handbagageutrymmet på flyget.',
    'Osprey Farpoint 40 — bästa dagsryggsäcken',
    'Osprey Farpoint 40 är perfekt för dagsturer från husbilen. Rymlig, bekväm och flygsäker.',
    'Packning & Väskor', 4, 0, 1
),
(
    'garmin-inreach-mini-2-mno345',
    'Garmin inReach Mini 2 Satellitkommunikator',
    'https://www.amazon.se/dp/B09FYNBK18',
    'https://www.amazon.se/dp/B09FYNBK18?tag=frizonofswede-21',
    'Tvåvägs satellitmeddelanden och SOS-funktion, 14 dagars batteritid.',
    'Vi tar alltid med inReach Mini 2 i fjällterräng och avlägsna områden utan mobilnät. Det ger oss och hemmavarande trygghet att vi alltid går att nå.',
    'Garmin inReach Mini 2 — säkerhet i vildmarken',
    'Garmin inReach Mini 2 — satellit-SOS och meddelanden för husbilsresor i avlägsna områden.',
    'Säkerhet', 5, 0, 1
),
(
    'eva-solo-thermal-mug-pqr678',
    'Eva Solo Urban To Go Cup 0,35 l',
    'https://www.amazon.se/dp/B00MG7XXQG',
    'https://www.amazon.se/dp/B00MG7XXQG?tag=frizonofswede-21',
    'Dubbelväggig termomugg i rostfritt stål, håller drycken varm i upp till 3 timmar.',
    'Ulrisas favorit. Den följer med på varje morgonpromenad och håller kaffet varmt länge nog för en lugn frukost utanför Frizze.',
    'Eva Solo Termomugg — morgonkaffet på resan',
    'Eva Solo Urban To Go Cup håller kaffet varmt på morgonpromenaden. Ulricas favorit på husbilsresan.',
    'Kök & Matlagning', 6, 0, 1
);
