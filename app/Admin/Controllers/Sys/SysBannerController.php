<?php

namespace App\Admin\Controllers\Sys;

use App\Admin\Repositories\Sys\SysBanner;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Admin\Controllers\BaseController;

class SysBannerController extends BaseController{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(){
        if(config('admin.banner.banner_show') == false){
            return admin_error('error', '当前已关闭轮播图功能，请删除此目录或联系管理员打开轮播图功能');
        }
        return Grid::make(new SysBanner(), function (Grid $grid) {
            $grid->column('id')->sortable();
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

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(){
        return Form::make(new SysBanner(), function (Form $form) {
            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
                $tools->disableDelete();
            });
            $form->display('id');
            $form->image('image')->autoUpload()->uniqueName()->saveFullUrl()->required();
            if(config('admin.banner.url_show')){
                $form->text('url')->required();
            }
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
        });
    }
}
