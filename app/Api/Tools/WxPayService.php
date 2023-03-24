<?php
namespace App\Api\Tools;

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
        $this->config = config("pay");
    }

    /**
     * 发起支付请求
     *
     * @param int $uid
     * @param string $subject
     * @param string $order_no
     * @param int|float|string $amount
     * @return void
     */
    public function mini_pay(int $uid, string $subject, string $order_no, int|float|string $amount){
        // 构建微信支付订单
        $user_repositories = new UsersRepository();
        $user = $user_repositories->base_use_fields_get_data([['id', '=', $uid]], ['openid']);
        $order = [
            'out_trade_no'=> $order_no,
            '_config' => 'default',
            'description'=> $subject,
            'amount' => [
                'total' => intval($amount * 100),
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
     * 微信公众号支付
     *
     * @param int $uid
     * @param string $subject
     * @param string $order_no
     * @param int|float|string $amount
     * @return void
     */
    public function jsapi_pay(int $uid, string $subject, string $order_no, int|float|string $amount){
        Pay::config($this->config);
        $user_repositories = new UsersRepository();
        $user = $user_repositories->base_use_fields_get_data([['id', '=', $uid]], ['openid']);
        $order = [
            'out_trade_no' => $order_no,
            'description' => $subject,
            'amount' => [
                'total' => intval($amount * 100),
            ],
            'payer' => [
                'openid' => $user->openid,
            ],
        ];
        $response = Pay::wechat()->mp($order);
        return $response;
    }

    /**
     * 退款
     *
     * @param string $order_no 此订单编号是支付记录的订单编号，而非订单数据中的订单编号
     * @param integer|float $money
     * @return void
     */
    public function refund(string $order_no, int|float $money){
        Pay::config($this->config);
        $order = [
            'out_trade_no' => $order_no,
            'out_refund_no' => '' . time(),
            'amount' => [
                'refund' => intval($money * 100),
                'total' => intval($money * 100),
                'currency' => 'CNY',
            ],
        ];
        $result = Pay::wechat()->refund($order);
        return $result;
    }

    /**
     * 支付回调验证
     * 微信的支付回调验证返回中包含订单编号
     *
     * @return void
     */
    public function notify_verify(){
        Pay::config($this->config);
        return Pay::wechat()->callback();
    }
}
