<?php
namespace App\Api\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * 实现数据仓库中通用的增删改查操作
 *
 * 在单条数据查询中，会根据数据id缓存数据
 * 所以所有的数据修改、删除操作必须调用 delete_select_cache() 方法，或使用此类中的数据数据修改、删除方法
 */
class BaseRepository{
    protected $eloquentClass;

    /**
     * 创建数据
     *
     * @param array $field_values
     * @return bool
     */
    public function create_data($field_values){
        return boolval($this->eloquentClass::create($field_values));
    }

    /**
     * 通过某个字段获取数据的id
     *
     * 对某表某字段某值对应的id号进行缓存，1天后过期
     * 即使修改此字段此行内容，下次获取时因为 value 变化了，不会命中旧的缓存，而是会新生成一条缓存。
     * 如果修改了其他字段，则因为只获取id，所以不用考虑更新缓存信息问题
     *
     * @param array $where 查询条件，二维数组，每个子数组中有三个元素，例如: ['id', '=', 1], ['status', 'in', [1, 2]]
     * @return int
     */
    public function use_field_get_id($where = []){
        $key = md5(json_encode($where));
        return Cache::remember("ufgi:{$this->eloquentClass}:{$key}", 86400, function() use($where){
            $obj = $this->many_where_select($where);
            return $obj->value('id') ?? 0;
        });
    }

    /**
     * 通过查询条件获取单条数据
     *
     * 获取并缓存指定查询条件的全部信息，然后根据需要返回指定字段。
     * 因为标识是id，如果某行字段的内容修改了，查询数据依旧会命中缓存而不查询新数据。所以需要考虑更新问题
     * 如果使用 update_data 方法修改数据，无需在此考虑数据更新问题
     * 如果在后台管理或修改数据库数据，则要考虑数据更新问题
     *
     * @param array $where 查询条件，二维数组，每个子数组中有三个元素，例如: ['id', '=', 1], ['status', 'in', [1, 2]]
     * @param array $select
     * @return json
     */
    public function use_field_get_data($where = [], $select = ['*']){
        // 无论什么条件，都获取到指定id
        $id = $this->use_field_get_id($where);
        if($id == 0){
            return json_decode(json_encode([]));
        }
        // 根据id获取此行所有字段的数据 (这里进行了缓存)
        $res = Cache::remember("ufgd:{$this->eloquentClass}:{$id}", 86400, function() use($id){
            return $this->eloquentClass::find($id);
        });
        // 根据select条件，将指定参数加入到返回数据中
        $data = [];
        if($res){
            foreach ($select as $value) {
                if($value == "*"){
                    $data = $res;
                    break;
                }
                $data[$value] = $res->$value;  # 参数不存在会返回null
            }
        }
        return json_decode(json_encode($data));  # 将数组或模型对象统一转换成json对象
    }

    /**
     * 根据多个查询条件获取数据列表
     * 包含查询条件、字段筛选、排序、分页。
     * 可使用page=1，limit=1的方式获取一条数据，但此时的一条数据同样是一个二维数组，此二维数组仅包含一条数据。
     *
     * @param array $where 查询条件，二维数组，每个子数组中有三个元素，例如: ['id', '=', 1], ['status', 'in', [1, 2]]
     * @param array $select 字段筛选，默认是查询全部字段
     * @param array $order 排序规则，二元数组，对于查询中 orderby 的两个参数
     * @param integer $page 页码
     * @param integer $limit 每页查询的条数
     * @return array[Collection, ...]
     */
    public function use_fields_get_list($where = [], $page = 1, $limit = 10, $order = ['id', 'desc'], $select = ['*']){
        $obj = $this->many_where_select($where, $select);
        // 排序
        $obj = $obj->orderby($order[0], $order[1]);
        // 保存本页最后一条数据的id，在查询下一页时，可直接使用保存的id作为where条件，筛除已查询过的数据
        // 原理：即使数据更新了，再次进入页面，依旧会从第一页开始获取，$last_id会被更新
        $key = md5($this->eloquentClass . json_encode($where) . json_encode($order));
        $last_id = Redis::get("listid:{$key}:" . ($page - 1));
        if($last_id !== null){
            $sort = $order[1] == 'desc' ? '<' : '>';
            $data = $obj->where('id', $sort, $last_id)->limit($limit)->get();
        }else{
            $data = $obj->offset(($page - 1) * $limit)->limit($limit)->get();
        }
        $list_count = count($data);
        if($list_count > 0){
            Redis::setex("listid:{$key}:{$page}", 86400, $data[$list_count - 1]['id']);
        }
        return $data;
    }

