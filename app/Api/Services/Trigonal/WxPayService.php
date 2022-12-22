<?php
namespace App\Api\Services\Trigonal;

use Yansongda\Pay\Pay;

use App\Api\Repositories\Sys\SysSettingRepository;
use App\Api\Repositories\Log\LogUserPayRepository;
use App\Api\Repositories\User\UsersRepository;

/**
 * 支付类
 */
class WxPayService{
    protected $config;

    public function __construct(){
        $this->sys_setting_repository = new SysSettingRepository();
        $this->mch_id = $this->sys_setting_repository->use_id_get_value(40);
        $this->mch_secret_key = $this->sys_setting_repository->use_id_get_value(41);
        $this->mini_app_id = $this->sys_setting_repository->use_id_get_value(16);
        $this->wechat_public_cert = $this->sys_setting_repository->use_id_get_value(42);
        $url = config("app.url");
        $this->config = [
            'wechat' => [
                'default' => [
                    // 必填-商户号，服务商模式下为服务商商户号
                    'mch_id' => $this->mch_id,
                    // 必填-商户秘钥
                    'mch_secret_key' => $this->mch_secret_key,
                    // 必填-商户私钥 字符串或路径
                    'mch_secret_cert' => './cert/apiclient_key.pem',
                    // 必填-商户公钥证书路径
                    'mch_public_cert_path' => './cert/apiclient_cert.pem',
                    // 必填
                    'notify_url' => $url . '/api/pay/notify',
                    // 选填-公众号 的 app_id
                    'mp_app_id' => '',
                    // 选填-小程序 的 app_id
                    'mini_app_id' => $this->mini_app_id,
                    // 选填-app 的 app_id
                    'app_id' => '',
                    // 选填-微信公钥证书路径, optional，强烈建议 php-fpm 模式下配置此参数
                    'wechat_public_cert_path' => [
                        $this->wechat_public_cert => __DIR__.'/Cert/wechatPublicKey.crt',
                    ],
                    // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SERVICE
                    'mode' => Pay::MODE_NORMAL,
                ]
            ],
            'logger' => [ // optional
                'enable' => false,
                'file' => './logs/wechat.log',
                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'single', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
            ],
        ];
    }

    /**
     * 发起支付请求
     *
     * @param int $uid 会员id
     * @param int $number 金额
     * @return void
     */
    public function mini_pay($uid, $number){
        // 创建支付订单
        $order_no = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $log_user_pay_repository = new LogUserPayRepository();
        $res = $log_user_pay_repository->create_data([
            'order_no'=> $order_no,
            'uid'=> $uid,
            'type'=> 'wxmini',
            'order_type'=> '充值',
            'money'=> $number,
            'platform'=> 'wxmini',
            'status'=> 1
        ]);
        if(!$res){
            return error('操作失败');
        }
        // 构建微信支付订单
        $user_repositories = new UsersRepository();
        $user = $user_repositories->use_field_get_data([['id', '=', $uid]], ['openid']);
        $order = [
            'out_trade_no'=> $order_no,
            '_config' => 'default',
            'description'=> '充值',
            'amount' => [
                'total' => intval($number),
                'currency' => 'CNY',
            ],
            'payer' => [
                'openid' => $user->openid,
            ]
        ];
        Pay::config($this->config);
        $result = Pay::wechat()->mini($order);
        return $result;
    }

    /**
     * 支付回调
     *
     * @return void
     */
    public function pay_notify(){
        Pay::config($this->config);
        try{
            $data = Pay::wechat()->callback();
            // TODO: 这里写业务逻辑
            return Pay::wechat()->success();
        } catch (\Exception $e) {
        }
        return false;
    }
}
