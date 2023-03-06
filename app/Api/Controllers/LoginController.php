<?php
namespace App\Api\Controllers;

use Illuminate\Http\Request;

use App\Api\Controllers\BaseController;

use App\Api\Services\UserLoginService;

use App\Api\Tools\YidunMobileService;
use App\Api\Tools\WxminiRegisterService;
use App\APi\Tools\WxLoginService;


class LoginController extends BaseController{
    protected $service = null;

    public function __construct(Request $request, UserLoginService $UserLoginService){
        parent::__construct($request);
        $this->service = $UserLoginService;
    }

    /**
     * 手机号注册
     *
     * @return void
     */
    public function phone_register(\App\Api\Requests\Login\RegisterRequest $request){
        $phone = $request->input('phone');
        $password = $request->input('password');
        $this->service->register('phone', ['phone'=> $phone, 'password'=> $password]);
        return success('注册成功');
    }

    /**
     * 手机号-短信验证码登录
     *
     * @param Request $request
     * @return void
     */
    public function phone_smscode_login(\App\Api\Requests\Login\SmscodeLoginRequest $request){
        $phone = $request->input('phone');
        return success('登录成功', $this->service->login('phone_smscode', ['phone'=> $phone]));
    }

    /**
     * 账号-密码登录
     *
     * @param Request $request
     * @return void
     */
    public function identity_password_login(Request $request){
        $identity_type = 'phone';
        $identity = $request->input('identity');
        $password = $request->input('password');
        return success('登录成功', $this->service->login('identity_password', [
            'identity_type'=> $identity_type,
            $identity_type=> $identity,
            'password'=> $password
        ]));
    }

    /**
     * 易盾一键登录
     *
     * @param Request $request
     * @return void
     */
    public function yidun_oauth_login(Request $request){
        $token = $request->input('token', '');
        $accessToken = $request->input('accessToken', '');
        $res = YidunMobileService::oauth($token, $accessToken);
        $phone = $res['data']['phone'];
        return success('登录成功', $this->service->login('yidun_oauth', ['phone'=> $phone]));
    }

    /**
     * 微信小程序登录
     *
     * @param Request $request
     * @return void
     */
    public function wxmini_login(Request $request){
        $code = $request->input('code', '');
        $iv = $request->input('iv', '') ?? '';
        $encryptedData = $request->input('encryptedData', '') ?? '';
        $parent_id = $request->input('parent_id', '') ?? '';
        // 此步骤会自动注册
        $wxmini_service = new WxminiRegisterService();
        $openid = $wxmini_service->get_openid($code, $iv, $encryptedData, $parent_id);
        return success('登录成功', $this->service->login('wxmini', ['openid'=> $openid]));
    }

    /**
     * 微信公众号登录(第三方登录)
     *
     * @param Request $request
     * @return void
     */
    public function wx_login(Request $request){
        $code = $request->input('code', '') ?? '';
        $WxLoginService = new WxLoginService();
        $openid = $WxLoginService->get_openid($this->uid, $code);
        return success('登录成功', $this->service->login('wx', ['openid'=> $openid]));

    }
}
