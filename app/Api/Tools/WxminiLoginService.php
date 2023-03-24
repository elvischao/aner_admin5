<?php
namespace App\Api\Tools;

use Illuminate\Support\Facades\Http;

use App\Api\Repositories\User\UsersRepository;

use App\Api\Services\UserLoginService;


/**
 * 微信小程序注册
 */
class WxminiRegisterService{
    protected $appid;
    protected $secret;
    protected $repository;

    public function __construct(){
        //微信小程序配置
        $this->appid = env("WXMINI_APPID");
        $this->secret = env("WXMINI_SECRET");
        $this->repository = new UsersRepository();
    }

    /**
     * 获取openid， 如果此用户还没有注册，则直接注册
     *
     * 当前小程序的政策为：所有用户解析出来的昵称和头像都是微信默认，所以我们可以不需要 iv 和 encryptedData 参数。
     *
     * @param [type] $code
     * @param [type] $iv
     * @param [type] $encryptedData
     * @return void
     */
    public function get_openid($code, $iv, $encryptedData, $parent_id){
        $data = $this->jscode2session($code);
        $nickname = "微信用户";
        $avatar = "https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132";
        // TODO::不解析微信用户信息
        // if($iv != '' && $encryptedData != ''){
        //     $user_info = $this->decryptData($this->appid, $data['session_key'], $encryptedData, $iv);
        //     $nickname = $user_info['nickName'];
        //     $avatar = $user_info['avatarUrl'];
        // }
        $user = $this->repository->base_use_fields_get_data([['third_party', '=', '微信小程序'], ['openid', '=', $data['openid']]]);
        if(!$user){
            (new UserLoginService())->register('', '', '', '', '', $avatar, $nickname, '', $parent_id, '微信小程序', $data['openid'], '');
        }
        return $data['openid'];
    }

    /**
     * 访问微信小程序接口，获取会员openid
     *
     * @param string $code
     * @return void
     */
    private function jscode2session(string $code){
        $api = "https://api.weixin.qq.com/sns/jscode2session?appid={$this->appid}&secret={$this->secret}&js_code={$code}&grant_type=authorization_code";
        $res = json_decode(Http::get($api), true);
        if(!empty($res['errcode'])){
            throwBusinessException($res['errcode']);
        }
        return [
            'session_key'=> $res['session_key'],
            'openid'=> $res['openid'],
        ];
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return array 返回数据
     */
    private function decryptData($appid, $sessionKey, $encryptedData, $iv){
        if(strlen($sessionKey) != 24){
            throwBusinessException('encodingAesKey 非法');
        }
        $aesKey = base64_decode($sessionKey);
        if(strlen($iv) != 24){
            throwBusinessException('aes 解密失败');
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "aes-128-cbc", $aesKey, OPENSSL_RAW_DATA, $aesIV);
        $result = $this->decode($result);
        $dataObj = json_decode($result);
        if($dataObj == NULL){
            throwBusinessException('解密后得到的buffer非法');
        }
        if($dataObj->watermark->appid != $appid){
            throwBusinessException('base64解密失败');
        }
        $data = json_decode($result, true);
        return $data;
    }

    private function decode($text){
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }
}