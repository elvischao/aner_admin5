<?php
namespace App\Api\Repositories\Sys;

use App\Api\Repositories\BaseRepository;
use App\Models\Sys\SysBanner as Model;
use Illuminate\Support\Facades\Redis;

class SysBannerRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    /**
     * 获取全部banner（所有场景都是获取所有的banner图）
     *
     * @return void
     */
    public function get_all(){
        $values = Redis::hvals("banner");
        if(!$values){
            self::set_all();
            $values = Redis::hvals("banner");
        }
        foreach($values as $key=> $value){
            $values[$key] = json_decode($value);
        }
        return $values;
    }

    /**
     * 将全部数据添加到redis
     * 后台修改banner图后，会将缓存删除
     *
     * @return void
     */
    private function set_all(){
        foreach($this->eloquentClass::all() as $item){
            Redis::hmset('banner', ["{$item->id}"=> json_encode(['image'=> $item->image, 'url'=> $item->url])]);
        }
        return true;
    }
}