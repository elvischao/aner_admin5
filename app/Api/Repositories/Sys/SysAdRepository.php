<?php
namespace App\Api\Repositories\Sys;

use App\Api\Repositories\BaseRepository;
use App\Models\Sys\SysAd as Model;
use Illuminate\Support\Facades\Redis;

class SysAdRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    /**
     * 获取指定id的数据
     *
     * @param int $id 广告id
     * @return void
     */
    public function get_one(int $id){
        do{
            $value = Redis::hgetall('ad:' . $id);
        }while(count($value) == 0 && $this->set_one($id));
        return $value;
    }

    /**
     * 将指定数据添加到redis
     * 如果是广告位，则获取广告位下的所有广告并存储
     *
     * @param int $id 广告id
     * @return void
     */
    private function set_one($id){
        $value = $this->eloquentClass::find($id);
        if(!$value){
            return Redis::hmset("ad:{$id}", []);
        }
        if($value->parent_id == 0){
            $items = $this->eloquentClass::where('parent_id', $value->id)->get();
            foreach ($items as $key => $item) {
                Redis::hmset("ad:{$value->id}", ["{$item->id}"=> json_encode([
                    'title'=> $item->title,
                    'image'=> $item->image,
                    'content'=> $item->content,
                ])]);
            }
        }else{
            Redis::hmset("ad:{$value->id}", [
                'title'=> $value->title,
                'image'=> $value->image,
                'content'=> $value->content,
            ]);
        }
        return true;
    }
}