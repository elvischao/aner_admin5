<?php

namespace App\Admin\Repositories\Article;

use App\Models\Article\ArticleTag as Model;
use Dcat\Admin\Repositories\EloquentRepository;

use App\Api\Repositories\Article\ArticleTagRepository;

class ArticleTag extends EloquentRepository{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    /**
     * 根据标签id集获取标签数据
     *
     * @param string $ids_str
     * @return void
     */
    public function use_ids_get_data(string $ids_str){
        $data = $this->eloquentClass::whereIn('id', json_decode($ids_str))->get();
        return $data;
    }

    /**
     * 获取全部数据
     *
     * @return void
     */
    public function get_all_data(){
        return $this->eloquentClass::all()->pluck('name', 'id');
    }

    /**
     * 删除api接口中的缓存
     *
     * @param integer $id
     * @return void
     */
    public function del_cache_data(int $id){
        $ArticleTagRepository = new ArticleTagRepository();
        $ArticleTagRepository->base_delete_select_cache($id);
    }
}
