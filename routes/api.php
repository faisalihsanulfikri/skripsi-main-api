<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/login', 'Api\AuthController@login');
Route::post('/auth/register', 'Api\AuthController@register');
Route::get('/test', 'Api\AuthController@test');

// route with access token
Route::group([
    'middleware' => 'auth:api'
], function(){

});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
