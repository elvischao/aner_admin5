<?php

namespace App\Models\Idx;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class IdxSetting extends Model{
	use HasDateTimeFormatter;
    use SoftDeletes;

    protected $table = 'idx_setting';
    protected $guarded = [];

    /**
     * 每多一个分类，都要在返回数组中添加一个以分类名为键的元素
     *
     * @return array
     */
    public static function type_page_attribute():array{
        return [
            // '<分类字段名>'=> [
            //     'title'=> '<此分类页面的标题>',
            // ],
        ];
    }

    /**
     * 每多一个分类，都要添加一个以分类命名的方法
     * public static function <分类字段名>_fields(){
     *   return ['<字段含义>', '<字段含义>', '<字段含义>'];
     * }
     */
}
