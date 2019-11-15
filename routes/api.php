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
Route::get('/get/users', 'Api\UserController@index');

Route::get('/get/cryptograpghy/encryption', 'Api\EncryptionController@encryption');
Route::get('/get/cryptograpghy/decryption', 'Api\DecryptionController@decryption');

// route with access token
Route::group([
    'middleware' => 'auth:api'
], function(){
    /**
     * auth connection to tenant database
     * payload = authorization data
     */
    Route::post('/auth/connection', 'Api\AuthController@connection');
    
    // get user by id
    Route::get('/get/users/{id}', 'Api\UserController@get');

    // get website by user id
    Route::get('/get/websites/{id}', 'Api\WebsiteController@getByUserId');

    Route::post('/set/product', 'Api\ProductController@store');
    Route::put('/put/product/{id}', 'Api\ProductController@update');
    Route::delete('/delete/product/{id}', 'Api\ProductController@destroy');

    /**
     * Website
     */
    // Route::get('/get/website/{id}', 'Api\WebsiteController@getByUserId');
    // Route::post('/set/product', 'Api\ProductController@store');
    // Route::put('/put/product/{id}', 'Api\ProductController@update');
    // Route::delete('/delete/product/{id}', 'Api\ProductController@destroy');
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
