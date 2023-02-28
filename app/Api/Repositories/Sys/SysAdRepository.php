<?php
namespace App\Api\Repositories\Sys;

use App\Api\Repositories\BaseRepository;
use App\Models\Sys\SysAd as Model;
use Illuminate\Support\Facades\Cache;

class SysAdRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    /**
     * 获取数据，如果是广告位则需要获取当前广告位下的全部广告
     *
     * @param integer $id
     * @return void
     */
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

    /**
     * 删除 get_data 方法中产生的缓存
     */
    public function del_get_data_cache(int $id){
        Cache::forget("ad:{$id}");
    }
}