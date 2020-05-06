<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\Common\model\Auth;

class Test extends Backend
{

    public function _initialize()
    {
        parent::_initialize();

        $this->newproduct = new \app\admin\model\NewProduct();
        $this->item = new \app\admin\model\itemmanage\Item();
    }

    /**
     * 更新采购负责人
     *
     * @Description
     * @author wpl
     * @since 2020/04/29 15:43:38 
     * @return void
     */
    public function test()
    {
        $ids = [];
        $person = '';
        $str = explode('
        ', $person);
        $supplier = new \app\admin\model\purchase\Supplier();
        foreach ($ids as $k => $v) {
            $supplier->where('id', $v)->update(['purchase_person' => $str[$k]]);
        }
    }

    public function demo()
    {
        $str = 'a:2:{s:15:"info_buyRequest";a:6:{s:7:"product";s:4:"1659";s:8:"form_key";s:16:"kC53WT57r1ZZ0NHk";s:3:"qty";i:1;s:7:"options";a:1:{i:1561;s:4:"1923";}s:13:"cart_currency";s:3:"USD";s:7:"tmplens";a:26:{s:19:"frame_regural_price";d:36.950000000000003;s:11:"frame_price";d:20;s:12:"prescription";s:278:"prescription_type=Progressive&od_sph=-1.50&od_cyl=-1.25&od_axis=1&os_sph=-1.25&os_cyl=-1.00&os_axis=1&pdcheck=on&pd_r=27.50&pd_l=28.00&pd=&os_add=1.00&od_add=1.25&prismcheck=on&od_pv=0.50&od_bd=In&od_pv_r=0.25&od_bd_r=Down&os_pv=1.25&os_bd=Out&os_pv_r=0.75&os_bd_r=Down&save=123";s:11:"lenstype_id";s:11:"lenstype_13";s:13:"lenstype_name";s:19:"Blue Light Blocking";s:21:"lenstype_regual_price";i:50;s:14:"lenstype_price";d:50;s:19:"lenstype_base_price";d:50;s:7:"lens_id";s:13:"refractive_38";s:9:"lens_name";s:9:"Recommend";s:10:"lens_index";s:4:"1.61";s:17:"lens_regual_price";i:20;s:10:"lens_price";d:20;s:15:"lens_base_price";d:20;s:8:"color_id";s:0:"";s:10:"color_name";N;s:18:"color_regual_price";N;s:11:"color_price";i:0;s:16:"color_base_price";N;s:10:"coating_id";s:0:"";s:12:"coating_name";N;s:13:"coating_price";i:0;s:18:"coating_base_price";N;s:3:"rid";N;s:4:"lens";d:70;s:5:"total";d:90;}}s:7:"options";a:1:{i:0;a:7:{s:5:"label";s:5:"Color";s:5:"value";s:6:"Purple";s:11:"print_value";s:6:"Purple";s:9:"option_id";s:4:"1561";s:11:"option_type";s:9:"drop_down";s:12:"option_value";s:4:"1923";s:11:"custom_view";b:0;}}}';
        $arr = unserialize($str);
        dump($arr);
    }
}
