<?php

namespace App\Models\Article;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use App\Models\Article\ArticleCategory;

class Article extends Model{
	use HasDateTimeFormatter;
    use SoftDeletes;

    protected $table = 'article';
    protected $guarded = [];

    public function category(){
        return $this->hasOne(ArticleCategory::class, 'id', "category_id");
    }

}
