-- Correct Frizze 100 000 km service and gas test date.
-- The work was performed by Torvalla LCV on 2026-02-04, not 2026-04-02.

UPDATE frizze_events
SET event_date = '2026-02-04',
    updated_at = NOW()
WHERE vehicle_id = 1
  AND event_date = '2026-04-02'
  AND title = '100 000 km-service + gasoltest'
  AND supplier = 'Torvalla LCV';

UPDATE frizze_service_tasks
SET due_date = '2027-02-04',
    last_done_at = '2026-02-04',
    notes = 'Senast gjort hos Torvalla LCV 2026-02-04.',
    updated_at = NOW()
WHERE vehicle_id = 1
  AND title = 'Årlig service: olja, oljefilter, diagnostik och nivåkontroll';

UPDATE frizze_service_tasks
SET due_date = '2027-02-04',
    last_done_at = '2026-02-04',
    notes = 'Senast godkänd 2026-02-04.',
    updated_at = NOW()
WHERE vehicle_id = 1
  AND title = 'Gasoltäthetskontroll enligt EN 1949';
