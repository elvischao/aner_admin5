<?php
namespace App\Api\Services;

use App\Api\Repositories\Log\LogUserFundRepository;
use App\Api\Repositories\Log\LogSysMessageRepository;

class UserLogService{
    /**
     * 获取资产流水记录
     *
     * @param int $uid 会员id
     * @param int $page 页码
     * @param integer $limit 每页条数
     * @return void
     */
    public function get_fund_log_list(int $uid, string $coin_type, int $page, int $limit = 10){
        return (new LogUserFundRepository())->base_use_fields_get_list([
            ['uid', '=', $uid],
            ['coin_type', '=', $coin_type]
        ], $page, $limit);
    }

    /**
     * 获取系统消息列表
     *
     * @param int $uid 会员id
     * @param int $page 页码
     * @param integer $limit 每页条数
     * @return void
     */
    public function get_sys_message_list($uid, $page, $limit = 10){
        $LogSysMessageRepository = new LogSysMessageRepository();
        $list_read = config('admin.sys_message.list_read');
        $data = $LogSysMessageRepository->base_use_fields_get_list([['uid', 'in', [0, $uid]]], $page, $limit, ['id', 'desc'], ['id', 'title', 'image', 'updated_at']);
        foreach($data as &$value){
            $value->is_read = $LogSysMessageRepository->get_read_status($uid, $value->id);
            if($list_read){
                $LogSysMessageRepository->set_read_status($uid, $value->id);
            }
        }
        return $data;
    }

    /**
     * 获取系统消息详情
     *
     * @param int $uid 会员id
     * @param int $id 系统消息id
     * @return void
     */
    public function get_sys_message_detail($uid, $id){
        $LogSysMessageRepository = new LogSysMessageRepository();
        $data = $LogSysMessageRepository->base_use_fields_get_data([['id', '=', $id]]);
        $LogSysMessageRepository->set_read_status($uid, $data->id);  # 设置为已读（无论设置如何，这里必须设置已读）
        return $data;
    }
}