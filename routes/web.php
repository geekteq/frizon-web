<?php

function registerRoutes(Router $router): void
{
    // Auth
    $router->get('/login', 'AuthController', 'showLogin');
    $router->post('/login', 'AuthController', 'login');
    $router->post('/logout', 'AuthController', 'logout');

    // Dashboard
    $router->get('/', 'DashboardController', 'index');

    // Places
    $router->get('/platser', 'PlaceController', 'index');
    $router->get('/platser/ny', 'PlaceController', 'create');
    $router->post('/platser', 'PlaceController', 'store');
    $router->get('/platser/{slug}', 'PlaceController', 'show');
    $router->get('/platser/{slug}/redigera', 'PlaceController', 'edit');
    $router->put('/platser/{slug}', 'PlaceController', 'update');
    $router->delete('/platser/{slug}', 'PlaceController', 'destroy');

    // Visits
    $router->get('/platser/{slug}/besok/nytt', 'VisitController', 'create');
    $router->post('/platser/{slug}/besok', 'VisitController', 'store');
    $router->get('/besok/{id}', 'VisitController', 'show');
    $router->get('/besok/{id}/redigera', 'VisitController', 'edit');
    $router->put('/besok/{id}', 'VisitController', 'update');
    $router->delete('/besok/{id}', 'VisitController', 'destroy');

    // Trips
    $router->get('/resor', 'TripController', 'index');
    $router->get('/resor/ny', 'TripController', 'create');
    $router->post('/resor', 'TripController', 'store');
    $router->get('/resor/{slug}', 'TripController', 'show');
    $router->get('/resor/{slug}/redigera', 'TripController', 'edit');
    $router->put('/resor/{slug}', 'TripController', 'update');
    $router->delete('/resor/{slug}', 'TripController', 'destroy');

    // Trip stops
    $router->post('/resor/{slug}/hallplatser', 'TripController', 'addStop');
    $router->delete('/resor/hallplatser/{stopId}', 'TripController', 'removeStop');
    $router->put('/resor/{slug}/hallplatser/ordning', 'TripController', 'reorderStops');

    // Trip routing and export
    $router->post('/resor/{slug}/berakna-rutt', 'TripController', 'calculateRoute');
    $router->get('/resor/{slug}/export/gpx', 'TripController', 'exportGpx');

    // List templates (must come before /listor/{id})
    $router->get('/listor/mallar', 'ListController', 'templates');
    $router->get('/listor/mallar/ny', 'ListController', 'createTemplate');
    $router->post('/listor/mallar', 'ListController', 'storeTemplate');
    $router->delete('/listor/mallar/{id}', 'ListController', 'deleteTemplate');

    // Lists
    $router->get('/listor', 'ListController', 'index');
    $router->get('/listor/ny', 'ListController', 'create');
    $router->post('/listor', 'ListController', 'store');
    $router->get('/listor/{id}', 'ListController', 'show');
    $router->get('/listor/{id}/redigera', 'ListController', 'edit');
    $router->put('/listor/{id}', 'ListController', 'update');
    $router->delete('/listor/{id}', 'ListController', 'destroy');

    // List items
    $router->post('/listor/{id}/punkt', 'ListController', 'addItem');
    $router->post('/listor/punkt/{itemId}/toggle', 'ListController', 'toggleItem');
    $router->delete('/listor/punkt/{itemId}', 'ListController', 'removeItem');
    $router->put('/listor/{id}/punkt/ordning', 'ListController', 'reorderItems');

    // Public pages (no auth required)
    $router->get('/pub', 'PublicController', 'homepage');
    $router->get('/pub/platser/{slug}', 'PublicController', 'placeDetail');
    $router->get('/pub/topplista', 'PublicController', 'topList');

    // Publish queue (private)
    $router->get('/publicera', 'PublishController', 'queue');
    $router->post('/publicera/{slug}/godkann', 'PublishController', 'approve');
    $router->post('/publicera/{slug}/avpublicera', 'PublishController', 'unpublish');
    $router->post('/publicera/{slug}/topplista', 'PublishController', 'toggleToplist');

    // API endpoints (JSON)
    $router->get('/api/platser/nearby', 'PlaceController', 'nearby');
    $router->post('/api/images/upload', 'VisitController', 'uploadImage');
    $router->get('/api/tags/suitable-for', 'VisitController', 'suitableForSuggestions');
}
