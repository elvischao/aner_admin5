<?php
namespace App\Api\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

use App\Api\Services\UserService;
use App\Api\Services\Trigonal\SmsService;
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

    /**
     * 发送短信验证码
     *
     * @param string $type 场景类型，register(注册), other(其他, 登录、忘记密码、修改密码等)
     * @param int $phone
     * @return void
     */
    public function send_sms(\App\Api\Requests\SendSmsRequest $request){
        $phone = $request->input('phone');
        $sms_service = new SmsService();
        $sms_service->send_sms($phone);
        return success('发送成功');
    }

    /**
     * 上传文件
     *
     * @param resource $file 文件资源句柄
     * @return void
     */
    public function upload(Request $request){
        if($request->hasFile('file')){
            $file_upload_service = new FileUploadService();
            return success('上传成功', ['path'=> $file_upload_service->upload_file($request->file('file'))]);
        }
       return error('上传文件不存在');
    }
}
