<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix'=>'api/v1'], function() use($router){

    $router->get('/activity/{allocationType}/{schoolId}', 'ActivityController@index');
    $router->get('/activity-totals/{allocationType}/{schoolId}', 'ActivityController@getTotalsForBarSection');
    $router->get('/activity-by-school-year/{allocationType}/{schoolYearId}', 'ActivityController@getAllocationBySchoolYear');
    $router->get('/activity-by-status/{allocationType}', 'ActivityController@getactivityByStatus');
    $router->get('/search-by-school/{allocationType}', 'ActivityController@searchBySchoolName');
    $router->get('/get-approval', 'ActivityController@getApprovals');
    $router->post('/activity', 'ActivityController@create');
    $router->get('/activity-show/{id}', 'ActivityController@show');
    $router->put('/activity/{id}', 'ActivityController@update');
    $router->delete('/activity/{id}', 'ActivityController@destroy');

});
