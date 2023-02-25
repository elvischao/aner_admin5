<?php
namespace App\Api\Services;

use Illuminate\Support\Facades\Redis;

class SmsService{
    /**
     * 发送短信验证码
     *
     * @param string $type 场景类型，register(注册), other(其他, 登录、忘记密码、修改密码等)
     * @param int $phone
     * @return void
     */
    public function send_sms(int $phone){
        $sms_code = rand(100000, 999999);
        Redis::setex("sms_code:{$sms_code}:{$phone}", 60 * 5, '');
        return $this->send($phone, $sms_code);
    }

    /**
     * 调用第三方短信发送接口
     *
     * @param int $phone
     * @param int $sms_code
     * @return bool
     */
    public function send(string $phone, int $sms_code){
        return true;
    }
}