<?php
namespace App\Api\Repositories\Sys;

use App\Api\Repositories\BaseRepository;
use App\Models\Sys\SysNotice as Model;
use Illuminate\Support\Facades\Redis;

class SysNoticeRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    /**
     * 获取当前公告是否已读
     *
     * @param int $uid 会员id
     * @param int $id 公告id
     * @return bool
     */
    public function get_read_status(int $uid, int $id):bool{
        return Redis::sismember('sys_notice:' . $uid, $id);
    }

    /**
     * 将当前公告设置为已读
     *
     * @param int $uid 会员id
     * @param int $id 公告id
     * @return bool
     */
    public function set_read_status(int $uid, int $id):bool{
        return Redis::sadd('sys_notice:' . $uid, $id);
    }
}