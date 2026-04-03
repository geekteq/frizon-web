<?php

function registerRoutes(Router $router): void
{
    // Public pages (no auth required) — root-level routes
    $router->get('/', 'PublicController', 'homepage');
    $router->get('/platser/{slug}', 'PublicController', 'placeDetail');
    $router->get('/topplista', 'PublicController', 'topList');
    $router->get('/sitemap.xml', 'PublicController', 'sitemap');
    $router->get('/llms.txt', 'PublicController', 'llmsTxt');
    $router->get('/integritetspolicy', 'PublicController', 'privacy');
    $router->get('/cookiepolicy', 'PublicController', 'cookies');

    // Shop (public)
    $router->get('/shop', 'AmazonController', 'shopIndex');
    $router->get('/shop/{slug}', 'AmazonController', 'shopProduct');

    // Auth
    $router->get('/adm/login', 'AuthController', 'showLogin');
    $router->post('/adm/login', 'AuthController', 'login');
    $router->get('/adm/byt-losenord', 'AuthController', 'showChangePassword');
    $router->post('/adm/byt-losenord', 'AuthController', 'changePassword');
    $router->post('/adm/logout', 'AuthController', 'logout');

    // Dashboard
    $router->get('/adm', 'DashboardController', 'index');

    // Places
    $router->get('/adm/platser', 'PlaceController', 'index');
    $router->get('/adm/platser/ny', 'PlaceController', 'create');
    $router->post('/adm/platser', 'PlaceController', 'store');
    $router->get('/adm/platser/{slug}', 'PlaceController', 'show');
    $router->get('/adm/platser/{slug}/redigera', 'PlaceController', 'edit');
    $router->put('/adm/platser/{slug}', 'PlaceController', 'update');
    $router->delete('/adm/platser/{slug}', 'PlaceController', 'destroy');

    // Visits
    $router->get('/adm/platser/{slug}/besok/nytt', 'VisitController', 'create');
    $router->post('/adm/platser/{slug}/besok', 'VisitController', 'store');
    $router->get('/adm/besok/{id}', 'VisitController', 'show');
    $router->get('/adm/besok/{id}/redigera', 'VisitController', 'edit');
    $router->put('/adm/besok/{id}', 'VisitController', 'update');
    $router->delete('/adm/besok/{id}', 'VisitController', 'destroy');

    // Trips
    $router->get('/adm/resor', 'TripController', 'index');
    $router->get('/adm/resor/ny', 'TripController', 'create');
    $router->post('/adm/resor', 'TripController', 'store');
    $router->get('/adm/resor/{slug}', 'TripController', 'show');
    $router->get('/adm/resor/{slug}/redigera', 'TripController', 'edit');
    $router->put('/adm/resor/{slug}', 'TripController', 'update');
    $router->delete('/adm/resor/{slug}', 'TripController', 'destroy');

    // Trip stops
    $router->post('/adm/resor/{slug}/hallplatser', 'TripController', 'addStop');
    $router->delete('/adm/resor/hallplatser/{stopId}', 'TripController', 'removeStop');
    $router->put('/adm/resor/{slug}/hallplatser/ordning', 'TripController', 'reorderStops');

    // Trip routing and export
    $router->post('/adm/resor/{slug}/berakna-rutt', 'TripController', 'calculateRoute');
    $router->get('/adm/resor/{slug}/export/gpx', 'TripController', 'exportGpx');
    $router->get('/adm/resor/{slug}/export/csv', 'TripController', 'exportCsv');
    $router->get('/adm/resor/{slug}/export/json', 'TripController', 'exportJson');
    $router->get('/adm/resor/{slug}/export/google-maps', 'TripController', 'exportGoogleMaps');

    // List templates (must come before /adm/listor/{id})
    $router->get('/adm/listor/mallar', 'ListController', 'templates');
    $router->get('/adm/listor/mallar/ny', 'ListController', 'createTemplate');
    $router->post('/adm/listor/mallar', 'ListController', 'storeTemplate');
    $router->delete('/adm/listor/mallar/{id}', 'ListController', 'deleteTemplate');

    // Lists
    $router->get('/adm/listor', 'ListController', 'index');
    $router->get('/adm/listor/ny', 'ListController', 'create');
    $router->post('/adm/listor', 'ListController', 'store');
    $router->get('/adm/listor/{id}', 'ListController', 'show');
    $router->get('/adm/listor/{id}/redigera', 'ListController', 'edit');
    $router->put('/adm/listor/{id}', 'ListController', 'update');
    $router->delete('/adm/listor/{id}', 'ListController', 'destroy');

    // List items
    $router->post('/adm/listor/{id}/punkt', 'ListController', 'addItem');
    $router->post('/adm/listor/punkt/{itemId}/toggle', 'ListController', 'toggleItem');
    $router->delete('/adm/listor/punkt/{itemId}', 'ListController', 'removeItem');
    $router->put('/adm/listor/{id}/punkt/ordning', 'ListController', 'reorderItems');

    // Shop admin
    $router->get('/adm/amazon-lista', 'AmazonController', 'adminIndex');
    $router->get('/adm/amazon-lista/ny', 'AmazonController', 'adminCreate');
    $router->post('/adm/amazon-lista', 'AmazonController', 'adminStore');
    $router->post('/adm/amazon-lista/{id}/publicera', 'AmazonController', 'adminTogglePublish');
    $router->post('/adm/amazon-lista/{id}/hamta', 'AmazonController', 'adminRefetch');
    $router->get('/adm/amazon-lista/{id}/redigera', 'AmazonController', 'adminEdit');
    $router->put('/adm/amazon-lista/{id}', 'AmazonController', 'adminUpdate');
    $router->delete('/adm/amazon-lista/{id}', 'AmazonController', 'adminDestroy');
    $router->post('/adm/amazon-lista/{id}/ai/generera', 'AmazonController', 'generateDraft');
    $router->get('/adm/api/amazon/kategorier', 'AmazonController', 'categoriesApi');

    // Publish queue (private)
    $router->get('/adm/publicera', 'PublishController', 'queue');
    $router->post('/adm/publicera/{slug}/godkann', 'PublishController', 'approve');
    $router->post('/adm/publicera/{slug}/avpublicera', 'PublishController', 'unpublish');
    $router->post('/adm/publicera/{slug}/topplista', 'PublishController', 'toggleToplist');

    // AI drafts
    $router->post('/adm/platser/{slug}/ai/generera', 'AiController', 'generatePlaceDraft');
    $router->post('/adm/besok/{id}/ai/generera', 'AiController', 'generateDraft');
    $router->post('/adm/besok/{id}/ai/{draftId}/godkann', 'AiController', 'approveDraft');
    $router->post('/adm/besok/{id}/ai/{draftId}/avvisa', 'AiController', 'rejectDraft');

    // API endpoints (JSON)
    $router->get('/adm/api/platser/nearby', 'PlaceController', 'nearby');
    $router->post('/adm/api/images/upload', 'VisitController', 'uploadImage');
    $router->post('/adm/api/images/{id}/rotate', 'VisitController', 'rotateImage');
    $router->post('/adm/api/images/{id}/ai-caption', 'VisitController', 'generateCaption');
    $router->post('/adm/api/images/{id}/caption', 'VisitController', 'updateCaption');
    $router->get('/adm/api/tags/suitable-for', 'VisitController', 'suitableForSuggestions');

    // Instagram publishing
    $router->get('/adm/api/besok/{id}/instagram/preview', 'VisitController', 'instagramPreview');
    $router->post('/adm/api/besok/{id}/instagram', 'VisitController', 'publishToInstagram');
}
