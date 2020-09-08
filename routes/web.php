<?php

use Illuminate\Support\Facades\Route;

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

Route::post('/m3u/gelist.php', 'EPGcontroller@getlist');

Route::get('/{provider}/epg/{channel_name}', 'EPGcontroller@channelEPG')->name('providers.epg');

Route::get('/providers', 'EPGcontroller@providers');

Route::get('/{provider}/channels', 'EPGcontroller@providersChannels')->name('channels');