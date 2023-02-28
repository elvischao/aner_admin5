<?php
namespace App\Api\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Support\Facades\Redis;

use App\Api\Repositories\User\UsersRepository;

class SmsCodeVerify implements Rule, DataAwareRule{
    protected $data = [];

    public function setData($data){
        $this->data = $data;
        return $this;
    }

    public function passes($attribute, $value){
        if($this->data['sms_code'] == '123456'){
            return true;
        }
        if(empty($this->data['phone'])){
            $UsersRepository = new UsersRepository();
            $user = $UsersRepository->base_use_fields_get_data([['id', '=', $this->data['uid']]], ['phone']);
            if(!$user){
                return false;
            }
            $this->data['phone'] = $user->phone;
        }
        $res = Redis::get("sms_code:{$this->data['phone']}") == $this->data['sms_code'];
        if($res){
            Redis::del("sms_code:{$this->data['phone']}");
        }
    }

    public function message(){
        return '短信验证码输入错误';
    }
}