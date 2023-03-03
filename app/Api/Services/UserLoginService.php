<?php
namespace App\Api\Services;


use App\Api\Repositories\User\UsersRepository;
use App\Api\Repositories\Sys\SysSettingRepository;

class UserLoginService{
    protected $repository;

    public function __construct(){
        $this->repository = new UsersRepository();
    }

    /**
     * 注册
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function register(string $type, array $data){
        if($type == 'phone'){
            return $this->repository->create_data($data);
        }
    }

    /**
     * 登录
     * 当前登录共有四种方法：手机号-短信验证码登录、账号(标识)-密码登录、云盾一键登录、微信小程序登录
     * 其中只有账号-密码登录需要验证密码
     * 手机号-短信验证码登录、云盾一键登录、微信小程序登录 的信息在此之前已经得到了验证，直接登录即可
     * 云盾一键登录、微信小程序登录 时会员不存在是需要注册的
     *
     * @param [type] $type
     * @param [type] $data
     * @return void
     */
    public function login(string $type, array $data){
        // 获取每种登录方式的登录标识（账号、手机号等）
        $identity_type = [
            'phone_smscode'=> 'phone',
            'yidun_oauth'=> 'phone',
            'wxmini'=> 'openid',
            'identity_password'=> empty($data['identity_type']) ? 'phone' : $data['identity_type']
        ][$type];
        // 判断传入的参数中是否有此标识参数
        if(empty($identity_type)){
            throwBusinessException('账号或密码错误！');
        }
        // 通过标识获取指定会员
        $user = $this->repository->use_field_get_data([[$identity_type, '=', $data[$identity_type]]]);
        // 短信验证码和账号密码登录需要判断会员是否存在
        if(in_array($type, ['phone_smscode', 'identity_password'])){
            if(!boolval($user)){
                throwBusinessException('账号或密码错误!!');
            }
        }
        // 账号密码登录需要判断密码是否正确
        if(in_array($type, ['identity_password'])){
            if(!$this->repository->verify_password($user, $data['password'])){
                throwBusinessException('账号或密码错误');
            }
        }
        // 云盾登录如果会员不存在则直接注册
        if(in_array($type, ['yidun_oauth'])){
            if(!boolval($user)){
                $user = $this->repository->create_data(['phone'=> $user->phone]);
            }
        }
        // 组合返回数据
        $data = [
            'uid'=> $user->id,
            'avatar'=> $user->avatar,
            'phone'=> $user->phone,
            'token'=> $this->repository->set_token($user->id),
            'openid'=> $user->openid
        ];
        return $data;
    }
}