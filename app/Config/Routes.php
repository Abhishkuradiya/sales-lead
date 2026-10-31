<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('dashboard', 'Home::dashboard');
$routes->get('api/public-settings', 'Api::publicSettings');
$routes->post('api/login', 'Api::login');
$routes->post('api/verify-otp', 'Api::verifyOtp');
$routes->get('api/me', 'Api::me');
$routes->get('api/third-party-directory', 'Api::thirdPartyDirectory');
$routes->post('api/logout', 'Api::logout');
$routes->post('api/session', 'Api::establishSession');
