<?php
namespace App\Api\Repositories\Article;

use App\Api\Repositories\BaseRepository;
use App\Models\Article\ArticleTag as Model;

class ArticleTagRepository extends BaseRepository{
    protected $eloquentClass = Model::class;
}