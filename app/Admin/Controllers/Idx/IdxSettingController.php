<?php

namespace App\Admin\Controllers\Idx;

use Illuminate\Support\Facades\Request;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Admin;
use Dcat\Admin\Http\Controllers\AdminController;

use App\Admin\Repositories\Idx\IdxSetting;


/**
 * 此控制器用于项目内的各种内容选项，这些内容选项大多是由多条数据、多字段数据 (单字段数据由系统设置和广告设置就可完成)
 * 例如：
 *  汽车品牌，有 中文名、英文名、LOGO等数据；
 *  打赏礼物，有 礼物名、图片、单价等数据；
 *
 * 使用方式：
 *  在设置路由时，一般为 admin/setting/***，其中 *** 即表示当前项目的设置类型，
 *  比如，$router->resource('setting/gift', 'Idx\IdxSettingController');
 *  以上即表示获取字段 type 的数据为 gift 的所有行。
 *  如果有多项分类，则只需要设置多个路由即可。
 *
 *  如果需要添加一类设置，则需要在 /app/Models/Idx/IdxSetting.php 中添加以下格式的方法：
 *      添加方法:
 *          public static function <type>_fields(){
 *              return ['<字段说明>', '<字段说明>', '<字段说明>', .......];
 *          }
 *      修改方法 type_page_attribute():
 *          在返回数组中，添加一个键名为 type 数据的数组，并且值也是一个数组，至少有一个键名为 title 的元素
 *          '<type>'=> [
 *              'title'=> '<标题>',
 *          ],
 */
class IdxSettingController extends AdminController{
    protected int $id;
    protected string $type;
    protected int $delete_allowed;
    protected int $update_allowed;

    protected $setting_type;
    protected $title;

    /**
     * 设置标题
     */
    public function __construct(){
        $this->setting_type = explode('/', explode('?', Request::getRequestUri())[0])[3];
        $this->title = (new IdxSetting())->get_type_page_attribute($this->setting_type)['title'];
    }

    protected function grid(){
        $setting_type = $this->setting_type;
        return Grid::make((new IdxSetting())->model()->where("type", $setting_type), function (Grid $grid) use($setting_type){
            // $grid->column('id')->sortable();
            $type_fields = (new IdxSetting())->get_type_fields($setting_type);
            $i = 0;
            foreach($type_fields as $field){
                $grid->column('value' . strval($i), $field);
                $i += 1;
            }
            // $grid->column('created_at');
            // $grid->column('updated_at')->sortable();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                if($this->delete_allowed == 0){
                    $actions->disableDelete();
                }
                if($this->update_allowed == 0){
                    $actions->disableEdit();
                }
            });
            $grid->disableViewButton();
        });
    }

    protected function form(){
        $setting_type = $this->setting_type;
        return Form::make(new IdxSetting(), function (Form $form) use($setting_type){
            $form->hidden('id');
            $form->hidden('type')->value($setting_type);
            $type_fields = (new IdxSetting())->get_type_fields($setting_type);
            $i = 0;
            foreach($type_fields as $field){
                $form->text('value' . strval($i), $field);
                $i += 1;
            }
            if(config('admin.developer_mode')){
                $form->switch("delete_allowed", '是否允许删除')->value(1);
                $form->switch("update_allowed", '是否允许修改')->value(1);
            }
            $form->footer(function ($footer) {
                $footer->disableViewCheck();
                $footer->disableEditingCheck();
                $footer->disableCreatingCheck();
            });
            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
                $tools->disableDelete();
            });
        });
    }
}
