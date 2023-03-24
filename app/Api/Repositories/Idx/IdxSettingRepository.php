<?php
namespace App\Api\Repositories\Idx;

use App\Api\Repositories\BaseRepository;
use App\Models\Idx\IdxSetting as Model;

class IdxSettingRepository extends BaseRepository{
    protected $eloquentClass = Model::class;

    /**
     * 获取指定分类中个字段的含义
     *
     * @param string $type
     * @return array
     */
    public function get_fields_name(string $type):array{
        $function_name = $type . '_fields';
        return $this->eloquentClass::$function_name();
    }

    /**
     * 获取设置类型的名称
     *
     * @param string $type
     * @return string
     */
    public function get_type_name(string $type):string{
        return $this->eloquentClass::type_page_attribute()[$type]['title'];
    }
}