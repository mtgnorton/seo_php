<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// Route::get('/', function () {
//     return view('welcome');
// });
// 测试方法

Route::get('test', ['\App\Http\Controllers\TestController', 'index']);

Route::any('robots.txt', ['\App\Http\Controllers\IndexController', 'robots']);

Route::fallback(['\App\Http\Controllers\IndexController', 'index'])->middleware([
        \App\Http\Middleware\RecordSpider::class,
        \App\Http\Middleware\RequestLimit::class
    ]
);

Route::get('api/translate', '\App\Http\Controllers\OpenController@translate');
Route::get('api/aiContent', '\App\Http\Controllers\OpenController@aiContent'); 
Route::get('api/translateTwice', '\App\Http\Controllers\OpenController@translateTwice');
