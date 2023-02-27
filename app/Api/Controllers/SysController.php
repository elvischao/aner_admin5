<?php
namespace App\Api\Controllers;

use App\Api\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Api\Services\SysService;
class SysController extends BaseController{
    protected $service;

    public function __construct(Request $request, SysService $SysService){
        parent::__construct($request);
        $this->service = $SysService;
    }

    /**
     * banner图
     *
     * @return void
     */
    public function banner(){
        return success('轮播图', $this->service->get_banners());
    }

    /**
     * 获取公告详情
     *
     * @param Request $request
     * @return void
     */
    public function notice(Request $request){
        $id = $request->input('id', 0);
        return success('公告', $this->service->get_notice($id));
    }

    /**
     * 获取公告列表
     *
     * @param Request $request
     * @return void
     */
    public function notice_list(\App\Api\Requests\PageRequest $request){
        $page = $request->input("page", 1) ?? 1;
        $limit = $request->input("limit", 10) ?? 10;
        $data = $this->service->get_notice_list($page, $limit);
        return success('公告列表', $data);
    }

    /**
     * 获取指定广告，如果是广告位则获取广告位下所有广告
     *
     * @param Request $request
     * @return void
     */
    public function ad(Request $request){
        $id = $request->input('id', 0);
        return success('广告', $this->service->get_ad($id));
    }

    /**
     * 获取文章分类列表
     *
     * @param Request $request
     * @return void
     */
    public function article_category_list(Request $request){
        $data = $this->service->get_article_category_list();
        return success("文章分类列表", $data);
    }

    /**
     * 获取文章列表, 可根据文章分类筛选
     *
     * @param \App\Api\Requests\PageRequest $request
     * @return void
     */
    public function article_list(\App\Api\Requests\PageRequest $request){
        $page = $request->input("page", 1) ?? 1;
        $limit = $request->input("limit", 10) ?? 10;
        $category_id = $request->input("category_id", 0) ?? 0;
        $data = $this->service->get_article_list($category_id, $page, $limit);
        return success("文章列表", $data);
    }

    /**
     * 获取文章详情
     *
     * @param Request $request
     * @return void
     */
    public function article_detail(Request $request){
        $id = $request->input('id', 0) ?? 0;
        $data = $this->service->get_article_detail($id);
        return success("文章详情", $data);
    }
}
