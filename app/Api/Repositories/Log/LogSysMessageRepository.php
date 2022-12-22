<?php
namespace App\Api\Repositories\Log;

use App\Api\Repositories\BaseRepository;
use App\Models\Log\LogSysMessage as Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class LogSysMessageRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    /**
     * 删除缓存数据
     *
     * @param int $uid 会员id
     * @return bool
     */
    public function delete_cache($uid){
        Cache::tags(["sys_message:{$uid}"])->flush();
        return true;
    }

    /**
     * 获取当前消息是否已读
     *
     * @param int $uid 会员id
     * @param int $id 消息id
     * @return bool
     */
    public function get_read_status($uid, $id){
        return Redis::sismember('sys_message_read:' . $uid, $id);
    }

    /**
     * 将当前消息设置为已读
     *
     * @param int $uid 会员id
     * @param int $id 消息id
     * @return bool
     */
    public function set_read_status($uid, $id){
        return Redis::sadd('sys_message_read:' . $uid, $id);
    }

    /**
     * 发送消息
     *
     * @param integer $uid 会员id，默认为0，表示向全体会员发送消息
     * @param string $title 标题
     * @param string $image 图片
     * @param string $message 详细说明
     * @return void
     */
    public function send_message($title, $uid = 0, $image = '', $message = ''){
        return $this->eloquentClass::create([
            'uid'=> $uid,
            'title'=> $title,
            'image'=> $image,
            'message'=> $message
        ]);
    }
}