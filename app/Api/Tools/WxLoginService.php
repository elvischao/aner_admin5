<?php
namespace App\Api\Tools;

use Illuminate\Support\Facades\Http;

use App\Api\Repositories\User\UsersRepository;


/**
 * 微信公众号登录
 */
class WxLoginService{
    protected $appid;
    protected $appsecret;

    public function __construct(){
        $this->appid = 'wxd7959af203d9633b';
        $this->appsecret = '02ddff94192e9c2858661e56d6fa16ba';
    }

    /**
     * 获取公众号信息
     */
    public function bind_wx_data_operation(int $uid, string $code, string $type){
        $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->appid . '&secret=' . $this->appsecret . '&code=' . $code . '&grant_type=authorization_code';
        $token = json_decode(file_get_contents($token_url));
        if(isset($token->errcode)){
            throwBusinessException($token->errcode . ': ' . $token->errmsg);
        }
        if($type == 'openid'){
            $data['openid'] = $token->openid;
        }else{
            $refresh_token = $token->refresh_token;
            $access_token_url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=' . $this->appid . '&grant_type=refresh_token&refresh_token=' . $refresh_token;
            $access_token = json_decode(file_get_contents($access_token_url));
            if(isset($access_token->errcode)){
                throwBusinessException($access_token->errcode . ': ' . $access_token->errmsg);
            }
            $user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token->access_token . '&openid=' . $access_token->openid . '&lang=zh_CN';
            $user_info = json_decode(file_get_contents($user_info_url));
            if(isset($user_info->errcode)){
                throwBusinessException($user_info->errcode . ': ' . $user_info->errmsg);
            }
            $data['openid'] = $user_info->openid;
            $data['nickname'] = $user_info->nickname;
            // $data['sex'] = $user_info->sex;
            // $data['language'] = $user_info->language;
            // $data['city'] = $user_info->city;
            // $data['province'] = $user_info->province;
            // $data['country'] = $user_info->country;
            $data['headimgurl'] = $user_info->headimgurl;
        }
        $res = (new UsersRepository())->base_update_datas([['id', '=', $uid]], $data);
        return boolval($res);
    }
}

/**
 * 以上三个接口的具体返回内容：
 *  https://api.weixin.qq.com/sns/oauth2/access_token：
 *   object(stdClass)#1777 (5) {
 *       ["access_token"]=> string(110) "66_DtB6.............KYOVcaXQ"
 *       ["expires_in"]=> int(7200)
 *       ["refresh_token"]=> string(110) "66_8g-.............i8TCGhfc"
 *       ["openid"]=> string(28) "ohyvq6axEpySwbGGfIOxV0a1QTrw"
 *       ["scope"]=> string(15) "snsapi_userinfo"
 *   }
 *
 * https://api.weixin.qq.com/sns/oauth2/refresh_token
 *  object(stdClass)#1776 (5) {
 *       ["openid"]=> string(28) "ohyvq6axEpySwbGGfIOxV0a1QTrw"
 *       ["access_token"]=> string(110) "66_DtB6.............KYOVcaXQ"
 *       ["expires_in"]=> int(7200)
 *       ["refresh_token"]=> string(110) "66_8g-.............i8TCGhfc"
 *       ["scope"]=> string(15) "snsapi_userinfo"
 *   }
 *
 * https://api.weixin.qq.com/sns/userinfo
 *  object(stdClass)#1779 (9) {
 *       ["openid"]=> string(28) "ohyvq6axEpySwbGGfIOxV0a1QTrw"
 *       ["nickname"]=> string(6) "奈亚"
 *       ["sex"]=> int(0)
 *       ["language"]=> string(0) ""
 *       ["city"]=> string(0) ""
 *       ["province"]=> string(0) ""
 *       ["country"]=> string(0) ""
 *       ["headimgurl"]=> string(131) "https://thirdwx.qlogo.cn/mmopen/vi_32/2M99jhPTcZDd3510WQmTC3CHj4s30oMzvnH4H6YMlE0hd7jCibztudNDYibyWYm7tBBic6RR7VMsH3JdurDAXB8Ug/132"
 *       ["privilege"]=> array(0) { }
 *   }
 */