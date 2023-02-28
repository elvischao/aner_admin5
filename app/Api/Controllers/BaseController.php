<?php
namespace App\Api\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

use App\Api\Services\UserService;
use App\Api\Services\SmsService;
use App\Api\Services\Trigonal\FileUploadService;


class BaseController extends Controller{
    protected $uid;
    protected $setting;

    public function __construct(Request $request){
        // 获取当前登录的会员信息
        if($request->hasHeader('token')){
            $user_service = new UserService();
            $this->uid = $user_service->use_token_get_uid($request->header('token'));
        }else{
            $this->uid = 0;
        }
        // 获取部分系统设置
        $this->setting['identity_field'] = config('admin.users.user_identity')[0];
    }
}
