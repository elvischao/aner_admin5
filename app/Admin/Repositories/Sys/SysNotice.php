<?php

namespace App\Admin\Repositories\Sys;

use App\Models\Sys\SysNotice as Model;
use Dcat\Admin\Repositories\EloquentRepository;
use App\Api\Repositories\Sys\SysNoticeRepository;

class SysNotice extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    /**
     * 删除 api 接口中设置的缓存
     *
     * @param integer $id
     * @return void
     */
    public function del_cache_data(int $id){
        $SysNoticeRepository = new SysNoticeRepository();
        $SysNoticeRepository->base_delete_select_cache($id);
    }
}
