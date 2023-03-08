<?php
namespace App\Api\Services;


use App\Api\Repositories\User\UsersRepository;
use App\Api\Repositories\Sys\SysSettingRepository;
use App\Api\Repositories\Sys\SysAdRepository;

class UserLoginService{
    protected $repository;

    public function __construct(){
        $this->repository = new UsersRepository();
    }

    /**
     * 用户注册
     * 用户注册唯一途径
     *
     * @param string $account 账号
     * @param string $phone 手机号
     * @param string $email 邮箱
     * @param string $password 密码
     * @param string $level_password 二级密码
     * @param string $avatar 头像
     * @param string $nickname 昵称
     * @param string $sex 性别
     * @param string $parent_id 上级id
     * @param string $login_type 第三方登录条件
     * @param string $openid 第三方标识
     * @param string $platform 登录平台
     * @return void
     */
    public function register(string $account = '', string $phone = '', string $email = '', string $password = '', string $level_password = '', string $avatar = '', string $nickname = '', string $sex = '', string $parent_id = '', string $third_party = '', string $openid = '', string $login_type = ''){
        if($third_party == ''){
            // 非第三方登录，需要填写 account，phone，email 中的一个
            if($account == '' && $phone == '' && $email == ''){
                throwBusinessException('请填写账号信息');
            }
        }else{
            if($openid == ''){
                throwBusinessException('请先从第三方获取数据');
            }
        }
        $avatar = $avatar == '' ? (new SysAdRepository())->get_data(17)['value'] : $avatar;
        $nickname = $nickname == '' ? '用户' . create_captcha(8, 'lowercase+uppercase+figure') : $nickname;
        $sex = $sex == '' ? '保密' : $sex;
        $res = $this->repository->create_data([
            'account'=> $account,
            'phone'=> $phone,
            'email'=> $email,
            'password'=> $password,
            'level_password'=> $level_password,
            'avatar'=> $avatar,
            'nickname'=> $nickname,
            'sex'=> $sex,
            'parent_id'=> $parent_id,
            'login_type'=> $login_type,
            'openid'=> $openid
        ]);
        return $res;
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
            'wx'=> 'openid',
            'identity_password'=> empty($data['identity_type']) ? 'phone' : $data['identity_type']
        ][$type];
        // 判断传入的参数中是否有此标识参数
        if(empty($identity_type)){
            throwBusinessException('账号或密码错误！');
        }
        // 通过标识获取指定会员
        $user = $this->repository->base_use_fields_get_data([[$identity_type, '=', $data[$identity_type]]]);
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
                $user = $this->register('', $user->phone);
            }
        }
        // 判断用户是否已冻结
        if($user->is_login == 0){
            throwBusinessException('当前用户已被冻结');
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