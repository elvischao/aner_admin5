<?php

namespace App\Admin\Repositories\Article;

use App\Models\Article\Article as Model;
use Dcat\Admin\Repositories\EloquentRepository;

use App\Api\Repositories\Article\ArticleRepository;

class Article extends EloquentRepository{
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
        $ArticleRepository = new ArticleRepository();
        $ArticleRepository->base_delete_select_cache($id);
    }
}
