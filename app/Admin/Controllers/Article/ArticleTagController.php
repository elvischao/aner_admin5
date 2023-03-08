<?php

namespace App\Admin\Controllers\Article;

use App\Admin\Controllers\BaseController;
use App\Admin\Repositories\Article\ArticleTag;

use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

class ArticleTagController extends BaseController{
    protected int $id;
    protected string $name;
    protected string $image;

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(){
        if(config('admin.article.tag_show') == false){
            return admin_error('error', '当前已关闭文章标签功能，请删除此目录或联系管理员打开文章标签功能');
        }
        return Grid::make(new ArticleTag(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            if(config('admin.article.tag_image_show')){
                $grid->column('image')->image('', 40, 40);
            }

            $grid->disableViewButton();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('name');
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(){
        return Form::make(new ArticleTag(), function (Form $form) {
            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
            });

            $form->display('id');
            $form->text('name')->required();
            if(config('admin.article.tag_image_show')){
                $form->image('image')->autoUpload()->uniqueName()->saveFullUrl()->required()->removable(false)->retainable();
            }

            // 清除缓存
            $form->saved(function(Form $form, $result){
                (new ArticleTag())->del_cache_data($form->id ?? $form->model()->id ?? $result);
            });
            $form->deleted(function(Form $form, $result){
                $data_id = $form->model()->toArray()[0]['id'];
                (new ArticleTag())->del_cache_data($data_id);
            });

            $form->footer(function ($footer) {
                $footer->disableViewCheck();
            });
        });
    }
}
