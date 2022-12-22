<?php
namespace App\Api\Repositories\Article;

use App\Api\Repositories\BaseRepository;
use App\Models\Article\Article as Model;

class ArticleRepository extends BaseRepository{
    protected $eloquentClass = Model::class;
}