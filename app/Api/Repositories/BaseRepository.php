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
    protected int $cache_expiration_time;  # 缓存的保存时间
    protected bool $cache_onoff;  # 缓存是否开启

    public function __construct(bool $cache_onoff = true, int $cache_expiration_time = 86400){
        $this->cache_onoff = $cache_onoff;
        $this->cache_expiration_time = $cache_expiration_time;
    }

    /**
     * 创建数据
     *
     * @param array $field_values 数据集
     * @return int 返回新添加数据的id
     */
    public function base_create_data(array $field_values):int{
        try{
            $res_data = $this->eloquentClass::create($field_values);
        }catch(\Throwable $th){
            throwBusinessException($th->getMessage());
        }
        return $res_data->id;
    }

    /**
     * 通过某个字段获取数据的id
     *
     * 对某表某字段某值对应的 id 进行缓存，指定时间过期
     * 即使修改此字段此行内容，下次获取时因为 查询字段的值 变化了，不会命中旧的缓存，而是会新生成一条缓存。
     * 如果删除了此行数据，查询时依旧会命中缓存，所以还是需要删除缓存操作。
     * TODO::但是因为查询的就是id，而缓存标签也要使用id，无法完成。
     * TODO::因为 base_use_fields_get_data() 方法需要频繁使用此方法，如果取消缓存则会消耗 mysql 性能。所以无法取消缓存
     *
     * @param array $where 查询条件，二维数组，每个子数组中有三个元素，例如: ['id', '=', 1], ['status', 'in', [1, 2]]
     * @return int
     */
    public function base_use_fields_get_id(array $where = [], bool $cache_onoff = $this->cache_onoff):int{
        switch($cache_onoff){
            case true:
                $key = md5(json_encode($where));
                $res_id = Cache::remember("ufgi:{$this->eloquentClass}:{$key}", $this->cache_expiration_time, function() use($where){
                    return $this->base_many_where_select($where)->value('id') ?? 0;
                });
                break;
            case false:
                $res_id = $this->base_many_where_select($where)->value('id') ?? 0;
                break;
        }
        return $res_id;
    }

    /**
     * 通过查询条件获取单条数据
     *
     * 获取并缓存指定查询条件的指定查询字段。
     * 要考虑更新的问题，需要给缓存设置标签 (一行数据因为查询字段和查询条件的不同会生成多条缓存)，所以需要优先获取数据的id
     * 因为标识是id，如果某行字段的内容修改了，查询数据依旧会命中缓存而不查询新数据。所以需要考虑更新问题
     * 如果使用 base_update_data 方法修改数据，无需在此考虑数据更新问题
     * 如果在后台管理或修改数据库数据，则要考虑数据更新问题
     *
     * @param array $where 查询条件，二维数组，每个子数组中有三个元素，例如: ['id', '=', 1], ['status', 'in', [1, 2]]
     * @param array $select
     * @return json
     */
    public function base_use_fields_get_data($where = [], $select = ['*']){
        switch($this->cache_onoff){
            case true:  // 有缓存
                // 根据查询条件获取id，如果查询条件中有id，则直接使用
                $id = 0;
                foreach($where as $field){
                    if($field[0] == 'id'){
                        $id = $field[2];
                        break;
                    }
                }
                $id = $id == 0 ? $this->base_use_fields_get_id($where) : $id;
                if($id == 0){
                    return null;
                }
                // 根据 where 和 select 条件进行缓存，筛选条件作为缓存主表示，数据id作为标签(删除用)
                $key = md5(json_encode($where) . json_encode($select));
                $data = Cache::tags(["{$this->eloquentClass}:{$id}"])->remember("ufgd:{$this->eloquentClass}:{$key}", $this->cache_expiration_time, function() use($where, $select){
                    return $this->base_many_where_select($where, $select)->first();
                });
                break;
            case false:  // 无缓存
                $data = $this->base_many_where_select($where, $select)->first();
                break;
        }
        return $data;
    }

    /**
     * 根据多个查询条件获取数据列表
     * 包含查询条件、字段筛选、排序、分页。
     * 可使用page=1，limit=1的方式获取一条数据，但此时的一条数据同样是一个二维数组，此二维数组仅包含一条数据。
     * TODO::当前仅缓存了查询的最后一条数据id，方便下一页的查询。（有无更好的缓存方案）
     * TODO::当前方案无数据不一致问题，因此不受缓存开关设置影响
     *
     * @param array $where 查询条件，二维数组，每个子数组中有三个元素，例如: ['id', '=', 1], ['status', 'in', [1, 2]]
     * @param array $select 字段筛选，默认是查询全部字段
     * @param array $order 排序规则，二元数组，对于查询中 orderby 的两个参数
     * @param integer $page 页码
     * @param integer $limit 每页查询的条数
     * @return array[Collection, ...]
     */
    public function base_use_fields_get_list($where = [], $page = 1, $limit = 10, $order = ['id', 'desc'], $select = ['*']){
        $obj = $this->base_many_where_select($where, $select);
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
            Redis::setex("listid:{$key}:{$page}", $this->cache_expiration_time, $data[$list_count - 1]['id']);
        }
        return $data;
    }

    /**
     * 根据指定一项或多项筛选条件，将符合条件的数据修改内容
     * （正常情况下，如果你只想修改一条数据，就需要使用唯一表示字段作为查询条件，因此无需再设置修改一条数据的方法）
     * 将符合筛选条件的缓存删除
     *
     * @param array $where 查询条件，二维数组，每个子数组中有三个元素，例如: ['id', '=', 1], ['status', 'in', [1, 2]]
     * @param array $update_data 要修改的数据，[字段=> 值, ...]
     * @return void
     */
    public function base_update_datas($where = [], $update_data = []){
        $obj = $this->base_many_where_select($where, ['id']);
        $ids = $obj->pluck('id');
        $res = $this->eloquentClass::whereIn('id', $ids)->update($update_data);
        foreach($ids as $id){
            $this->base_delete_select_cache($id);
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
    public function base_increment_data($where, $increment_field, $increment_value = 1, $update_data = []){
        $id = $this->base_use_fields_get_id($where, false);
        $res = $this->eloquentClass::where('id', $id)->increment($increment_field, $increment_value, $update_data);
        $this->base_delete_select_cache($id);
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
    public function base_delete_datas($where){
        $obj = $this->base_many_where_select($where, ['id']);
        $ids = $obj->pluck('id');
        $res = $this->eloquentClass::whereIn('id', $ids)->delete();
        foreach($ids as $id){
            $this->base_delete_select_cache($id);
        }
        return $res;
    }

    /**
     * 删除缓存, 删除查询数据的缓存
     *
     * @param int $id
     * @return void
     */
    public function base_delete_select_cache($id){
        Cache::tags(["{$this->eloquentClass}:{$id}"])->flush();
    }

    /**
     * 通过条件与查询字段，获取一个已经整理好查询语句的模型对象
     *
     * @param array $where
     * @param array $select
     * @return void
     */
    protected function base_many_where_select($where = [], $select = ['*']){
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