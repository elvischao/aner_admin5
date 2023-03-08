<?php
namespace App\Api\Repositories\Log;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

use App\Models\Log\LogSysMessage as Model;

use App\Api\Repositories\BaseRepository;
use App\Admin\Repositories\Log\LogSysMessage as AdminLogSysMessage;


class LogSysMessageRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    /**
     * 通过uid获取会员当前页的系统消息数据
     *
     * @param integer $uid
     * @param integer $page
     * @param integer $limit
     * @return array
     */
    public function use_uid_get_datas_form_redis(int $uid, int $page = 1, int $limit = 10):array{
        return (new AdminLogSysMessage())->use_uid_get_datas_form_redis($uid, $page, $limit);
    }

    /**
     * 根据id获取系统消息数据
     * TODO::没有做验证，如果要验证要么在内容中添加uid数据(数据存储量大)，要么查询当前会员的全部系统消息id然后进行对比(感觉查询过多)
     *
     * @param integer $id
     * @param integer $uid
     * @return array
     */
    public function use_id_get_data_form_redis(int $id, int $uid = null):array{
        $data = (new AdminLogSysMessage())->use_id_get_data_form_redis($id);
        return $data;
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