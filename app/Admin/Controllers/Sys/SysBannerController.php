<?php

namespace App\Admin\Controllers\Sys;

use App\Admin\Repositories\Sys\SysBanner;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Admin\Controllers\BaseController;

class SysBannerController extends BaseController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SysBanner(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('image')->image('', 60, 60);
            config('admin.banner.url_show') ? $grid->column('url') : '';
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
    protected function form()
    {
        return Form::make(new SysBanner(), function (Form $form) {
            $form->display('id');
            $form->image('image')->autoUpload()->uniqueName()->saveFullUrl()->required();
            config('admin.banner.url_show') ? $form->text('url')->required() : '';
            $form->saved(function(Form $form, $result){
                (new SysBanner())->del_cache_data();
            });
            $form->deleted(function (Form $form, $result) {
                (new SysBanner())->del_cache_data();
            });
        });
    }
}
