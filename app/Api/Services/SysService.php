<?php
namespace App\Api\Services;

use App\Api\Repositories\Sys\SysBannerRepository;
use App\Api\Repositories\Sys\SysNoticeRepository;
use App\Api\Repositories\Sys\SysAdRepository;
use App\Api\Repositories\Article\ArticleRepository;
use App\Api\Repositories\Article\ArticleCategoryRepository;
use App\Api\Repositories\Idx\IdxSettingRepository;


class SysService{
    /**
     * 获取全部的轮播图
     *
     * @return void
     */
    public function get_banners(){
        return (new SysBannerRepository())->get_all();
    }

    /**
     * 获取公告
     * 根据系统设置返回指定公共
     *
     * @param integer $id
     * @return void 单条的公告信息
     */
    public function get_notice(int $id = 0):\Illuminate\Database\Eloquent\Model{
        $SysNoticeRepository = new SysNoticeRepository();
        $sys_image_show = config('admin.notice.image_show');
        switch(config('admin.notice.type')){
            case '单条文字':
                $data = $SysNoticeRepository->base_use_fields_get_data([], ['id', 'title']);
                $data->content = $data->title;
                unset($data->title);
                break;
            case "多条文字":
                if($id == 0){
                    throwBusinessException('当前公告模式设置为多条，请使用 get_notice_list 接口或传递 id 参数!');
                }
                $data = $SysNoticeRepository->base_use_fields_get_data([['id', '=', $id]], ['id', 'title', 'image']);
                if($sys_image_show){
                    unset($data->image);
                }
                break;
            case "单条富文本":
                $data = $SysNoticeRepository->base_use_fields_get_data([], ['id', 'title', 'content', 'image']);
                if($sys_image_show){
                    unset($data->image);
                }
                break;
            case "多条富文本":
                if($id == 0){
                    throwBusinessException('当前公告模式设置为多条，请使用 get_notice_list 接口或传递 id 参数!');
                }
                $data = $SysNoticeRepository->base_use_fields_get_data([['id', '=', $id]], ['id', 'title', 'content', 'image']);
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
    public function get_notice_list(int $page = 1, int $limit = 10):\Illuminate\Database\Eloquent\Collection{
        $SysNoticeRepository = new SysNoticeRepository();
        $notice_type = config('admin.notice.type');
        if($notice_type != '多条富文本' && $notice_type != '多条文字'){
            throwBusinessException('当前公告模式设置为单条，请直接使用 get_notice 接口');
        }
        $data = $SysNoticeRepository->base_use_fields_get_list([], $page, $limit, ['id', 'desc'], ['id', 'title', 'content', 'image']);
        $sys_image_show = config('admin.notice.image_show');
        foreach($data as $key=> $value){
            if($sys_image_show == false){
                unset($data[$key]->image);
            }
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
        $SysAdRepository = new SysAdRepository();
        $data = $SysAdRepository->get_data($id);
        return $data;
    }

    /**
     * 获取文章分类列表
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get_article_category_list():\Illuminate\Database\Eloquent\Collection{
        $ArticleCategoryRepository = new ArticleCategoryRepository();
        $data = $ArticleCategoryRepository->base_use_fields_get_list([], 1, 1000, ['id', 'desc'], ['id', 'name', 'image']);
        return $data;
    }

    /**
     * 获取文章列表
     *
     * @param integer $category_id
     * @param integer $page
     * @param integer $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get_article_list(int $category_id, int $page = 1, int $limit = 10):\Illuminate\Database\Eloquent\Collection{
        $where = [];
        if($category_id != 0){
            $where[] = ['category_id', '=', $category_id];
        }
        $ArticleRepository = new ArticleRepository();
        $data = $ArticleRepository->base_use_fields_get_list($where, $page, $limit, ['id', 'desc'], ['id', 'tag_ids', 'category_id', 'title', 'author', 'intro', 'keyword', 'image', 'content', 'created_at']);
        foreach($data as &$v){
            $v = $ArticleRepository->disposal_data($v);
        }
        return $data;
    }

    /**
     * 获取文章详情
     *
     * @param integer $id
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function get_article_detail(int $id):\Illuminate\Database\Eloquent\Model{
        $ArticleRepository = new ArticleRepository();
        $data = $ArticleRepository->base_use_fields_get_data([['id', '=', $id]], ['id', 'tag_ids', 'category_id', 'title', 'author', 'intro', 'keyword', 'image', 'content', 'created_at']);
        if(!$data){
            throwBusinessException('文章不存在');
        }
        $data = $ArticleRepository->disposal_data($data);
        return $data;
    }


    /**
     * 获取指定类型的项目设置
     *
     * @param string $type
     * @return array
     */
    public function get_setting_list(string $type):array{
        $IdxSettingRepository = new IdxSettingRepository();
        $res = $IdxSettingRepository->base_use_fields_get_list([['type', '=', $type]], 1, 1000);
        $fields = $IdxSettingRepository->get_fields_name($type);
        $data = [];
        foreach($res as $v){
            $temp = [];
            foreach(range(0, count($fields) - 1) as $key){
                $temp[$fields[$key]] = $v['value' . $key];
            }
            $data[] = $temp;
        }
        return ['msg'=> $IdxSettingRepository->get_type_name($type), 'data'=> $data];
    }
}