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
    public function save_uid_to_redis(int $id, string $uids):bool{
        $uids = explode(',', $uids);
        foreach($uids as $uid){
            Redis::sadd("sysmessage:{$uid}", $id);
        }
        return true;
    }

    /**
     * 将系统消息数据保存到redis中
     *
     * @param integer $id
     * @param string $title
     * @param string $image
     * @param string $content
     * @return void
     */
    public function save_data_to_redis(int $id, string $title, string $image, string $content){
        Redis::hmset("sysmessagecontent:{$id}", 'id', $id, 'title', $title, 'image', $image, 'content', $content, 'created_at', time());
        return true;
    }

    /**
     * 将系统消息id从缓存中删除
     * 包含会员对应的id缓存和消息内容缓存
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
        Redis::del("sysmessagecontent:{$id}");
        return true;
    }

    /**
     * 从缓存中获取会员的系统消息
     *
     * @param integer $uid
     * @param integer $page
     * @param integer $limit
     * @return array
     */
    public function use_uid_get_datas_form_redis(int $uid, int $page = 1, int $limit = 10):array{
        // 获取 uid 与 0 的缓存， 并分页
        $message_ids = Redis::sunion("sysmessage:{$uid}", "sysmessage:0");
        $message_ids = array_slice(array_reverse($message_ids), (($page - 1) * $limit), $limit);
        $data = [];
        foreach($message_ids as $message_id){
            $data[] = $this->use_id_get_data_form_redis($message_id);
        }
        return $data;
    }

    /**
     * 通过id获取系统消息
     *
     * @param integer $id
     * @return array
     */
    public function use_id_get_data_form_redis(int $id):array{
        $res = Redis::hgetall("sysmessagecontent:{$id}");
        $res['created_at'] = date("Y-m-d H:i:s", $res['created_at']);
        return $res;
    }
}
