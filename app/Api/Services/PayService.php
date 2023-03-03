<?php
namespace App\Api\Services;

use App\Api\Repositories\Log\LogUserPayRepository;

use App\Api\Tools\AliyunPayService;
use App\Api\Tools\WxPayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayService{
    protected $pay_type = ['充值'];

    /**
     * 充值功能-第三方支付调用
     *
     * @param integer $uid
     * @param integer|float|string $amount
     * @param string $pay_method
     * @return void
     */
    public function recharge_pay(int $uid, int|float|string $amount, string $pay_method){
        return $this->pay($pay_method, $uid, $amount, '', '充值', '充值' . $amount);
    }


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
        $res = $LogUserPayRepository->base_create_data([
            'order_no'=> $order_no,
            'uid'=> $uid,
            'type'=> $pay_method,
            'order_type'=> $order_type,
            'money'=> $money,
            'platform'=> 'app',
            'status'=> 1,
            'remark'=> $remark
        ]);
        // 调用第三方支付
        switch($pay_method){
            case 'app支付宝':
                return ((new AliyunPayService()))->index($subject, $order_no, $money);
                break;
            case "h5支付宝":
                break;
            case "app微信":
                break;
            case "h5微信":
                return ((new WxPayService()))->jsapi_pay($uid, $subject, $order_no, $money);
                break;
            case "小程序微信":
                return ((new WxPayService()))->mini_pay($uid, $subject, $order_no, $money);
                break;
            default:
                throwBusinessException('支付调用失败');
                break;
        }
    }

    /**
     * 阿里云支付回调
     *
     * @param array $request
     * @return bool
     */
    public function alipay_notify(array $params):bool{
        // 验证
        $result = (new AliyunPayService())->notify_verify($params);
        Log::debug("支付宝支付：" . json_encode($result));
        $res = $this->notify_execute($params['out_trade_no']);
        return $res;
    }

    /**
     * 微信支付回调
     *
     * @param array $request
     * @return bool
     */
    public function wxpay_notify(array $params):bool{
        // 验证
        $result = (new WxPayService())->notify_verify();
        Log::debug("微信支付：" . json_encode($result));
        // 回调
        $res = $this->notify_execute($result['resource']['ciphertext']['out_trade_no']);
        return $res;
    }

    /**
     * 支付回调后，逻辑执行
     *
     * @param string $order_no 订单编号，数据的唯一标识，由支付方回调数据中获得
     * @return boolean
     */
    protected function notify_execute(string $order_no):bool{
        // 获取支付记录
        $LogUserPayRepository = new LogUserPayRepository();
        $log = $LogUserPayRepository->base_use_fields_get_data([['order_no', '=', $order_no]]);
        if(!$log || $log->status != 1){
            return true;
        }
        // 根据记录类型做支付成功后的逻辑处理
        DB::beginTransaction();
        try{
            // 将支付记录修改为已处理(已回调)
            $res_one = $LogUserPayRepository->base_update_datas([['id', '=', $log->id]], ['status'=> 2]);
            if(!$res_one){
                throwBusinessException('支付记录状态修改失败');
            }
            switch($log->order_type){
                case "充值":
                    break;
                default:
                    break;
            }
            DB::commit();
        }catch(\Exception $e){
            DB::rollback();
            throwBusinessException($e->getMessage());
        }
        return true;
    }
}