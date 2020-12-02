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
            $json['xColumnName'] = ['zeelool','voogueme','nihao','wesee','meeloog','zeelool-es','zeelool-de','zeelool-jp'];
            $item = new \app\admin\model\itemmanage\Item();
            $map = [];
            if ($order_platform == 1){
                $skus = $item->getFrameSku();
                $map['sku'] = ['in', $skus];
            }elseif ($order_platform == 2){
                $skus = $item->getOrnamentsSku();
                $map['sku'] = ['in', $skus];
            }else{
                $map = [];
            }
            $platform_z_up_num =$this->item_platform->where('platform_type',1)->where($map)->where('outer_sku_status',1)->count();
            $platform_z_down_num =$this->item_platform->where('platform_type',1)->where($map)->where('outer_sku_status',2)->count();
            $platform_z_yushou_num =$this->item_platform->where('platform_type',1)->where($map)->where('presell_status',1)->count();
            $platform_v_up_num =$this->item_platform->where('platform_type',2)->where($map)->where('outer_sku_status',1)->count();
            $platform_v_down_num =$this->item_platform->where('platform_type',2)->where($map)->where('outer_sku_status',2)->count();
            $platform_v_yushou_num =$this->item_platform->where('platform_type',2)->where($map)->where('presell_status',1)->count();
            $platform_n_up_num =$this->item_platform->where('platform_type',3)->where($map)->where('outer_sku_status',1)->count();
            $platform_n_down_num =$this->item_platform->where('platform_type',3)->where($map)->where('outer_sku_status',2)->count();
            $platform_n_yushou_num =$this->item_platform->where('platform_type',3)->where($map)->where('presell_status',1)->count();
            $platform_w_up_num =$this->item_platform->where('platform_type',4)->where($map)->where('outer_sku_status',1)->count();
            $platform_w_down_num =$this->item_platform->where('platform_type',4)->where($map)->where('outer_sku_status',2)->count();
            $platform_w_yushou_num =$this->item_platform->where('platform_type',4)->where($map)->where('presell_status',1)->count();
            $platform_m_up_num =$this->item_platform->where('platform_type',5)->where($map)->where('outer_sku_status',1)->count();
            $platform_m_down_num =$this->item_platform->where('platform_type',5)->where($map)->where('outer_sku_status',2)->count();
            $platform_m_yushou_num =$this->item_platform->where('platform_type',5)->where($map)->where('presell_status',1)->count();
            $platform_es_up_num =$this->item_platform->where('platform_type',9)->where($map)->where('outer_sku_status',1)->count();
            $platform_es_down_num =$this->item_platform->where('platform_type',9)->where($map)->where('outer_sku_status',2)->count();
            $platform_es_yushou_num =$this->item_platform->where('platform_type',9)->where($map)->where('presell_status',1)->count();
            $platform_de_up_num =$this->item_platform->where('platform_type',10)->where($map)->where('outer_sku_status',1)->count();
            $platform_de_down_num =$this->item_platform->where('platform_type',10)->where($map)->where('outer_sku_status',2)->count();
            $platform_de_yushou_num =$this->item_platform->where('platform_type',10)->where($map)->where('presell_status',1)->count();
            $platform_jp_up_num =$this->item_platform->where('platform_type',11)->where($map)->where('outer_sku_status',1)->count();
            $platform_jp_down_num =$this->item_platform->where('platform_type',11)->where($map)->where('outer_sku_status',2)->count();
            $platform_jp_yushou_num =$this->item_platform->where('platform_type',11)->where($map)->where('presell_status',1)->count();

            $json['columnData'] = [
                [
                    'type' => 'bar',
                    'barWidth' => '10%',
                    'data' => [$platform_z_up_num,$platform_v_up_num,$platform_n_up_num,$platform_w_up_num,$platform_m_up_num,$platform_es_up_num,$platform_de_up_num,$platform_jp_up_num],
                    'name' => '在售'
                ],
                [
                    'type' => 'bar',
                    'barWidth' => '10%',
                    'data' => [$platform_z_yushou_num,$platform_v_yushou_num,$platform_n_yushou_num,$platform_w_yushou_num,$platform_m_yushou_num,$platform_es_yushou_num,$platform_de_yushou_num,$platform_jp_yushou_num],
                    'name' => '预售'
                ],
                [
                    'type' => 'bar',
                    'barWidth' => '10%',
                    'data' => [$platform_z_down_num,$platform_v_down_num,$platform_n_down_num,$platform_w_down_num,$platform_m_down_num,$platform_es_down_num,$platform_de_down_num,$platform_jp_down_num],
                    'name' => '下架'
                ],
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
            //站点a id
            $platform_a = $params['platform_a'];
            //站点a名称
            $platform_a_name = $params['platform_a_name'];
            //站点b id
            $platform_b = $params['platform_b'];
            //站点b名称
            $platform_b_name = $params['platform_b_name'];
            $item = new \app\admin\model\itemmanage\Item();
            //获取仓库镜架SKU
            $skus = $item->getFrameSku();
            $map['sku'] = ['in', $skus];
            //镜框数量
            $platform_a_num =$this->item_platform->where('platform_type',$platform_a)->where($map)->count();
            $platform_b_num =$this->item_platform->where('platform_type',$platform_b)->where($map)->count();

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

    /**
     * 获取重复数量和重复占比
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/11/19
     * Time: 10:37:44
     */
    public function again_glass_same_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $platform_a = $params['platform_a'];
            $platform_b = $params['platform_b'];

            $item = new \app\admin\model\itemmanage\Item();
            //获取仓库镜架SKU
            $skus = $item->getFrameSku();
            $map['sku'] = ['in', $skus];
            $again_num = $this->item_platform
                ->where('platform_type','in',[$platform_a,$platform_b])
                ->where($map)
                ->group('sku')
                ->having('count(platform_type)>1')
                ->count();
            //镜框数量
            $platform_a_num =$this->item_platform->where('platform_type',$platform_a)->where($map)->count();
            // dump($platform_a_num);
            $again_rate = round($again_num/$platform_a_num * 100,2).'%';

            $data = compact('again_num','again_rate');
            $this->success('', '', $data);
        }
    }
}
