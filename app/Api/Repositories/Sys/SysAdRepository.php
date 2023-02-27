<?php
namespace App\Api\Repositories\Sys;

use App\Api\Repositories\BaseRepository;
use App\Models\Sys\SysAd as Model;
use Illuminate\Support\Facades\Cache;

class SysAdRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    public function get_data(int $id){
        $res = Cache::remember("ad:{$id}", $this->cache_expiration_time, function() use($id){
            return $this->eloquentClass::with(['parent', 'children'])->where("id", $id)->first();
        });
        if($res->parent_id == 0){
            // 广告位
            $data = [
                'id'=> $res->id,
                'title'=> $res->title,
                'ad'=> []
            ];
            foreach($res->children as $child){
                $value_key_name = ['文字'=> 'value', '图片'=> 'image', '富文本'=> 'content'][$child->type];
                $data['ad'][] = [
                    'id'=> $child->id,
                    'title'=> $child->title,
                    'type'=> $child->type,
                    'value'=> $child->$value_key_name,
                ];
            }
        }else{
            // 广告
            $value_key_name = ['文字'=> 'value', '图片'=> 'image', '富文本'=> 'content'][$res->type];
            $data = [
                'id'=> $res->id,
                'title'=> $res->title,
                'type'=> $res->type,
                'value'=> $res->$value_key_name,
            ];
        }
        return $data;
    }

    public function del_get_data_cache(int $id){
        Cache::forget("ad:{$id}");
    }


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