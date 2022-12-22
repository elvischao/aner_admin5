<?php
namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Api\Controllers\BaseController;

use App\Api\Services\PayService;

use App\Api\Services\Trigonal\IosPayService;
use App\Api\Services\Trigonal\WxPayService;


/**
 * 支付类
 * 以下支付后进行的逻辑处理均为充值功能的逻辑代码
 */
class PayController extends BaseController{

    protected $service;

    public function __construct(Request $request, PayService $PayService){
        parent::__construct($request);
        $this->service = $PayService;
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
        $this->service->alipay_notify($request);
    }

    /**
     * 微信回调
     *
     * @param Request $request
     * @return void
     */
    public function wxpay_notify(Request $request){
        $this->service->wxpay_notify($request);
    }
}
