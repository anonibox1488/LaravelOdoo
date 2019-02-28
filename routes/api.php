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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

//listado de funciones q se pueden usar
Route::get('version', 'testController@getVersion');
Route::get('user', 'testController@getIdLogin');
Route::get('permisos/{model}', 'testController@getPermisos');
Route::get('provaides/{model}', 'testController@getProvides');
Route::get('modelos/{model}', 'testController@getModels');
Route::get('modelospro', 'testController@getModelsPro');
Route::get('odoo', 'testController@testOdoo');

Route::resource('contacto', 'testController', ['only' => ['store', 'update']]);
Route::delete('contacto/{id}', 'testController@dropById');
Route::delete('contactod/{field}/{value}', 'testController@dropByWhere');

Route::resource('productos', 'ProductsController', ['only' => ['store', 'index','update']]);
Route::resource('reparaciones', 'RepairsController', ['only' => ['store','update','index', 'destroy']]);

