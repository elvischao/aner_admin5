<?php

namespace App\Admin\Repositories\Sys;

use App\Models\Sys\SysBanner as Model;
use Dcat\Admin\Repositories\EloquentRepository;
use Illuminate\Support\Facades\Redis;

use App\Api\Repositories\Sys\SysBannerRepository;

class SysBanner extends EloquentRepository{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    /**
     * 删除api接口中生成的缓存
     *
     * @return void
     */
    public function del_cache_data(){
        $SysBannerRepository = new SysBannerRepository();
        $SysBannerRepository->del_get_all_cache();
    }
}
