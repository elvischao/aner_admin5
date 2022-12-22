<?php
namespace App\Api\Services;

use App\Api\Repositories\Log\LogUserPayRepository;



class PayService{

    /**
     * 调用第三方支付
     *
     * @param string $pay_method 支付类型，alipay 支付宝，wx 微信
     * @param int $uid 会员id
     * @param int $money 支付金额
     * @param string $remark 备注，每种支付场景备注的情况不同
     * @param string $order_type 订单类型，支付场景
     * @param string $subject 商品名
     * @return void
     */
    public function pay($pay_method, $uid, $money, $remark = '', $order_type = '', $subject = ''){
        // 创建支付订单
        $LogUserPayRepository = new LogUserPayRepository();
        $order_no = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $res = $LogUserPayRepository->create_data([
            'order_no'=> $order_no,
            'uid'=> $uid,
            'type'=> $pay_method,
            'order_type'=> $order_type,
            'money'=> $money,
            'platform'=> 'app',
            'status'=> 1,
            'remark'=> $remark
        ]);
        switch($pay_method){
            case 'alipay':
                break;
            case "wxpay":
                break;
            default:
                throwBusinessException('支付调用失败');
                break;
        }
    }

    /**
     * 阿里云支付回调
     *
     * @param [type] $request
     * @return void
     */
    public function alipay_notify($request){
        // 验证
        
        // 回调逻辑处理
        $identity = 1;
        $type = '';
        $this->notify_execute($identity, $type);
    }

    /**
     * 微信支付回调
     *
     * @param [type] $request
     * @return void
     */
    public function wxpay_notify($request){
        // 验证
        
        // 回调逻辑处理
        $identity = 1;
        $type = '';
        $this->notify_execute($identity, $type);
    }

    /**
     * 支付回调后，逻辑执行
     *
     * @param string|integer $identity 数据的唯一标识，由支付方回调数据中获得
     * @param string $type 类型，用于区分多种支付场景，不同的逻辑处理
     * @return boolean
     */
    protected function notify_execute(string|int $identity, string $type):bool{
        switch($type){
            // 在这里根据 $type 的值做逻辑处理
            default:
                return true;
                break;
        }
    }
}