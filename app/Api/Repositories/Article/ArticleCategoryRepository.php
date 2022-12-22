<?php
namespace App\Api\Repositories\Article;

use App\Api\Repositories\BaseRepository;
use App\Models\Article\ArticleCategory as Model;

class ArticleCategoryRepository extends BaseRepository{
    protected $eloquentClass = Model::class;
}