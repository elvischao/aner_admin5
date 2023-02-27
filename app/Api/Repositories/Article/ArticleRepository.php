<?php
namespace App\Api\Repositories\Article;

use App\Api\Repositories\BaseRepository;
use App\Models\Article\Article as Model;

use App\Api\Repositories\Article\ArticleCategoryRepository;
use App\Api\Repositories\Article\ArticleTagRepository;

class ArticleRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    /**
     * 整理文章数据
     *
     * @param \Illuminate\Database\Eloquent\Model $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function disposal_data(\Illuminate\Database\Eloquent\Model $data):\Illuminate\Database\Eloquent\Model{
        $ArticleCategoryRepository = new ArticleCategoryRepository();
        $ArticleTagRepository = new ArticleTagRepository();
        $data->category = $ArticleCategoryRepository->base_use_fields_get_data([['id', '=', $data->category_id]], ['id', 'name', 'image']);
        unset($data->category_id);
        $tags = $ArticleTagRepository->base_use_fields_get_list([['id', 'in', json_decode($data->tag_ids)]], 1, 100, ['id', 'desc'], ['id', 'name']);
        $data->tags = get_collection_field($tags, 'name');
        unset($data->tag_ids);
        return $data;
    }
}