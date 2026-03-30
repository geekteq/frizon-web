# Product Specification — frizon.org

## Core goals

### 1. Private travel log
- Save places from current mobile position
- Add short raw internal notes
- Add optional structured fields
- Add a few mobile photos
- Rate places with sub-ratings
- Create visits over time for the same place
- Keep places in a long-term place database

### 2. Trip planning
- Create trips with one or more ordered stops
- A trip may contain only one stop
- Reuse places from the place database or create new places
- Add checklists and shopping lists
- Support list templates
- Support stop categories
- Calculate route by road between stops
- Store both route ETA and custom ETA based on 95 km/h
- Export a whole trip for Garmin and other formats

### 3. Public publishing
- Publish selected places, not the full private trip structure
- Public homepage starts with a map
- Show selected public places with AI-assisted descriptions and photos
- Top list is manual, not automatic

## Domain concepts

### Place
A place is a durable object in the system.
It can have many visits over time.
A place can exist without being in an active trip.
A trip can later include existing places.

### Visit
A visit belongs to a place.
A visit holds time-specific notes, ratings and photos.
Same place can be visited many times over years.

### Trip
A trip is a container with ordered stops.
This is what gets exported to Garmin.
Trips can have cover image, intro and public summary if needed.

### Trip stop
A stop belongs to a trip and points to a place.
It has order, category, notes and list connections.

### List
A reusable checklist or shopping list.
Can belong globally, to a trip, or to a stop.

## Stop categories
- breakfast
- lunch
- dinner
- fika
- sight
- shopping
- stellplatz
- wild_camping
- camping

## Ratings
Sub-ratings for places (from a campervan perspective):
- location
- calmness
- service
- value
- return_value (how likely we would return to this place)

## Optional visit fields
All nullable:
- plus_notes
- minus_notes
- tips_notes
- price_level
- would_return
- suitable_for (freetext, comma-delimited, with autocomplete from past values)
- things_to_note

## User experience decisions
- Mobile first
- Quick save from current GPS position
- Swipe gestures for checklist items
- Internal note can be raw and short
- AI text generation triggered by "Brodera ut text" button, never automatic
- System should detect if user is at a known place and suggest creating a new visit
- Public side is place-based, not trip diary based
- Top list is manual curation only
- Default is private, human approval required before publishing

## GPS flow
Mobile-first "add place here" using browser geolocation.
If user is close to an existing place within configurable radius, suggest:
"Det här ser ut som [Platsnamn]. Skapa ett nytt besök?"

## Routing
- Provider abstraction (do not hardwire Google APIs)
- Start with openrouteservice or similar
- Road routing, not straight line
- Per segment: distance_km, provider_eta_minutes, eta_95_minutes, geometry/polyline
- ETA 95 formula: round(distance_km / 95 * 60)

Service abstractions:
- RouteProviderInterface
- OpenRouteServiceProvider
- FakeRouteProvider (for local testing)

## Exports
From a trip, not just a place:
- GPX (first priority)
- CSV
- JSON
- Coordinate list
- Google Maps links

Garmin Drive and manual file drop via computer are important.
Trip export should produce a real route with ordered stops.

Export services:
- GpxTripExporter
- CsvTripExporter
- JsonTripExporter
- GoogleMapsLinkExporter

GPX requirements:
- Export whole trip with ordered waypoints
- Include place name and coordinates
- Practical for Garmin import
- Trip export matters more than perfect GPX sophistication in v1

## Image handling
- Store images on disk
- Store metadata in database
- Generate resized variants for list, card and detail views
- Do not store image blobs in MySQL

## AI text generation
AI is helper only. Flow:
1. User writes raw internal note
2. User optionally fills structured fields (plus, minus, tips, price level, would_return, suitable_for, things_to_note)
3. User clicks "Brodera ut text"
4. System sends structured prompt to AI
5. AI returns a draft public description
6. User edits and approves

## Publishing model
Public publishing is place-led, with visit-derived content.
A published public place page may show:
- Place name
- Selected approved public description
- Selected visit photos
- Ratings summary
- Tags
- Filters

## Public frontend
Homepage starts with map first.
Public filters:
- Place type
- Country
- Tags
- Total rating
- Top list
- Featured

## Checklist interactions
- Swipe right marks item done
- Swipe left opens edit or delete actions
- Long press may reorder if practical
- Fallback for desktop with standard buttons

## Views and flows

### Private side
- Login
- Dashboard
- Places index
- Place detail
- Add place from current location
- Create visit
- Detect nearby known place and suggest new visit
- Trips index
- Trip detail
- Create trip
- Reorder stops
- Add stop from existing place
- Create new place from trip flow
- Trip route summary
- List management
- Template management
- Publish queue
- AI draft review

### Public side
- Homepage with map first
- Public place cards
- Place detail page
- Top list page
- Filters
- Featured places

## Database design

### Tables
- users
- places
- place_tags
- place_filters (or normalized metadata tables)
- visits
- visit_images
- visit_ratings
- trips
- trip_stops
- trip_route_segments
- lists
- list_items
- list_templates
- ai_drafts
- public_featured_places

### Field intent

**places**
- id
- slug
- name
- lat
- lng
- address_text
- country_code
- place_type
- public_allowed
- is_featured
- is_toplisted
- default_public_text
- created_by
- created_at
- updated_at

**visits**
- id
- place_id
- user_id
- visited_at
- raw_note
- plus_notes
- minus_notes
- tips_notes
- price_level
- would_return
- suitable_for (freetext, comma-delimited)
- things_to_note
- ai_draft_id
- approved_public_text
- ready_for_publish
- published_at
- created_at
- updated_at

**visit_ratings**
- id
- visit_id
- location_rating
- calmness_rating
- service_rating
- value_rating
- return_value_rating (likelihood to return)
- total_rating_cached

**trips**
- id
- slug
- title
- intro_text
- public_summary
- cover_image_path
- status
- is_public
- created_by
- start_date
- end_date
- created_at
- updated_at

**trip_stops**
- id
- trip_id
- place_id
- stop_order
- stop_type
- planned_at
- arrival_at
- departure_at
- note
- created_at
- updated_at

**trip_route_segments**
- id
- trip_id
- from_stop_id
- to_stop_id
- distance_km
- provider_eta_minutes
- eta_95_minutes
- geometry
- provider_name
- created_at
- updated_at

**lists**
- id
- scope_type (global, trip, stop)
- scope_id
- list_type (checklist, shopping)
- title
- based_on_template_id (nullable)
- created_by
- created_at
- updated_at

**list_items**
- id
- list_id
- item_order
- text
- is_done
- done_at
- category (nullable)
- notes (nullable)

**list_templates**
- id
- list_type
- title
- description
- created_by
- created_at
- updated_at
