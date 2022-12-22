<?php
namespace App\Api\Services;

use App\Api\Repositories\Sys\SysBannerRepository;
use App\Api\Repositories\Sys\SysNoticeRepository;
use App\Api\Repositories\Sys\SysAdRepository;

class SysService{
    /**
     * 获取全部的轮播图
     *
     * @return void
     */
    public function get_banners(){
        $banners = (new SysBannerRepository())->get_all();
        if(config('admin.banner.url_show') == false){ // 是否展示url
            foreach($banners as $key => $value){
                $banners[$key] = $value->image;
            }
        }
        return $banners;
    }

    /**
     * 获取公告
     * 根据系统设置返回指定公共
     *
     * @param integer $id
     * @return void 单条的公告信息
     */
    public function get_notice(int $id = 0){
        $SysNoticeRepository = new SysNoticeRepository();
        $sys_image_show = config('admin.notice.image_show');
        switch(config('admin.notice.type')){
            case '单条文字':
                $data = $SysNoticeRepository->use_field_get_data([], ['id', 'title']);
                $data->content = $data->title;
                unset($data->title);
                break;
            case "单条富文本":
                $data = $SysNoticeRepository->use_field_get_data([], ['id', 'title', 'content', 'image']);
                if($sys_image_show){
                    unset($data->image);
                }
                break;
            case "多条富文本":
                if($id == 0){
                    throwBusinessException('当前公告模式设置为多条，请使用 get_notice_list 接口');
                }
                $data = $SysNoticeRepository->use_field_get_data([['id', '=', $id]], ['id', 'title', 'content', 'image']);
                if($sys_image_show){
                    unset($data->image);
                }
                break;
            default:
                return [];
        }
        return $data;
    }

    /**
     * 获取公告列表
     *
     * @param integer $page 页码
     * @param integer $limit 每页展示数据数量
     * @return void
     */
    public function get_notice_list(int $page = 1, int $limit = 10){
        $SysNoticeRepository = new SysNoticeRepository();
        $sys_image_show = config('admin.notice.image_show');
        if(config('admin.notice.type') != '多条富文本'){
            throwBusinessException('当前公告模式设置为单条，请使用 get_notice 接口');
        }
        $data = $SysNoticeRepository->use_fields_get_list([], $page, $limit, ['id', 'desc'], ['id', 'title', 'content', 'image']);
        foreach($data as $key=> $value){
            if($sys_image_show == false){
                unset($data[$key]->image);
            }
            unset($data[$key]->content);
        }
        return $data;
    }

    /**
     * 返回指定广告位信息
     *
     * @param int $id
     * @return void
     */
    public function get_ad(int $id){
        $data = (new SysAdRepository())->getone($id);
        if(empty($data['title'])){  // 是广告位，需要解析数据
            foreach($data as $key=> $value){
                $data[$key] = json_decode($value);
            }
        }
        return $data;
    }
}