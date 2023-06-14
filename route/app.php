<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('/', 'index/index');
Route::get('/connect/oauth2/authorize', 'index/connect');
Route::get('/connect/qrconnect', 'index/qrconnect');
Route::get('/alipayoauth', 'index/alipayoauth');
Route::get('/token', 'index/token');
Route::get('/qytoken', 'index/qytoken');
Route::get('/return', 'index/return');
Route::get('/alipayreturn', 'index/alipayreturn');

Route::any('/install', 'install/index');

Route::get('/admin/verifycode', 'admin/verifycode')->middleware(\think\middleware\SessionInit::class);
Route::any('/admin/login', 'admin/login')->middleware(\think\middleware\SessionInit::class);
Route::get('/admin/logout', 'admin/logout');

Route::get('/admin/', function () {
    return redirect('index');
});

Route::group('admin', function () {
    Route::get('/', function () {
        return redirect('admin/index');
    });
    Route::any('/index', 'admin/index');
    Route::any('/set', 'admin/set');
    Route::get('/doc', 'admin/doc');
    Route::any('/domain', 'admin/domain');
    Route::post('/domain_data', 'admin/domain_data');
    Route::get('/record', 'admin/record');
    Route::post('/record_data', 'admin/record_data');
    Route::any('/wxtoken', 'admin/wxtoken');
    Route::post('/wxtoken_data', 'admin/wxtoken_data');
    Route::post('/textwxtoken', 'admin/textwxtoken');
    Route::get('/cleancache', 'admin/cleancache');

})->middleware(\app\middleware\CheckAdmin::class);

Route::miss(function() {
    return response('404 Not Found')->code(404);
});
