<?php

namespace App\Admin\Repositories\Article;

use App\Models\Article\ArticleCategory as Model;
use Dcat\Admin\Repositories\EloquentRepository;
use App\Api\Repositories\Article\ArticleCategoryRepository;

class ArticleCategory extends EloquentRepository{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    public function get_all_data(){
        return $this->eloquentClass::all()->pluck('name', 'id');
    }

    public function use_id_get_name(int $id){
        return $this->eloquentClass::where('id', $id)->value('name');
    }

    /**
     * 删除api接口中的缓存
     *
     * @param integer $id
     * @return void
     */
    public function del_cache_data(int $id){
        $ArticleCategoryRepository = new ArticleCategoryRepository();
        $ArticleCategoryRepository->base_delete_select_cache($id);
    }
}