    /**
     * 根据某标识获取指定数据行，并修改数据
     * 因为修改了行数据，所以缓存与数据库不一致了，需要删除缓存
     *
     * @param array $where 查询条件，二维数组，每个子数组中有三个元素，例如: ['id', '=', 1], ['status', 'in', [1, 2]]
     * @param array $update_data 要修改的数据，[字段=> 值, ...]
     * @return bool
     */
    public function update_data($where, $update_data = []){
        $id = $this->use_field_get_id($where);
        $res = $this->eloquentClass::where('id', $id)->update($update_data);
        $this->delete_select_cache($id);  # 此值必定是id
        return $res;
    }

    /**
     * 根据指定一项或多项筛选条件，将符合条件的数据修改内容
     * 将符合筛选条件的缓存删除
     *
     * @param array $where 查询条件，二维数组，每个子数组中有三个元素，例如: ['id', '=', 1], ['status', 'in', [1, 2]]
     * @param array $update_data 要修改的数据，[字段=> 值, ...]
     * @return void
     */
    public function update_datas($where = [], $update_data = []){
        $obj = $this->many_where_select($where, ['id']);
        $ids = $obj->pluck('id');
        $res = $this->eloquentClass::whereIn('id', $ids)->update($update_data);
        foreach($ids as $id){
            $this->delete_select_cache($id);
        }
        return $res;
    }

    /**
     * 根据某标识获取指定数据行，并将指定字段自增指定数值，同时也可修改其他字段的值
     * 与 update_data() 方法一样，需要删除缓存
     * TODO：传负数是否可以实现自减效果？
     *
     * @param array $where 查询条件，二维数组，每个子数组中有三个元素，例如: ['id', '=', 1], ['status', 'in', [1, 2]]
     * @param string $increment_field 自增字段
     * @param integer $increment_value 自增数值
     * @param array $update_data 要修改的数据，[字段=> 值, ...]
     * @return bool
     */
    public function increment_data($where, $increment_field, $increment_value = 1, $update_data = []){
        $id = $this->use_field_get_id($where);
        $res = $this->eloquentClass::where('id', $id)->increment($increment_field, $increment_value, $update_data);
        $this->delete_select_cache($id);
        return $res;
    }

    /**
     * 根据某标识获取指定数据行，并删除数据
     * 与 update_data() 方法一样，需要删除缓存
     *
     * @param string $select_field
     * @param string $select_value
     * @return void
     */
    public function delete_datas($where){
        $obj = $this->many_where_select($where, ['id']);
        $ids = $obj->pluck('id');
        $res = $this->eloquentClass::whereIn('id', $ids)->delete();
        foreach($ids as $id){
            $this->delete_select_cache($id);
        }
        return $res;
    }

    /**
     * 删除缓存, 删除查询数据的缓存
     *
     * @param int $id
     * @return void
     */
    public function delete_select_cache($id){
        Cache::forget("ufgd:{$this->eloquentClass}:{$id}");
    }

    /**
     * 通过条件与查询字段，获取一个已经整理好查询语句的模型对象
     *
     * @param array $where
     * @param array $select
     * @return void
     */
    protected function many_where_select($where = [], $select = ['*']){
        // 如果需要筛选字段，则 id 必须存在
        if(count($select) <= 0 || ($select[0] != '*' && !in_array('id', $select))){
            $select[] = 'id';
        }
        $obj = $this->eloquentClass::select($select);
        // 根据参数组合查询条件，in 方法比较特殊，使用单独的函数，其他如 like, <>, <, >, <=, >=, =等是使用同类规则
        foreach($where as $value){
            switch($value[1]){
                case 'in':
                    $obj = $obj->whereIn($value[0], $value[2]);
                    break;
                default:
                    $obj = $obj->where($value[0], $value[1], $value[2]);
                    break;
            }
        }
        return $obj;
    }
}