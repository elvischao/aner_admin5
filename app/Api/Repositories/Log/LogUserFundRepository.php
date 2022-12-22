<?php
namespace App\Api\Repositories\Log;

use App\Api\Repositories\BaseRepository;
use App\Models\Log\LogUserFund as Model;
use Illuminate\Support\Facades\Cache;

class LogUserFundRepository extends BaseRepository{
    protected $eloquentClass = Model::class;
    protected $cache_prefix = 'user_fund_log';

    /**
     * 清除缓存
     *
     * @param int $uid 会员id
     * @return void
     */
    public function delete_cache($uid){
        Cache::tags(["{$this->cache_prefix}:{$uid}"])->flush();
        return true;
    }
}