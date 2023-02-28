<?php
namespace App\Api\Tools;

use Illuminate\Support\Facades\Http;

use App\Api\Repositories\Sys\SysSettingRepository;
use App\Api\Repositories\User\UsersRepository;


/**
 * 微信小程序注册
 */
class WxminiRegisterService{
    public function __construct($code, $iv, $encryptedData){
        //微信小程序配置
        $sys_setting_repositories = new SysSettingRepository();
        $this->appid = $sys_setting_repositories->use_id_get_value(16);
        $this->secret = $sys_setting_repositories->use_id_get_value(17);

        //根据code获取openid
        $api = "https://api.weixin.qq.com/sns/jscode2session?appid={$this->appid}&secret={$this->secret}&js_code={$code}&grant_type=authorization_code";
        $res = json_decode(Http::get($api), true);
        if(!empty($res['errcode'])){
            throwBusinessException($res['errcode']);
        }
        $sessionKey = $res['session_key'];
        $openid = $res['openid'];
        if($iv != '' && $encryptedData != ''){
            $res = $this->decryptData($this->appid, $sessionKey, $encryptedData, $iv);
            $nickname = $res['nickName'];
            $avatar = $res['avatarUrl'];
        }else{
            $nickname = '游客';
            $avatar = '';
        }

        $user_repository = new UsersRepository();
        $this->user = $user_repository->use_field_get_data([['openid', '=', $openid]]);
        if(!$this->user){
            $this->user = $this->repositories->create_data([
                'login_type' => 'wxmini',
                'nickname' => $nickname,
                'avatar' => $avatar,
                'openid' => $openid
            ]);
        }
    }

    /**
     * 获取会员信息
     *
     * @param [type] $field
     * @return void
     */
    public function get_data($field){
        return $this->user->$field;
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