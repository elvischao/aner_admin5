<?php

namespace App\Admin\Controllers\Sys;

use App\Admin\Repositories\Sys\SysBanner;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Admin\Controllers\BaseController;

class SysBannerController extends BaseController{
    protected int $id;
    protected string $image;
    protected string $url;
    protected array $banner_site;

    public function __construct(){
        $this->banner_site = config("admin.banner.site");
        if(count($this->banner_site) == 0){
            $this->banner_site = ['首页'];
        }
    }


    protected function grid(){
        if(config('admin.banner.banner_show') == false){
            return admin_error('error', '当前已关闭轮播图功能，请删除此目录或联系管理员打开轮播图功能');
        }
        return Grid::make(new SysBanner(), function (Grid $grid) {
            $grid->column('id')->sortable();
            if(count($this->banner_site) != 1){
                $grid->column("site", '位置');
                $grid->selector(function (Grid\Tools\Selector $selector){
                    $selector->select('site', '位置', array_combine($this->banner_site, $this->banner_site));
                });
            }
            $grid->column('image')->image('', 60, 60);
            if(config('admin.banner.url_show')){
                $grid->column('url');
            }

            $grid->disableFilterButton();
            $grid->disableViewButton();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
            });
        });
    }

    protected function form(){
        return Form::make(new SysBanner(), function (Form $form) {
            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
                $tools->disableDelete();
            });
            $form->display('id');
            if(count($this->banner_site) == 1){
                $form->hidden("site")->value($this->banner_site[0]);
            }else{
                $form->select("site", '位置')->options(array_combine($this->banner_site, $this->banner_site))->required();
            }
            $form->image('image')->autoUpload()->uniqueName()->saveFullUrl()->required()->removable(false)->retainable();
            if(config('admin.banner.url_show')){
                $form->text('url')->required();
            }

            // 清除缓存
            $form->saved(function(Form $form, $result){
                (new SysBanner())->del_cache_data();
            });
            $form->deleted(function(Form $form, $result){
                (new SysBanner())->del_cache_data();
            });

            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
        });
    }
}
