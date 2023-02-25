<?php

namespace App\Admin\Repositories\Log;

use App\Models\Log\LogSysMessage as Model;

use Dcat\Admin\Repositories\EloquentRepository;
use Illuminate\Support\Facades\Redis;

class LogSysMessage extends EloquentRepository{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    /**
     * 将系统消息id保存到对应的会员缓存中
     * @param int $id 系统消息id
     * @param string $uids 会员id集
     * @return bool
     */
    public function save_data_to_redis(int $id, string $uids):bool{
        $uids = explode(',', $uids);
        foreach($uids as $uid){
            Redis::sadd("sysmessage:{$uid}", $id);
        }
        return true;
    }

    /**
     * 将系统消息id从会员缓存中删除
     *
     * @param integer $id
     * @param string $uids
     * @return boolean
     */
    public function delete_data_form_redis(int $id, string $uids):bool{
        $uids = explode(',', $uids);
        foreach($uids as $uid){
            Redis::srem("sysmessage:{$uid}", $id);
        }
        return true;
    }

    /**
     * 从缓存中获取会员的系统消息
     *
     * @param integer $uid
     * @param integer $page
     * @param integer $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get_data_form_redis(int $uid, int $page = 1, int $limit = 10):\Illuminate\Database\Eloquent\Collection{
        // 获取 uid 与 0 的缓存
        $message_ids = Redis::sunion("sysmessage:{$uid}", "sysmessage:0");
        $message_ids = array_slice(array_reverse($message_ids), (($page - 1) * $limit), $limit);
        $data = $this->eloquentClass::whereIn('id', $message_ids)->select(['id', 'title', 'content'])->get();
        return $data;
    }
}
