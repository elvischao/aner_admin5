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
