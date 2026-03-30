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

    // API endpoints (JSON)
    $router->get('/api/platser/nearby', 'PlaceController', 'nearby');
    $router->post('/api/images/upload', 'VisitController', 'uploadImage');
    $router->get('/api/tags/suitable-for', 'VisitController', 'suitableForSuggestions');
}
