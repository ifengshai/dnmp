<?php

namespace app\admin\controller\operatedatacenter\goodsdata;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class GoodStatus extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->item_platform = new ItemPlatformSku();
    }

    public function index()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key=>$val){
            if(!in_array($val['name'],['zeelool','voogueme','nihao'])){
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('magentoplatformarr'));
        return $this->view->fetch();
    }

    /**
     * 商品状态分析柱状图
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/11/18
     * Time: 15:08:36
     */
    public function ajax_histogram(){

        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            $json['xColumnName'] = ['zeelool','voogueme','nihao','wesee','zeelool-de','zeelool-es','zeelool-jp','meeloog'];
            $json['columnData'] = [
                [
                    'type' => 'bar',
                    'barWidth' => '20%',
                    'data' => [1,2,3],
                    'name' => '客单价'
                ],
                [
                    'type' => 'bar',
                    'barWidth' => '20%',
                    'data' => [4,5,6],
                    'name' => '中位数'
                ],
                [
                    'type' => 'bar',
                    'barWidth' => '20%',
                    'data' => [7,8,9],
                    'name' => '标准差'
                ]

            ];
            return json(['code' => 1, 'data'=>$json]);
        }
    }

    /**
     * 各站点镜框产品重复比例及数量统计
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/11/18
     * Time: 15:09:24
     */
    public function glass_same_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $platform_a = $params['platform_a'];
            $platform_a_name = $params['platform_a_name'];
            $platform_b = $params['platform_b'];
            $platform_b_name = $params['platform_b_name'];
            //镜框数量
            $platform_a_num =$this->item_platform->where('platform_type',$platform_a)->count();
            $platform_b_num =$this->item_platform->where('platform_type',$platform_b)->count();


            $json['column'] = [$platform_a_name, $platform_b_name];
            $json['columnData'] = [
                [
                    'name' => $platform_a_name,
                    'value' => $platform_a_num,
                ],
                [
                    'name' => $platform_b_name,
                    'value' => $platform_b_num,
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }
}
