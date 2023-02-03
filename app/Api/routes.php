<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::post('upload', [\App\Api\Controllers\BaseController::class, 'upload']);

Route::post('send/sms', [\App\Api\Controllers\BaseController::class, 'send_sms']);

Route::post('login/phone_smscode', [\App\Api\Controllers\LoginController::class, 'phone_smscode_login']);
Route::post('login/identity_password', [\App\Api\Controllers\LoginController::class, 'identity_password_login']);
Route::post('login/yidun_oauth', [\App\Api\Controllers\LoginController::class, 'yidun_oauth_login']);
Route::post('login/third_party', [\App\Api\Controllers\LoginController::class, 'third_party_login']);
Route::post('register', [\App\Api\Controllers\LoginController::class, 'phone_register']);

Route::post('sys/banner', [\App\Api\Controllers\SysController::class, 'banner']);
Route::post('sys/notice', [\App\Api\Controllers\SysController::class, 'notice']);
Route::post('sys/notice/list', [\App\Api\Controllers\SysController::class, 'notice_list']);
Route::post('sys/ad', [\App\Api\Controllers\SysController::class, 'ad']);

Route::group([
    'middleware' => ['user.token'],
], function(Router $router){
    $router->post('user/detail', [\App\Api\Controllers\UserController::class, 'detail']);
    $router->post('user/update/data', [\App\Api\Controllers\UserController::class, 'update_data']);

    $router->post('user/test', [\App\Api\Controllers\UserController::class, 'test']);
    $router->post('user/log/fund', [\App\Api\Controllers\UserLogController::class, 'fund_log']);
    $router->post('user/log/sysmessage', [\App\Api\Controllers\UserLogController::class, 'sys_message_log']);
    $router->post('user/log/sysmessage/detail', [\App\Api\Controllers\UserLogController::class, 'sys_message_detail']);

    $router->post('user/update_password', [\App\Api\Controllers\UserController::class, 'update_password']);
    $router->post('user/forget_password', [\App\Api\Controllers\UserController::class, 'forget_password']);
    $router->post('user/update_level_password', [\App\Api\Controllers\UserController::class, 'update_level_password']);
    $router->post('user/forget_level_password', [\App\Api\Controllers\UserController::class, 'forget_level_password']);
});

