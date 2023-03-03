<?php
namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Api\Controllers\BaseController;

use App\Api\Services\PayService;

use App\Api\Tools\IosPayService;
use App\Api\Tools\WxPayService;


/**
 * 支付类
 */
class PayController extends BaseController{

    protected $service;

    public function __construct(Request $request, PayService $PayService){
        parent::__construct($request);
        $this->service = $PayService;
    }

    /**
     * 充值
     *
     * @param Request $request
     * @return void
     */
    public function recharge(Request $request){
        $amount = $request->input('amount', 0) ?? 0;
        $pay_method = $request->input('pay_method', '') ?? '';
        $data = $this->service->recharge_pay($this->uid, $amount, $pay_method);
        return success("充值支付调用", $data);
    }

    /**
     * ios支付
     *
     * @param Request $request
     * @return void
     */
    public function ios_pay(Request $request){
        $receipt_data = $request->input('receipt-data', '');
        $ios_pay_service = new IosPayService();
        $ios_pay_service->pay($receipt_data);
        return success("支付成功");
    }

    /**
     * 微信小程序支付
     *
     * @param \App\Api\Requests\PayRequest $request
     * @return void
     */
    public function wxmini_pay(Request $request){
        $number = $request->input('number');
        $wxmini_pay_service = new WxPayService();
        $result = $wxmini_pay_service->mini_pay($this->uid, $number);
        return $result ? success('支付发起', $result) : error('支付发起失败');
    }

    /**
     * 支付宝回调
     *
     * @param Request $request
     * @return void
     */
    public function alipay_notify(Request $request){
        $PayService = new PayService();
        return success("回调", $PayService->alipay_notify($request->input()));
    }

    /**
     * 微信回调
     *
     * @param Request $request
     * @return void
     */
    public function wxpay_notify(Request $request){
        $PayService = new PayService();
        return success("回调", $PayService->wxpay_notify($request->input()));
    }
}
