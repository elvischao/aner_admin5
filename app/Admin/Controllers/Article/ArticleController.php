<?php

namespace App\Admin\Controllers\Article;

use App\Admin\Controllers\BaseController;
use App\Admin\Repositories\Article\Article;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Models\Article\ArticleCategory;
use App\Models\Article\ArticleTag;

use Dcat\Admin\Widgets\Card;

class ArticleController extends BaseController{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(){
        return Grid::make(new Article(), function (Grid $grid) {
            $grid->fixColumns(3, -3);
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->width("8%")->sortable();
            $grid->column('title')->width("20%");
            if(config('admin.article.image_show')){
                $grid->column('image')->image('', 40, 40)->width("6%");
            }
            if(config('admin.article.tag_show')){
                $grid->column('tag_ids')->width("15%")->display(function(){
                    if($this->tag_ids != ''){
                        $tag = ArticleTag::whereIn('id', json_decode($this->tag_ids))->get();
                        $str = '';
                        foreach ($tag as $value) {
                            $str .= '<span class="label" style="background:#586cb1">' . $value->name . '</span>&nbsp;';
                        }
                        return $str;
                    }
                });
            }
            $grid->column('category_id')->display(function(){
                return ArticleCategory::where('id', $this->category_id)->value('name');
            })->width("10%");
            if(config('admin.article.author_show')){
                $grid->column('author')->width("10%");
            }
            if(config('admin.article.intro_show')){
                $grid->column('intro')->limit(10, '...')->width("20%");
            }
            if(config('admin.article.keyword_show')){
                $grid->column('keyword')->explode(',')->label()->width("15%");
            }
            $grid->column('content')->width('15%')->display('')->modal(function ($modal) {
                $modal->title($this->title);
                $this->content == null ? $modal->icon('feather ') : $modal->icon('feather icon-eye');
                $card = new Card(null, $this->content);
                return "<div style='padding:10px 10px 0'>$card</div>";
            });
            $grid->column('created_at');

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                if($this->delete_allowed == 0){
                    $actions->disableDelete();
                }
                if($this->update_allowed == 0){
                    $actions->disableEdit();
                }
            });

            $grid->selector(function (Grid\Tools\Selector $selector) {
                $selector->select('category_id', '分类', ArticleCategory::all()->pluck('name', 'id'));
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('title');
                if(config('admin.article.tag_show')){
                    $filter->in('tag_ids')->multipleSelect(ArticleTag::all()->pluck('name', 'id'));
                }
                $filter->between('created_at')->datetime();
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id){
        return Show::make($id, new Article(), function (Show $show) {
            $show->field('id');
            $show->field('title');
            $show->field('author');
            $show->field('image')->image();
            $show->field('tag_ids')->as(function(){
                $tag = ArticleTag::whereIn('id', json_decode($this->tag_ids))->get();
                $str = '';
                foreach ($tag as $value) {
                    $str .= $value->name . ' ';
                }
                return $str;
            });
            $show->field('category_id')->as(function(){
                return ArticleCategory::where('id', $this->category_id)->value('name');
            });;
            $show->field('intro');
            $show->field('keyword');
            $show->field('content')->unescape();
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(){
        return Form::make(new Article(), function (Form $form) {
            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
            });
            $form->display('id');
            $form->text('title');
            if(config('admin.article.author_show')){
                $form->text('author');
            }
            if(config('admin.article.tag_show')){
                $form->checkbox('tag_ids')->options(ArticleTag::all()->pluck('name', 'id'))->saving(function ($value) {
                    return json_encode($value);
                });
            }
            $form->select('category_id')->options(ArticleCategory::all()->pluck('name', 'id'));
            if(config('admin.article.image_show')){
                $form->image('image')->autoUpload()->uniqueName()->saveFullUrl();
            }
            if(config('admin.article.intro_show')){
                $form->textarea('intro')->rows(3);
            }
            if(config('admin.article.keyword_show')){
                $form->tags('keyword')->help('输入关键词后输入逗号，可分割关键词');
            }
            $form->editor('content')->height('600')->disk(config('admin.upload_disk'));

            if(config('admin.developer_mode')){
                $form->switch("delete_allowed", '是否允许删除')->value(1);
                $form->switch("update_allowed", '是否允许修改')->value(1);
            }
            $form->saving(function(Form $form){
                if(config('admin.article.keyword_show')){
                    $form->keyword = implode(',', $form->keyword);
                }
            });

            // 清除缓存
            $form->saved(function(Form $form, $result){
                (new Article())->del_cache_data($form->id ?? $form->model()->id ?? $result);
            });
            $form->deleted(function(Form $form, $result){
                $data_id = $form->model()->toArray()[0]['id'];
                (new Article())->del_cache_data($data_id);
            });

            $form->footer(function ($footer) {
                $footer->disableViewCheck();
            });
        });

    }
}
