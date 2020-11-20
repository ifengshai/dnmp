<?php

namespace app\admin\controller\operatedatacenter\userdata;

use app\common\controller\Backend;
use think\Controller;
use think\Db;
use think\Request;

class UserValueRfm extends Backend
{
    public function _initialize()
    {
        parent::_initialize();
        $this->zeelool = new \app\admin\model\order\order\Zeelool();
        $this->voogueme = new \app\admin\model\order\order\Voogueme();
        $this->nihao = new \app\admin\model\order\order\Nihao();
        $this->zeeloolOperate = new \app\admin\model\operatedatacenter\Zeelool;
        $this->vooguemeOperate = new \app\admin\model\operatedatacenter\Voogueme;
        $this->nihaoOperate = new \app\admin\model\operatedatacenter\Nihao;
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }

    /**
     * 用户平台贡献分布
     *
     * @return \think\Response
     */
    public function user_contribution_distribution()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    /*
     * ajax获取用户消费金额分布
     * */
    public function ajax_user_order_amount()
    {

        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            $data1 = $this->getOrderAmountUserNum($order_platform, 1);
            $data2 = $this->getOrderAmountUserNum($order_platform, 2);
            $data3 = $this->getOrderAmountUserNum($order_platform, 3);
            $data4 = $this->getOrderAmountUserNum($order_platform, 4);
            $data5 = $this->getOrderAmountUserNum($order_platform, 5);
            $data6 = $this->getOrderAmountUserNum($order_platform, 6);

            $data = array($data1['count'], $data2['count'], $data3['count'], $data4['count'], $data5['count'], $data6['count'],
            );
            $json['firtColumnName'] = ['0-40', '40-80', '80-150', '150-200', '200-300', '300+'];
            $json['columnData'] = [[
                'type' => 'bar',
                'barWidth' => '40%',
                'data' => $data,
                'name' => $data1['sum_count'],
                'itemStyle' => [
                    'normal' => [
                        'label' => [
                            'show' => true,
                            'position' => 'right',
                            'formatter'=>"{c}"."人",
                            'textStyle'=>[
                                'color'=> 'black'
                            ],
                        ],
                    ]
                ]
            ]];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /*
     * 获取金额分布人数
     * type  1:[0-40)  2:[40-80)  3:[80-150)   4:[150-200)    5:[200-300)   6:[300,10000000)
    */
    public function getOrderAmountUserNum($order_platform, $type)
    {
        if ($order_platform == 2) {
            $order_model = $this->voogueme;
        } elseif ($order_platform == 3) {
            $order_model = $this->nihao;
        } else {
            $order_model = $this->zeelool;
        }
        $today = date('Y-m-d');
        $start = date('Y-m-d', strtotime("$today -12 month"));
        $end = date('Y-m-d 23:59:59', strtotime($today));
        $where['created_at'] = ['between', [$start, $end]];
        $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        switch ($type) {
            case 1:
                $order_where1['base_grand_total'] = ['>=', 0];
                $order_where2['base_grand_total'] = ['<', 40];
                break;
            case 2:
                $order_where1['base_grand_total'] = ['>=', 40];
                $order_where2['base_grand_total'] = ['<', 80];
                break;
            case 3:
                $order_where1['base_grand_total'] = ['>=', 80];
                $order_where2['base_grand_total'] = ['<', 150];
                break;
            case 4:
                $order_where1['base_grand_total'] = ['>=', 150];
                $order_where2['base_grand_total'] = ['<', 200];
                break;
            case 5:
                $order_where1['base_grand_total'] = ['>=', 200];
                $order_where2['base_grand_total'] = ['<', 300];
                break;
            case 6:
                $order_where1['base_grand_total'] = ['>=', 300];
                $order_where2['base_grand_total'] = ['<', 1000000];
                break;
            default:
                break;
        }
        $count = $order_model->where($where)->where($order_where1)->where($order_where2)->count();
        $sum_count = $order_model->where($where)->count();
        $arr = [
            'count' => $count,
            'sum_count' => $sum_count,
        ];
        return $arr;
    }
    /*
     * ajax获取用户消费次数分布
     * */
    public function ajax_user_order_num()
    {

        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $order_platform = $params['order_platform'];
            $data1 = $this->getOrderAmountUserNum($order_platform, 1);
            $data2 = $this->getOrderAmountUserNum($order_platform, 2);
            $data3 = $this->getOrderAmountUserNum($order_platform, 3);
            $data4 = $this->getOrderAmountUserNum($order_platform, 4);
            $data5 = $this->getOrderAmountUserNum($order_platform, 5);
            $data6 = $this->getOrderAmountUserNum($order_platform, 6);

            $data = array($data1['count'], $data2['count'], $data3['count'], $data4['count'], $data5['count'], $data6['count'],
            );
            $json['firtColumnName'] = ['0-40', '40-80', '80-150', '150-200', '200-300', '300+'];
            $json['columnData'] = [[
                'type' => 'bar',
                'barWidth' => '40%',
                'data' => $data,
                'name' => $data1['sum_count'],
                'itemStyle' => [
                    'normal' => [
                        'label' => [
                            'show' => true,
                            'position' => 'right',
                            'formatter'=>"{c}"."人",
                            'textStyle'=>[
                                'color'=> 'black'
                            ],
                        ],
                    ]
                ]
            ]];
            return json(['code' => 1, 'data' => $json]);
        }
    }
    /*
     * 获取购买用户消费次数分布人数
     * type  1:1  2:2  3:3   4:4    5:5+
    */
    public function getOrderNumUserNum($order_platform, $type)
    {
        if ($order_platform == 2) {
            $order_model = $this->voogueme;
        } elseif ($order_platform == 3) {
            $order_model = $this->nihao;
        } else {
            $order_model = $this->zeelool;
        }
        $today = date('Y-m-d');
        $start = date('Y-m-d', strtotime("$today -12 month"));
        $end = date('Y-m-d 23:59:59', strtotime($today));
        $where['created_at'] = ['between', [$start, $end]];
        $where['status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal']];
        switch ($type) {
            case 1:
                $order_where1['base_grand_total'] = ['>=', 0];
                $order_where2['base_grand_total'] = ['<', 40];
                break;
            case 2:
                $order_where1['base_grand_total'] = ['>=', 40];
                $order_where2['base_grand_total'] = ['<', 80];
                break;
            case 3:
                $order_where1['base_grand_total'] = ['>=', 80];
                $order_where2['base_grand_total'] = ['<', 150];
                break;
            case 4:
                $order_where1['base_grand_total'] = ['>=', 150];
                $order_where2['base_grand_total'] = ['<', 200];
                break;
            case 5:
                $order_where1['base_grand_total'] = ['>=', 200];
                $order_where2['base_grand_total'] = ['<', 300];
                break;
            case 6:
                $order_where1['base_grand_total'] = ['>=', 300];
                $order_where2['base_grand_total'] = ['<', 1000000];
                break;
            default:
                break;
        }
        $count = $order_model->where($where)->where($order_where1)->where($order_where2)->count();
        $sum_count = $order_model->where($where)->count();
        $arr = [
            'count' => $count,
            'sum_count' => $sum_count,
        ];
        return $arr;
    }
    /**
     * 消费临近天数
     *
     * @return \think\Response
     */
    public function user_shopping_near_days()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }
}
