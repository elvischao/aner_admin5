<?php

namespace App\Admin\Controllers\Sys;

use App\Admin\Repositories\Sys\SysSetting;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Admin\Controllers\BaseController;
use Dcat\Admin\Widgets\Tab;
use App\Models\Sys\SysSetting as SysSettingModel;

class SysSettingController extends BaseController{

    protected function grid(){
        return Grid::make(new SysSetting(), function (Grid $grid) {
            $grid->wrap(function(){
                $tab = Tab::make();
                foreach ((new SysSetting())->get_parent_list() as $key=> $value) {
                    $tab->add($value, $this->tab($key));
                }
                return $tab;
            });
        });
    }

    private function tab($id){
        return Grid::make(SysSettingModel::where('parent_id', $id), function (Grid $grid) {
            $grid->column('id')->sortable()->width('10%');
            $grid->column('title')->width("20%");
            $grid->column('value')->width("50%")->setAttributes(['style'=> 'word-break:break-all;'])->if(function($column){
                if($this->input_type == 'text'){
                    return $column->textarea();
                }elseif($this->input_type == 'onoff'){
                    return $column->switch();
                }elseif($this->input_type == 'redio'){
                    return $column->radio(explode(' ', $this->remark));
                }elseif($this->input_type == 'select'){
                    return $column->select(explode(' ', $this->remark));
                }
            });
            if(config('admin.setting.line_button_show') == false){
                $grid->disableViewButton();
                $grid->disableEditButton();
                $grid->disableDeleteButton();
                $grid->disableQuickEditButton();
                $grid->disableToolbar();
            }
            $grid->disableRowSelector();
            $grid->withBorder();
            $grid->disableRefreshButton();
            $grid->disableFilterButton();
            $grid->disablePagination();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
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
    protected function detail($id)
    {
        return Show::make($id, new SysSetting(), function (Show $show) {
            $show->field('id');
            $show->field('parent_id');
            $show->field('title');
            $show->field('input_type');
            $show->field('value');
            $show->field('remark');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new SysSetting(), function (Form $form) {
            $form->hidden('value');
            $form->input('remark', $form->model()->remark);

            $form->display('id');
            $form->select('parent_id')->options((new SysSetting())->get_parent_list());
            $form->text('title');
            $form->hidden('remark');
            $form->select('input_type')->options(['text'=> '普通字符', 'select'=> '下拉选项', 'redio'=> '单选项', 'onoff'=> '开关'])->when(['select', 'redio'], function(Form $form){
                $form->text('remark')->help('仅在select、redio类型的表单中有效，每个选项以空格隔开');
            });
            $form->footer(function ($footer) {
                $footer->disableViewCheck();
            });
            $form->saving(function (Form $form) {
                $form->value = $form->value ?? '';
                if($form->isCreating()){
                    $form->parent_id = $form->parent_id ?? 0;
                }
                if($form->remark == null){
                    $form->remark = '';
                }
            });
            $form->saved(function(Form $form, $result){
                (new SysSetting())->del_cache_data($form->id);
            });
            $form->disableResetButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
        });
    }
}
