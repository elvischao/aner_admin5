<?php
namespace App\Api\Repositories\Sys;

use App\Api\Repositories\BaseRepository;
use App\Models\Sys\SysBanner as Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class SysBannerRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    /**
     * 获取全部banner（所有场景都是获取所有的banner图）
     *
     * @return void
     */
    public function get_all(){
        return Cache::tags('banner')->remember("banner", $this->cache_expiration_time, function(){
            return $this->eloquentClass::select(['id', 'image', 'url', 'created_at'])->get();
        });
    }

    /**
     * 删除 get_all() 方法设置的缓存
     *
     * @return void
     */
    public function del_get_all_cache(){
        Cache::tags("banner")->flush();
    }
}