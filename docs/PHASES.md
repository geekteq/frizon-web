# Implementation Phases — frizon.org

Work in phases. Commit after each logical milestone.

## Phase 1 — Foundation
- Project skeleton and directory structure
- Auth (simple username/password, session-based)
- Database schema and migrations
- Basic place CRUD
- Visit CRUD
- Image upload
- Mobile place capture from geolocation

## Phase 2 — Trips and routing
- Trip CRUD
- Ordered stops
- Route segment storage
- Routing provider abstraction (openrouteservice)
- Trip summary view
- Basic GPX export

## Phase 3 — Lists and public layer
- Lists and templates
- Swipe interactions for checklist items
- Publish queue
- Public pages (homepage with map, place cards, detail, top list)
- Filters and map markers

## Phase 4 — AI and polish
- AI draft button ("Brodera ut text") and review flow
- Nearby place suggestion (detect known place within radius)
- Extra exporters (CSV, JSON, coordinate list, Google Maps links)
- Polish

## Expected outputs per phase
1. A concrete file/folder structure
2. SQL schema or migrations
3. Core PHP classes
4. Controllers, routes and views for MVP
5. Export service for GPX
6. Routing abstraction
7. README
8. Short TODO list for deferred features

## Before writing lots of code
- Restate the architecture you will implement
- Point out any risky assumptions
- Then start building
- Keep changes scoped and coherent
