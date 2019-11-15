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

Route::get('/', function () {
    return "api main-api";
});

Route::post('/auth/login', 'Api\AuthController@login');

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
    Route::get('/get/user/{id}', 'Api\UserController@get');

    // get website by user id
    Route::get('/get/website/{id}', 'Api\WebsiteController@getByUserId');
});
