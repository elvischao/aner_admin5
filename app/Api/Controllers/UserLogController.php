<?php
namespace App\Api\Controllers;

use Illuminate\Http\Request;

use App\Api\Controllers\BaseController;

use App\Api\Services\UserLogService;

class UserLogController extends BaseController{

    /**
     * 会员资产流水记录
     *
     * @param int $page 页码
     * @param int $limit 每页条数
     * @return void
     */
    public function fund_log(Request $request){
        $page = $request->input('page', 1) ?? 1;
        $limit = $request->input('limit', 10) ?? 10;
        $coin_type = $request->input('coin_type', '') ?? '';
        return success('资产流水日志', (new UserLogService())->get_fund_log_list($this->uid, $coin_type, $page, $limit));
    }

    /**
     * 系统消息记录
     *
     * @param Request $request
     * @return void
     */
    public function sys_message_log(Request $request){
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        return success('系统消息1', (new UserLogService())->get_sys_message_list($this->uid, $page, $limit));
    }

    /**
     * 获取系统消息的详情
     *
     * @param Request $request
     * @return void
     */
    public function sys_message_detail(Request $request){
        $id = $request->input('id', 0);
        return success('系统消息2', (new UserLogService())->get_sys_message_detail($this->uid, $id));
    }
}
