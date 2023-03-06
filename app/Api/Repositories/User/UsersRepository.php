<?php

namespace App\Api\Repositories\User;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Hash;

use App\Api\Repositories\BaseRepository;

use App\Models\User\Users as Model;
use App\Models\User\UserFunds;
use App\Models\User\UserDetail;


class UsersRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    /**
     * 创建会员
     *
     * @param [type] $field_values
     * @return void
     */
    public function create_data($field_values){
        $field_values['password'] = $this->set_password($field_values['password']);
        $obj = $this->eloquentClass::create($field_values);
        UserFunds::create(['id'=> $obj->id]);
        UserDetail::create(['id'=> $obj->id]);
        return $obj;
    }

    /**
     * 设置会员的token
     *
     * @param int $uid 会员id
     * @return void
     */
    public function set_token($uid){
        $this->delete_token($uid);
        $user_token = md5(Hash::make(time()));
        Redis::set('user_token:' . $user_token, $uid);
        Redis::set('user_token:' . $uid, $user_token);
        return $user_token;
    }

    /**
     * 删除会员的token信息
     *
     * @param int $uid 会员id
     * @return void
     */
    public function delete_token($uid){
        $token = $this->use_uid_get_token($uid);
        Redis::delete('user_token:' . $token);
        Redis::delete('user_token:' . $uid);
        return true;
    }

    /**
     * 通过token获取会员的id
     *
     * @param string $token token
     * @return void
     */
    public function use_token_get_uid($token){
        $uid = Redis::get('user_token:' . $token);
        return $uid ?? 0;
    }

    /**
     * 通过会员的id获取token
     *
     * @param int $uid 会员id
     * @return void
     */
    public function use_uid_get_token($uid){
        $token = Redis::get('user_token:' . $uid);
        return $token ?? '';
    }

    /**
     * 生成密码
     *
     * @param string $password 密码原码
     * @return array 加密密码
     */
    public function set_password($password){
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 验证密码
     *
     * @param Eloquent $user_obj 会员数据对象
     * @param string $password 密码原码
     * @return void
     */
    public function verify_password($user_obj, $password){
        return password_verify($password, $user_obj->password);
    }

    /**
     * 获取邀请二维码
     *
     * @param integer $uid 会员id
     * @param string $url 邀请链接
     * @return string
     */
    public function get_invite_qrcode(int $uid, string $url):string{
        $save_url = Redis::get("invite_url:{$uid}");
        if($save_url && $save_url == $url){  // 判断链接是否一致
            $invite_qrcode = Redis::get("invite_qrcode:{$uid}");
            if(!$invite_qrcode){  // 判断二维码是否存在
                $invite_qrcode = qrcode($url, $uid);
                Redis::set("invite_qrcode:{$uid}", $invite_qrcode);
            }
        }else{
            Redis::set("invite_url:{$uid}", $url);
            $invite_qrcode = qrcode($url, $uid);
            Redis::set("invite_qrcode:{$uid}", $invite_qrcode);
        }
        return $invite_qrcode;
    }
}
