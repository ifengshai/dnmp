<?php

namespace app\admin\controller\order;

use app\admin\model\DistributionAbnormal;
use app\admin\model\order\order\LensData;
use app\admin\model\order\order\NewOrder;
use app\admin\model\order\order\NewOrderItemOption;
use app\admin\model\order\order\NewOrderItemProcess;
use app\admin\model\order\order\NewOrderProcess;
use app\admin\model\warehouse\StockHouse;
use app\common\controller\Backend;
use fast\Trackingmore;
use Think\Log;
use Util\NihaoPrescriptionDetailHelper;
use Util\ZeeloolPrescriptionDetailHelper;
use Util\VooguemePrescriptionDetailHelper;
use Util\WeseeopticalPrescriptionDetailHelper;
use Util\MeeloogPrescriptionDetailHelper;
use Util\ZeeloolEsPrescriptionDetailHelper;
use Util\ZeeloolDePrescriptionDetailHelper;
use Util\ZeeloolJpPrescriptionDetailHelper;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use think\Exception;
use think\Loader;
use think\Db;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


/**
 * 订单列表
 */
class Index extends Backend  /*这里继承的是app\common\controller\Backend*/
{
    protected $noNeedRight = ['orderDetail', 'batch_print_label_new', 'batch_export_xls', 'account_order_batch_export_xls'];
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->nihao = new \app\admin\model\order\order\Nihao;
        $this->zeelool = new \app\admin\model\order\order\Zeelool;
        $this->voogueme = new \app\admin\model\order\order\Voogueme;
        $this->weseeoptical = new \app\admin\model\order\order\Weseeoptical;
        $this->meeloog = new \app\admin\model\order\order\Meeloog;
        $this->rufoo = new \app\admin\model\order\order\Rufoo;
        $this->zeelool_es = new \app\admin\model\order\order\ZeeloolEs;
        $this->zeelool_de = new \app\admin\model\order\order\ZeeloolDe;
        $this->zeelool_jp = new \app\admin\model\order\order\ZeeloolJp;
        $this->ordernodedeltail = new \app\admin\model\order\order\Ordernodedeltail;

        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 订单列表
     *
     * @Description
     * @author wpl
     * @since 2020/11/16 09:42:29 
     * @return void
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $filter = json_decode($this->request->get('filter'), true);
            //默认Z站数据
            if (!$filter['site']) {
                $map['site'] = 1;
            }

            // //SKU搜索
            // if ($filter['sku']) {
            //     $smap['sku'] = ['like', $filter['sku'] . '%'];
            //     if ($filter['status']) {
            //         $smap['status'] = ['in', $filter['status']];
            //     }
            //     $ids = $this->orderitemoption->where();
            //     $map['entity_id'] = ['in', $ids];
            //     unset($filter['sku']);
            //     $this->request->get(['filter' => json_encode($filter)]);
            // }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->order
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->order
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $arr = [
                'Business express(4-7 business days)',
                'Expedited',
                'Business express(7-14 Days)',
                'Business express(7-12 Days)',
                'Business express',
                'Business express (7-12 days)',
                'Business express(7-12 days)',
                'Express Shipping (3-5 Days)',
                'Express Shipping (5-8Days)',
                'Express Shipping (3-5 Business Days)',
                'Express Shipping (5-8 Business Days)',
                'Business Express(7-12 Days)',
                'Business express(7-12 business days)'
            ];
            foreach ($list as &$v) {
                if ($v['shipping_method'] == 'tablerate_bestway') {
                    $v['label'] = 1;
                } else {
                    $v['label'] = 0;
                }
                $v['created_at'] = date('Y-m-d H:i:s', $v['created_at']);
            }
            unset($v);

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //选项卡
        $this->assign('getTabList', $this->order->getTabList());
        return $this->view->fetch();
    }

    /**
     * 查看
     */
    public function index_bak()
    {
        $label = $this->request->get('label', 1);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //根据传的标签切换对应站点数据库
            switch ($label) {
                case 1:
                    $db = 'database.db_zeelool';
                    $model = $this->zeelool;
                    break;
                case 2:
                    $db = 'database.db_voogueme';
                    $model = $this->voogueme;
                    break;
                case 3:
                    $db = 'database.db_nihao';
                    $model = $this->nihao;
                    break;
                case 4:
                    $db = 'database.db_weseeoptical';
                    $model = $this->weseeoptical;
                    break;
                case 5:
                    $db = 'database.db_meeloog';
                    $model = $this->meeloog;
                    break;
                case 9:
                    $db = 'database.db_zeelool_es';
                    $model = $this->zeelool_es;
                    break;
                case 10:
                    $db = 'database.db_zeelool_de';
                    $model = $this->zeelool_de;
                    break;
                case 11:
                    $db = 'database.db_zeelool_jp';
                    $model = $this->zeelool_jp;
                    break;
                default:
                    return false;
                    break;
            }

            $filter = json_decode($this->request->get('filter'), true);
            //SKU搜索
            if ($filter['sku']) {
                $smap['sku'] = ['like', $filter['sku'] . '%'];
                if ($filter['status']) {
                    $smap['status'] = ['in', $filter['status']];
                }
                $ids = $model->getOrderId($smap);
                $map['a.entity_id'] = ['in', $ids];
                unset($filter['sku']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $map['b.address_type'] = 'shipping';
            $total = $model->alias('a')->join(['sales_flat_order_address' => 'b'], 'a.entity_id=b.parent_id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $model->alias('a')->field('a.entity_id,increment_id,b.country_id,customer_firstname,customer_email,status,base_grand_total,base_shipping_amount,custom_order_prescription_type,order_type,a.created_at,a.shipping_description')
                ->join(['sales_flat_order_address' => 'b'], 'a.entity_id=b.parent_id')
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $arr = [
                'Business express(4-7 business days)',
                'Expedited',
                'Business express(7-14 Days)',
                'Business express(7-12 Days)',
                'Business express',
                'Business express (7-12 days)',
                'Business express(7-12 days)',
                'Express Shipping (3-5 Days)',
                'Express Shipping (5-8Days)',
                'Express Shipping (3-5 Business Days)',
                'Express Shipping (5-8 Business Days)',
                'Business Express(7-12 Days)',
                'Business express(7-12 business days)'
            ];
            foreach ($list as &$v) {
                if (in_array($v['shipping_description'], $arr)) {
                    $v['label'] = 1;
                } else {
                    $v['label'] = 0;
                }
            }
            unset($v);

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }

    /**
     * 订单详情
     *
     * @Description
     * @author wpl
     * @since 2020/11/16 09:42:40 
     * @param [type] $ids
     * @return void
     */
    public function detail($ids = null)
    {
        if ($_POST){
            $data  = input('param.');
            $value['order_id'] = $data['entity_id'];
            $count = count($data['item_id']);
            for ($i= 0;$i<$count;$i++) {
                $value['order_items'][$i]['order_item_id'] = $data['item_id'][$i];
                $value['order_items'][$i]['od_sph'] = $data['od_sph'][$i];
                $value['order_items'][$i]['od_cyl'] = $data['od_cyl'][$i];
                $value['order_items'][$i]['od_axis'] = $data['od_axis'][$i];
                $value['order_items'][$i]['os_sph']= $data['os_sph'][$i];
                $value['order_items'][$i]['os_cyl'] = $data['os_cyl'][$i];
                $value['order_items'][$i]['os_axis'] = $data['os_axis'][$i];
                if ($data['pd_l'][$i] ==null && $data['pd_r'][$i] ){
                    $value['order_items'][$i]['pd'] = $data['pd_r'][$i];
                }else{
                    $value['order_items'][$i]['pd_r'] = $data['pd_r'][$i];
                    $value['order_items'][$i]['pd_l'] = $data['pd_l'][$i];
                    $value['order_items'][$i]['pdcheck'] = 'on';
                }
                $value['order_items'][$i]['od_pv'] = $data['od_pv'][$i];
                $value['order_items'][$i]['os_pv'] = $data['os_pv'][$i];
                $value['order_items'][$i]['od_bd'] = $data['od_bd'][$i];
                $value['order_items'][$i]['os_bd'] = $data['os_bd'][$i];
                $value['order_items'][$i]['od_pv_r'] = $data['od_pv_r'][$i];
                $value['order_items'][$i]['os_pv_r'] = $data['os_pv_r'][$i];
                $value['order_items'][$i]['od_bd_r'] = $data['od_bd_r'][$i];
                $value['order_items'][$i]['os_bd_r'] = $data['os_bd_r'][$i];
                $value['order_items'][$i]['os_add'] = $data['os_add'][$i];
                $value['order_items'][$i]['od_add'] = $data['od_add'][$i];
                $value['order_items'][$i]['prescription_type'] = $data['prescription_type'][$i];
                if ($data['od_pv'][$i] !== null && $data['od_pv_r'][$i] !==null && $data['os_pv'][$i] !== null && $data['os_pv_r'][$i]){
                    $value['order_items'][$i]['prismcheck'] = 'on';
                }else{
                    $value['order_items'][$i]['prismcheck'] = '';
                }
            }
            //请求接口
            $url = config('url.zeelooles_url').'magic/order/prescriptionPicCheck';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($value));
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 20);
            $content =json_decode(curl_exec($curl),true);
            curl_close($curl);
            if ($content['status'] == 200){
                $this->success('操作成功');
            }else{
                $this->error('操作失败,原因:'.$content['msg']);
            }
        }
        $ids = $ids ?? $this->request->get('id');
        //查询订单详情
        $row = $this->order->get($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        //获取支付信息
        $pay = $this->zeelool->getPayDetail($row->site, $row->entity_id);

        //订单明细数据
        $item = $this->orderitemoption->where('order_id', $ids)->select();
        $items = collection($item)->toArray();
        foreach ($items as $key=>$item){
            $items[$key]['total'] = number_format($item['total'],2);
            $items[$key]['frame_price'] = number_format($item['frame_price'],2);
            $items[$key]['index_price'] = number_format($item['index_price'],2);
            $items[$key]['coating_price'] = number_format($item['coating_price'],2);
            if ($item['site'] ==9){
                if ($item['prescription_pic_checked'] == false && $item['prescription_pic_id']>0){
                    $items[$key]['to_examine']= true;
                }else{
                    $items[$key]['to_examine'] = false;
                }
                if ($item['prescription_pic_id'] > 0){
                    $items[$key]['prescription_image'] = Db::connect('database.db_zeelool_es')->table('oc_prescription_pic')->where('id',$item['prescription_pic_id'])->value('pic');
                }else{
                    $items[$key]['prescription_image'] = null;
                }
            }
        }
        $this->view->assign("item", $items);
        $this->view->assign("row", $row);
        $this->view->assign("pay", $pay);
        return $this->view->fetch();
    }

    /**
     * 订单节点
     *
     * @Description
     * @author wpl
     * @since 2020/11/16 09:42:52 
     * @param [type] $order_number
     * @return void
     */
    public function orderDetail($order_number = null)
    {

        $new_order = new NewOrder();
        $new_order_process = new NewOrderProcess();
        $order_number = $order_number ?? $this->request->get('order_number');

        $new_order_item_process_id =$new_order->alias('a')
            ->join(['fa_order_item_process' => 'b'], 'a.id=b.order_id')
            ->where('a.increment_id',$order_number)
            ->field('b.id,b.sku,b.distribution_status')
            ->select();
        $new_order_item_process_id2 = array_column($new_order_item_process_id,'sku','id');
        $is_shendan = $new_order_process->where('increment_id',$order_number)->field('check_time,check_status,delivery_time')->find();
        //子单节点日志
        foreach ($new_order_item_process_id as $k=>$v){
            $distribution_log[$v['id']] = Db::name('distribution_log')->where('item_process_id',$v['id'])->select();
        }

        $new_order_item_process_id1 =array_column($new_order_item_process_id, 'id');
        $distribution_log_times = Db::name('distribution_log')
            ->where('item_process_id','in',$new_order_item_process_id1)
            ->where('distribution_node',1)
            ->order('create_time asc')
            ->column('create_time');

        //查询订单详情
        $ruleList = collection($this->ordernodedeltail->where(['order_number' => ['eq', $order_number]])->order('node_type asc')->field('node_type,create_time,handle_user_name,shipment_type,track_number')->select())->toArray();

        $new_ruleList = array_column($ruleList, NULL, 'node_type');
        $key_list = array_keys($new_ruleList);

        $id = $this->request->get('id');
        $label = $this->request->get('label', 1);

        $this->view->assign(compact('order_number', 'id', 'label'));
        $this->view->assign("list", $new_ruleList);
        $this->view->assign("is_shendan", $is_shendan);
        $this->view->assign("distribution_log_times", $distribution_log_times);
        $this->view->assign("distribution_log", $distribution_log);
        $this->view->assign("key_list", $key_list);
        $this->view->assign("new_order_item_process_id2", $new_order_item_process_id2);
        return $this->view->fetch();
    }

    /**
     * 订单成本核算 create@lsw
     */
    public function account_order()
    {
        $label = $this->request->get('label', 1);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {

                return $this->selectpage();
            }
            $rep    = $this->request->get('filter');

            $addWhere = '1=1';
            if ($rep != '{}') {
//                 $whereArr = json_decode($rep,true);
//                 if(!array_key_exists('created_at',$whereArr)){
//                     $addWhere  .= " AND DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(created_at)";
//                 }
            }else {
                $addWhere  .= " AND DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(created_at)";
            }

            //根据传的标签切换对应站点数据库
            $label = $this->request->get('label', 1);
            $where_order['replenish_money'] =['gt',0];
            if ($label == 1) {
                $model = $this->zeelool;
                $where_order['work_platform'] = ['eq',1];
            } elseif ($label == 2) {
                $model = $this->voogueme;
                $where_order['work_platform'] = ['eq',2];
            } elseif ($label == 3) {
                $model = $this->nihao;
                $where_order['work_platform'] = ['eq',3];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $model
                ->where($where)
//                ->field('increment_id,customer_firstname,customer_email,status,base_grand_total,base_shipping_amount,custom_order_prescription_type,order_type,created_at,base_total_paid,base_total_due')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $totalId = $model
                ->where($where)
                ->where($addWhere)
                ->whereNotIn('order_type',['3','4'])
                ->column('entity_id');

            $thisPageId = $model
                ->where($where)
                ->order($sort, $order)
                ->whereNotIn('order_type',['3','4'])
                ->limit($offset, $limit)
                ->column('entity_id');

            $costInfo = $model->getOrderCostInfo($totalId, $thisPageId);
         
            $list = collection($list)->toArray();

            foreach ($list as $k => $v) {
                //原先
                // if(isset($costInfo['thisPagePayPrice'])){
                //     if(array_key_exists($v['entity_id'],$costInfo['thisPagePayPrice'])){
                //         $list[$k]['total_money'] = $costInfo['thisPagePayPrice'][$v['entity_id']];
                //    }
                // }
                //订单支付金额
                if (in_array($v['status'], ['processing', 'complete', 'creditcard_proccessing', 'free_processing'])) {
                    //$costInfo['totalPayInfo'] +=  round($v['base_total_paid']+$v['base_total_due'],2);
                    $list[$k]['total_money']      =  round($v['base_total_paid'] + $v['base_total_due'], 2);
                }
                //订单镜架成本
                if (isset($costInfo['thispageFramePrice'])) {
                    if (array_key_exists($v['increment_id'], $costInfo['thispageFramePrice'])) {
                        $list[$k]['frame_cost']   = $costInfo['thispageFramePrice'][$v['increment_id']];
                    }
                }
                //订单镜片成本
                if (isset($costInfo['thispageLensPrice'])) {
                    if (array_key_exists($v['increment_id'], $costInfo['thispageLensPrice'])) {
                        $list[$k]['lens_cost']    = $costInfo['thispageLensPrice'][$v['increment_id']];
                    }
                }
                //订单退款金额
                if (isset($costInfo['thispageRefundMoney'])) {
                    if (array_key_exists($v['increment_id'], $costInfo['thispageRefundMoney'])) {
                        $list[$k]['refund_money'] = $costInfo['thispageRefundMoney'][$v['increment_id']];
                    }
                }
                //订单补差价金额
                if (isset($costInfo['thispageFullPostMoney'])) {
                    if (array_key_exists($v['increment_id'], $costInfo['thispageFullPostMoney'])) {
                        $list[$k]['fill_post']    = $costInfo['thispageFullPostMoney'][$v['increment_id']];
                    }
                }
                //订单加工费
                if (isset($costInfo['thisPageProcessCost'])) {
                    if (array_key_exists($v['entity_id'], $costInfo['thisPageProcessCost'])) {
                        $list[$k]['process_cost'] = $costInfo['thisPageProcessCost'][$v['entity_id']];
                    }
                }
                //查询工单里是否有补差价记录
                $mojing = Db::connect('mysql://fanzhigang:3QGz60R2E!@aVOXP@54.189.215.133:3306/mojing#utf8');
                $where_order['platform_order'] = ['eq',$v['increment_id']];
                $work_order_list = $mojing->table('fa_work_order_list')->where($where_order)->field('replenish_money')->select();
                if (!empty($work_order_list)){
                    $work_order_list = array_column($work_order_list,'replenish_money');
                    $difference_log = implode(',',$work_order_list);
                }
                if ($v['fill_post'] == null){
                    $list[$k]['fill_post'] = '-';
                }else{
                    $list[$k]['fill_post'] = $v['fill_post'].'-'.$difference_log;
                }

            }
            $result = array(
                "total"             =>  $total,
                "rows"              =>  $list,
                "totalPayInfo"      =>  round($costInfo['totalPayInfo'], 2),
                "totalLensPrice"    =>  round($costInfo['totalLensPrice'], 2),
                "totalFramePrice"   =>  round($costInfo['totalFramePrice'], 2),
                "totalPostageMoney" =>  round($costInfo['totalPostageMoney'], 2),
                "totalRefundMoney"  =>  round($costInfo['totalRefundMoney'], 2),
                "totalFullPostMoney" =>  round($costInfo['totalFullPostMoney'], 2),
                "totalProcessCost"  =>  round($costInfo['totalProcessCost'], 2)
            );
            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }
    /***
     * 导入邮费页面 create@lsw
     */
    public function postage_import()
    {
        $label = $this->request->get('label', 1);
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //根据传的标签切换对应站点数据库
            $label = $this->request->get('label', 1);
            if ($label == 1) {
                $model = $this->zeelool;
            } elseif ($label == 2) {
                $model = $this->voogueme;
            } elseif ($label == 3) {
                $model = $this->nihao;
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assign('label', $label);
        $this->assignconfig('label', $label);
        return $this->view->fetch();
    }

    /***
     * 邮费导入   create@lsw
     */
    public function import()
    {

        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, "w");
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        //$importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';
        //模板文件列名
        $listName = ['订单号', '邮费'];
        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }
            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);

            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }

            //模板文件不正确
            if ($listName !== $fields) {
                throw new Exception("模板文件不正确！！");
            }

            $data = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $data[$currentRow - 2][$currentColumn - 1] = is_null($val) ? '' : $val;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
        $model = $this->zeelool;
        foreach ($data as $k => $v) {
            $increment_id = $v[0];
            $postage_money = $v[1];
            $result = $model->updatePostageMoney($increment_id, $postage_money);
            if ($result === false) {
                $this->error($this->model->getError());
            }
        }
        $this->success();
    }


    /**
     * 生成新的条形码
     */
    protected function generate_barcode_new($text, $fileName)
    {
        // 引用barcode文件夹对应的类
        Loader::import('BCode.BCGFontFile', EXTEND_PATH);
        //Loader::import('BCode.BCGColor',EXTEND_PATH);
        Loader::import('BCode.BCGDrawing', EXTEND_PATH);
        // 条形码的编码格式
        // Loader::import('BCode.BCGcode39',EXTEND_PATH,'.barcode.php');
        Loader::import('BCode.BCGcode128', EXTEND_PATH, '.barcode.php');

        // $code = '';
        // 加载字体大小
        $font = new \BCGFontFile(EXTEND_PATH . '/BCode/font/Arial.ttf', 18);
        //颜色条形码
        $color_black = new \BCGColor(0, 0, 0);
        $color_white = new \BCGColor(255, 255, 255);
        $label = new \BCGLabel();
        $label->setPosition(\BCGLabel::POSITION_TOP);
        $label->setText('Made In China');
        $label->setFont($font);
        $drawException = null;
        try {
            // $code = new \BCGcode39();
            $code = new \BCGcode128();
            $code->setScale(4);
            $code->setThickness(18); // 条形码的厚度
            $code->setForegroundColor($color_black); // 条形码颜色
            $code->setBackgroundColor($color_white); // 空白间隙颜色
            $code->setFont($font); //设置字体
            $code->addLabel($label); //设置字体
            $code->parse($text); // 条形码需要的数据内容
        } catch (\Exception $exception) {
            $drawException = $exception;
        }
        //根据以上条件绘制条形码
        $drawing = new \BCGDrawing('', $color_white);
        if ($drawException) {
            $drawing->drawException($drawException);
        } else {
            $drawing->setBarcode($code);
            if ($fileName) {
                // echo 'setFilename<br>';
                $drawing->setFilename($fileName);
            }
            $drawing->draw();
        }
        // 生成PNG格式的图片
        header('Content-Type: image/png');
        // header('Content-Disposition:attachment; filename="barcode.png"'); //自动下载
        $drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);
    }

    /**
     * 批量打印标签
     *
     * @Description
     * @author wpl
     * @since 2020/11/16 09:58:44 
     * @return void
     */
    public function batch_print_label_new()
    {
        ob_start();
        $ids = rtrim(input('id_params'), ',');
        if (!$ids) {
            return $this->error('缺少参数', url('index?ref=addtabs'));
        }

        $row = $this->order->where(['id' => ['in', $ids]])->where(['country_id' => ['in', ['US', 'PR']]])->select();

        $file_header = <<<EOF
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
body{ margin:0; padding:0}
.single_box{margin:0 auto;width: 400px;padding:1mm;margin-bottom:2mm;}
table.addpro {clear: both;table-layout: fixed; margin-top:6px; border-top:1px solid #000;border-left:1px solid #000; font-size:12px;}
table.addpro .title {background: none repeat scroll 0 0 #f5f5f5; }
table.addpro .title  td {border-collapse: collapse;color: #000;text-align: center; font-weight:normal; }
table.addpro tbody td {word-break: break-all; text-align: center;border-bottom:1px solid #000;border-right:1px solid #000;}
table.addpro.re tbody td{ position:relative}
</style>
EOF;

        $arr = [
            'Business express(4-7 business days)',
            'Expedited',
            'Business express(7-14 Days)',
            'Business express(7-12 Days)',
            'Business express',
            'Business express (7-12 days)',
            'Business express(7-12 days)',
            'Express Shipping (3-5 Days)',
            'Express Shipping (5-8Days)',
            'Express Shipping (3-5 Business Days)',
            'Express Shipping (5-8 Business Days)',
            'Business Express(7-12 Days)'
        ];

        $file_content = '';
        $temp_increment_id = 0;
        foreach ($row as $processing_key => $processing_value) {
            if (in_array($processing_value['shipping_title'], $arr)) {
                continue;
            }

            if ($temp_increment_id != $processing_value['increment_id']) {
                $temp_increment_id = $processing_value['increment_id'];

                $date = substr($processing_value['created_at'], 0, strpos($processing_value['created_at'], " "));
                $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "zeelool" . DS . "new" . DS . "$date" . DS . "$temp_increment_id.png";
                // dump($fileName);
                $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "printOrder" . DS . "zeelool" . DS . "new"  . DS . "$date";
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                    // echo '创建文件夹$dir成功';
                } else {
                    // echo '需创建的文件夹$dir已经存在';
                }
                $img_url = "/uploads/printOrder/zeelool/new/$date/$temp_increment_id.png";
                //生成条形码
                $this->generate_barcode_new($temp_increment_id, $fileName);
                $file_content .= "<div  class = 'single_box'>
                <table width='400mm' height='102px' border='0' cellspacing='0' cellpadding='0' class='addpro' style='margin:0px auto;margin-top:0px;padding:0px;'>
                <tr>
                <td rowspan='5' colspan='3' style='padding:10px;'><img src='" . $img_url . "' height='80%'><br></td></tr>                
                </table></div>";
            }
        }
        echo $file_header . $file_content;
    }

    /**
     * 批量导出xls
     *
     * @Description
     * @author wpl
     * @since 2020/02/28 14:45:39 
     * @return void
     */
    public function batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //根据传的标签切换对应站点数据库
        $label = $this->request->get('label', 1);
        switch ($label) {
            case 1:
                $model = $this->zeelool;
                break;
            case 2:
                $model = $this->voogueme;
                break;
            case 3:
                $model = $this->nihao;
                break;
            case 4:
                $model = $this->weseeoptical;
                break;
            case 5:
                $model = $this->meeloog;
                break;
            case 9:
                $model = $this->zeelool_es;
                break;
            case 10:
                $model = $this->zeelool_de;
                break;
            default:
                return false;
                break;
        }

        $ids = input('ids');
        if ($ids) {
            $map['entity_id'] = ['in', $ids];
        }

        $filter = json_decode($this->request->get('filter'), true);
        //SKU搜索
        if ($filter['sku']) {
            $smap['sku'] = ['like', $filter['sku'] . '%'];
            if ($filter['status']) {
                $smap['status'] = ['in', $filter['status']];
            }
            $ids = $model->getOrderId($smap);
            $map['entity_id'] = ['in', $ids];
            unset($filter['sku']);
            $this->request->get(['filter' => json_encode($filter)]);
        }

        list($where) = $this->buildparams();

//        $list = $model
////          ->field('increment_id,customer_firstname,customer_email,status,base_grand_total,base_shipping_amount,custom_order_prescription_type,order_type,created_at')
//            ->where($where)
//            ->where($map)
//            ->select();
//        $list = collection($list)->toArray();
        if ($label ==1){
            $field = 'sfo.entity_id,sfo.increment_id,sfo.customer_firstname,sfo.customer_email,sfo.status,sfo.base_grand_total,sfo.base_shipping_amount,
        sfo.custom_order_prescription_type,sfo.order_type,sfo.created_at,sfo.is_new_version,sfo.global_currency_code,
        sfoi.product_options,sfo.total_qty_ordered as NUM,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.product_id,sfoi.qty_ordered';
        }else{
            $field = 'sfo.entity_id,sfo.increment_id,sfo.customer_firstname,sfo.customer_email,sfo.status,sfo.base_grand_total,sfo.base_shipping_amount,
        sfo.custom_order_prescription_type,sfo.order_type,sfo.created_at,sfo.global_currency_code,
        sfoi.product_options,sfo.total_qty_ordered as NUM,sfoi.order_id,sfo.`status`,sfoi.sku,sfoi.product_id,sfoi.qty_ordered';
        }

        $resultList = $model->alias('sfo')
            ->join(['sales_flat_order_item' => 'sfoi'], 'sfoi.order_id=sfo.entity_id')
            ->field($field)
            ->where($map)
            ->where($where)
            ->order('sfoi.order_id desc')
            ->select();
        $resultList = collection($resultList)->toArray();


        foreach ($resultList as $key=>$value){

            $finalResult[$key]['country_id'] = $model->table('sales_flat_order_address')->where(array('parent_id'=>$value['entity_id']))->value('country_id');
            $finalResult[$key]['method'] = $model->table('sales_flat_order_payment')->where(array('parent_id'=>$value['entity_id']))->value('method');
            $finalResult[$key]['increment_id'] = $value['increment_id'];
            $finalResult[$key]['sku'] = $value['sku'];
//            $finalResult[$key]['created_at'] = substr($value['created_at'], 0, 10);
            $finalResult[$key]['created_at'] = $value['created_at'];
            $finalResult[$key]['base_grand_total'] = $value['base_grand_total'];
            $finalResult[$key]['base_shipping_amount'] = $value['base_shipping_amount'];
            $finalResult[$key]['label'] = $value['label'];
            $finalResult[$key]['customer_email'] = $value['customer_email'];
            $finalResult[$key]['status'] = $value['status'];
            $finalResult[$key]['total_qty_ordered'] = $value['total_qty_ordered'];
            $finalResult[$key]['entity_id'] = $value['entity_id'];
            $finalResult[$key]['order_type'] = $value['order_type'];
            $finalResult[$key]['global_currency_code'] = $value['global_currency_code'];
            $finalResult[$key]['NUM'] = $value['NUM'];
            $tmp_product_options = unserialize($value['product_options']);
            //新处方
            if ($label ==1){
                if ($value['is_new_version'] == 1) {
                    //镀膜
                    $finalResult[$key]['coatiing_name'] = $tmp_product_options['info_buyRequest']['tmplens']['coating_name'];
                    //镜片类型
                    $finalResult[$key]['index_type'] = $tmp_product_options['info_buyRequest']['tmplens']['lens_data_name'];
                    //镜片类型拼接颜色字段
                    if ($tmp_product_options['info_buyRequest']['tmplens']['color_id']) {
                        $finalResult[$key]['index_type'] .= '-' . $tmp_product_options['info_buyRequest']['tmplens']['color_data_name'];
                    }
                } else {
                    $finalResult[$key]['coatiing_name'] = $tmp_product_options['info_buyRequest']['tmplens']['coatiing_name'];
                    $finalResult[$key]['index_type'] = $tmp_product_options['info_buyRequest']['tmplens']['index_type'];
                    //镜片类型拼接颜色字段
                    if ($tmp_product_options['info_buyRequest']['tmplens']['color_name']) {
                        $finalResult[$key]['index_type'] .= '-' . $tmp_product_options['info_buyRequest']['tmplens']['color_name'];
                    }
                }
            }else{
                $finalResult[$key]['coatiing_name'] = $tmp_product_options['info_buyRequest']['tmplens']['coatiing_name'];
                $finalResult[$key]['index_type'] = $tmp_product_options['info_buyRequest']['tmplens']['index_type'];
                //镜片类型拼接颜色字段
                if ($tmp_product_options['info_buyRequest']['tmplens']['color_name']) {
                    $finalResult[$key]['index_type'] .= '-' . $tmp_product_options['info_buyRequest']['tmplens']['color_name'];
                }
            }

            $tmp_prescription_params = $tmp_product_options['info_buyRequest']['tmplens']['prescription'];
            if (isset($tmp_prescription_params)) {
                $tmp_prescription_params = explode("&", $tmp_prescription_params);
                $tmp_lens_params = array();
                foreach ($tmp_prescription_params as $tmp_key => $tmp_value) {
                    $arr_value = explode("=", $tmp_value);
                    if (isset($arr_value[1])) {
                        $tmp_lens_params[$arr_value[0]] = $arr_value[1];
                    }
                }
            }

            //斜视值
            if (isset($tmp_lens_params['prismcheck']) && $tmp_lens_params['prismcheck'] == 'on') {
                $finalResult[$key]['od_bd'] = $tmp_lens_params['od_bd'];
                $finalResult[$key]['od_pv'] = $tmp_lens_params['od_pv'];
                $finalResult[$key]['os_pv'] = $tmp_lens_params['os_pv'];
                $finalResult[$key]['os_bd'] = $tmp_lens_params['os_bd'];

                $finalResult[$key]['od_pv_r'] = $tmp_lens_params['od_pv_r'];
                $finalResult[$key]['od_bd_r'] = $tmp_lens_params['od_bd_r'];
                $finalResult[$key]['os_pv_r'] = $tmp_lens_params['os_pv_r'];
                $finalResult[$key]['os_bd_r'] = $tmp_lens_params['os_bd_r'];
            }

            $finalResult[$key]['od_sph'] = isset($tmp_lens_params['od_sph']) ? $tmp_lens_params['od_sph'] : '';
            $finalResult[$key]['od_cyl'] = isset($tmp_lens_params['od_cyl']) ? $tmp_lens_params['od_cyl'] : '';
            $finalResult[$key]['od_axis'] = isset($tmp_lens_params['od_axis']) ? $tmp_lens_params['od_axis'] : '';
            $finalResult[$key]['od_add'] = isset($tmp_lens_params['od_add']) ? $tmp_lens_params['od_add'] : '';

            $finalResult[$key]['os_sph'] = isset($tmp_lens_params['os_sph']) ? $tmp_lens_params['os_sph'] : '';
            $finalResult[$key]['os_cyl'] = isset($tmp_lens_params['os_cyl']) ? $tmp_lens_params['os_cyl'] : '';
            $finalResult[$key]['os_axis'] = isset($tmp_lens_params['os_axis']) ? $tmp_lens_params['os_axis'] : '';
            $finalResult[$key]['os_add'] = isset($tmp_lens_params['os_add']) ? $tmp_lens_params['os_add'] : '';

            $finalResult[$key]['pd_r'] = isset($tmp_lens_params['pd_r']) ? $tmp_lens_params['pd_r'] : '';
            $finalResult[$key]['pd_l'] = isset($tmp_lens_params['pd_l']) ? $tmp_lens_params['pd_l'] : '';
            $finalResult[$key]['pd'] = isset($tmp_lens_params['pd']) ? $tmp_lens_params['pd'] : '';
            $finalResult[$key]['pdcheck'] = isset($tmp_lens_params['pdcheck']) ? $tmp_lens_params['pdcheck'] : '';


            $tmp_bridge = $this->get_frame_lens_width_height_bridge($value['product_id']);
            $finalResult[$key]['lens_width'] = $tmp_bridge['lens_width'];
            $finalResult[$key]['lens_height'] = $tmp_bridge['lens_height'];
            $finalResult[$key]['bridge'] = $tmp_bridge['bridge'];
            $finalResult[$key]['is_new_version'] = $value['is_new_version'];
        }

        $data = array();
        foreach ($finalResult as $k=>$it){
            $data[$it['increment_id']]['increment_id'] =  $it['increment_id'];
            $data[$it['increment_id']]['entity_id'] =  $it['entity_id'];
            $data[$it['increment_id']]['order_type'] =  $it['order_type'];//订单类型
            $data[$it['increment_id']]['base_grand_total'] =  $it['base_grand_total'];//订单金额
            $data[$it['increment_id']]['base_shipping_amount'] =  $it['base_shipping_amount'];//邮费
            $data[$it['increment_id']]['label'] =  $it['label'];//是否为商业快递
            $data[$it['increment_id']]['country_id'] =  $it['country_id'];//国家
            $data[$it['increment_id']]['customer_email'] =  $it['customer_email'];//邮箱
            $data[$it['increment_id']]['status'] =  $it['status'];//订单状态
            $data[$it['increment_id']]['NUM'] =  $it['NUM']; //sku数量
            $data[$it['increment_id']]['method'] =  $it['method']; //支付方式
            $data[$it['increment_id']]['global_currency_code'] =  $it['global_currency_code']; //原币种
            $data[$it['increment_id']]['base_grand_total'] =  $it['base_grand_total']; //原支付金额
            $data[$it['increment_id']]['created_at'] =  $it['created_at']; //订单支付时间
            $data[$it['increment_id']]['list'][] =  $it;
            if ($it['order_type'] == 1) {
                $data[$it['increment_id']]['order_type'] = '普通订单';
            }elseif ($it['order_type'] == 2) {
                $data[$it['increment_id']]['order_type'] = '批发单';
            } elseif ($it['order_type'] == 3) {
                $data[$it['increment_id']]['order_type'] = '网红单';
            } elseif ($it['order_type'] == 4) {
                $data[$it['increment_id']]['order_type'] = '补发单';
            } elseif ($it['order_type'] == 5) {
                $data[$it['increment_id']]['order_type'] = '补差价';
            } elseif ($it['order_type'] == 6) {
                $data[$it['increment_id']]['order_type'] = '一件代发';
            }
            if ($it['label']  ==1){
                $data[$it['increment_id']]['label'] = '是';
            }else{
                $data[$it['increment_id']]['label'] = '否';
            }
        }
//        dump($data);die();


        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        $spreadsheet
            ->setActiveSheetIndex(0)
            ->setCellValue("A1", "记录标识")
            ->setCellValue("B1", "订单号")
            ->setCellValue("C1", "订单类型")  //利用setCellValues()填充数据
            ->setCellValue("D1", "订单金额")
            ->setCellValue("E1", "邮费")
            ->setCellValue("F1", "是否为商业快递")
            ->setCellValue("G1", "国家")
            ->setCellValue("H1", "邮箱")
            ->setCellValue("I1", "订单状态")
            ->setCellValue("J1", "SKU数量")
            ->setCellValue("K1", "SKU")
            ->setCellValue("L1", "眼球")
            ->setCellValue("M1", "SPH")
            ->setCellValue("N1", "CYL")
            ->setCellValue("O1", "AXI")
            ->setCellValue("P1", "ADD")
            ->setCellValue("Q1", "单PD")
            ->setCellValue("R1", "PD")
            ->setCellValue("S1", "镜片")
            ->setCellValue("T1", "镜框宽度")
            ->setCellValue("U1", "镜框高度")
            ->setCellValue("V1", "bridge")
            ->setCellValue("W1", "处方类型")
            ->setCellValue("X1", "Prism")
            ->setCellValue("Y1", "Direct")
            ->setCellValue("Z1", "Prism")
            ->setCellValue("AA1", "Direct")
            ->setCellValue("AB1", "支付方式")
            ->setCellValue("AC1", "原币种")
            ->setCellValue("AD1", "原支付金额")
            ->setCellValue("AE1", "订单支付时间");
//            ->setCellValue("AF1", "订单创建时间");

        $count = 2;
        $nums =2;
        $merge =0;
        foreach ($data as $key => $value) {
            $count += $count;
            $merge +=$nums;
            $num = $nums+$num_cat;
            $cat[] = $num;
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($num), $value['entity_id'],\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);//记录标识
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($num), $value['increment_id']);//订单编号
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($num), $value['order_type']);//订单类型
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($num), $value['base_grand_total']); //订单金额
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($num), $value['base_shipping_amount']); //邮费
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($num), $value['label']); //是否为商业快递
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($num), $value['country_id']); //国家
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($num), $value['customer_email']); //邮箱
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($num), $value['status']);//订单状态
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($num), $value['NUM']);//SKU数量

            foreach ($value['list'] as $k=>$i){

                if ($i['custom_order_prescription_type'] == 1) {
                    $custom_order_prescription_type = '仅镜架';
                } elseif ($i['custom_order_prescription_type'] == 2) {
                    $custom_order_prescription_type = '现货处方镜';
                } elseif ($i['custom_order_prescription_type'] == 3) {
                    $custom_order_prescription_type = '定制处方镜';
                } elseif ($i['custom_order_prescription_type'] == 4) {
                    $custom_order_prescription_type = '镜架+现货';
                } elseif ($i['custom_order_prescription_type'] == 5) {
                    $custom_order_prescription_type = '镜架+定制';
                } elseif ($i['custom_order_prescription_type'] == 6) {
                    $custom_order_prescription_type = '现片+定制片';
                }else{
                    $custom_order_prescription_type = '获取中';
                }
//                $spreadsheet->getActiveSheet()->setCellValue("C" . ($k+$count), $i[]);//订单类型
                $spreadsheet->getActiveSheet()->setCellValue("K" . ($k*2+$num), $i['sku']);//SKU
                $spreadsheet->getActiveSheet()->setCellValue("L" . ($k*2+$num), '右眼');//眼球
                $spreadsheet->getActiveSheet()->setCellValue("L" . ($k*2+$num+1),'左眼');//眼球
                $spreadsheet->getActiveSheet()->setCellValue("M" . ($k*2+$num),(float) $i['od_sph'] > 0 ? ' +' . number_format($value['od_sph'] * 1, 2) : ' ' . $value['od_sph']);//SPH
                $spreadsheet->getActiveSheet()->setCellValue("M" . ($k*2+$num+1),(float) $i['os_sph'] > 0 ? ' +' . number_format($value['os_sph'] * 1, 2) : ' ' . $value['os_sph']);//SPH
                $spreadsheet->getActiveSheet()->setCellValue("N" . ($k*2+$num), (float) $i['od_cyl'] > 0 ? ' +' . number_format($value['od_cyl'] * 1, 2) : ' ' . $value['od_cyl']);//CYL
                $spreadsheet->getActiveSheet()->setCellValue("N" . ($k*2+$num+1), (float) $i['os_cyl'] > 0 ? ' +' . number_format($value['os_cyl'] * 1, 2) : ' ' . $value['os_cyl']);//CYL
                $spreadsheet->getActiveSheet()->setCellValue("O" . ($k*2+$num), $i['od_axis']);//AXI
                $spreadsheet->getActiveSheet()->setCellValue("O" . ($k*2+$num+1), $i['os_axis']);//AXI
                $spreadsheet->getActiveSheet()->setCellValue("AB" . ($num), $value['method']);//支付方式
                $spreadsheet->getActiveSheet()->setCellValue("AC" . ($num), $value['global_currency_code']);//原币种
                $spreadsheet->getActiveSheet()->setCellValue("AD" . ($num), $value['base_grand_total']);//原支付金额
                $spreadsheet->getActiveSheet()->setCellValue("AE" . ($num), $value['created_at']);//订单支付时间
                $i['os_add'] = urldecode($i['os_add']);
                if ($i['os_add'] && $i['os_add'] && (float) ($i['os_add']) * 1 != 0 && (float) ($i['od_add']) * 1 != 0) {
                    //新处方版本
                    if ($i['is_new_version'] == 1) {
                        $spreadsheet->getActiveSheet()->setCellValue("P" . ($k*2+$num), $i['od_add']); //ADD
                        $spreadsheet->getActiveSheet()->setCellValue("P" . ($k*2+$num+1), $i['os_add']);
                    } else {
                        // 旧处方 双ADD值时，左右眼互换
                        $spreadsheet->getActiveSheet()->setCellValue("P" . ($k*2+$num), $i['os_add']);
                        $spreadsheet->getActiveSheet()->setCellValue("P" . ($k*2+$num+1), $i['od_add']);
                    }
                } else {
                    if ($i['os_add'] && (float) $i['os_add'] * 1 != 0) {
                        //数值在上一行合并有效，数值在下一行合并后为空
                        $spreadsheet->getActiveSheet()->setCellValue("P" . ($k*2+$num), $i['os_add']);
                        $spreadsheet->getActiveSheet()->mergeCells("P" . ($k*2+$num) . ":P" . ($k*2+$num+1));
                    } else {
                        //数值在上一行合并有效，数值在下一行合并后为空
                        $spreadsheet->getActiveSheet()->setCellValue("P" . ($k*2+$num), $i['od_add']);
                        $spreadsheet->getActiveSheet()->mergeCells("P" . ($k*2+$num) . ":P" . ($k*2+$num+1));
                    }
                }

                if ($value['pdcheck'] == 'on' && $value['pd_r'] && $value['pd_l']) {
                    $spreadsheet->getActiveSheet()->setCellValue("Q" . ($k*2+$num), $i['pd_r']); //单PD
                    $spreadsheet->getActiveSheet()->setCellValue("Q" . ($k*2+$num+1), $i['pd_l']);
                } else {
                    $spreadsheet->getActiveSheet()->setCellValue("R" . ($k*2+$num), $i['pd']); //PD
                    $spreadsheet->getActiveSheet()->mergeCells("R" . ($k*2+$num) . ":R" . ($k*2+$num+1));
                }

                $spreadsheet->getActiveSheet()->setCellValue("S" . ($k*2+$num), $i['index_type']);//镜片
                $spreadsheet->getActiveSheet()->setCellValue("T" . ($k*2+$num), $i['lens_width']);//镜框宽度
                $spreadsheet->getActiveSheet()->setCellValue("U" . ($k*2+$num), $i['lens_height']);//镜框高度
                $spreadsheet->getActiveSheet()->setCellValue("V" . ($k*2+$num), $i['bridge']);//bridge
                $spreadsheet->getActiveSheet()->setCellValue("W" . ($k*2+$num), $custom_order_prescription_type);//处方类型
                $spreadsheet->getActiveSheet()->setCellValue("X" . ($k*2+$num), isset($i['od_pv']) ? $i['od_pv'] : '');//Prism
                $spreadsheet->getActiveSheet()->setCellValue("X" . ($k*2+$num+1), isset($i['os_pv']) ? $i['os_pv'] : '');
                $spreadsheet->getActiveSheet()->setCellValue("Y" . ($k*2+$num), isset($i['od_bd']) ? $i['od_bd'] : '');//Direct
                $spreadsheet->getActiveSheet()->setCellValue("Y" . ($k*2+$num+1), isset($i['os_bd']) ? $i['os_bd'] : '');
                $spreadsheet->getActiveSheet()->setCellValue("Z" . ($k*2+$num), isset($i['od_pv_r']) ? $i['od_pv_r'] : '');//Prism
                $spreadsheet->getActiveSheet()->setCellValue("Z" . ($k*2+$num+1), isset($i['os_pv_r']) ? $i['os_pv_r'] : '');
                $spreadsheet->getActiveSheet()->setCellValue("AA" . ($k*2+$num), isset($i['od_bd_r']) ? $i['od_bd_r'] : '');//Direct
                $spreadsheet->getActiveSheet()->setCellValue("AA" . ($k*2+$num+1), isset($i['os_bd_r']) ? $i['os_bd_r'] : '');
                $spreadsheet->getActiveSheet()->mergeCells("K" . ($k*2+$num)  . ":K" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("P" . ($k*2+$num)  . ":P" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("R" . ($k*2+$num)  . ":R" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("S" . ($k*2+$num)  . ":S" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("T" . ($k*2+$num)  . ":T" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("U" . ($k*2+$num)  . ":U" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("V" . ($k*2+$num)  . ":V" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("W" . ($k*2+$num)  . ":W" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("AB" . ($k*2+$num)  . ":AC" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("AC" . ($k*2+$num)  . ":AC" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("AD" . ($k*2+$num)  . ":AD" . ($k*2+$num+1));
                $spreadsheet->getActiveSheet()->mergeCells("AE" . ($k*2+$num)  . ":AE" . ($k*2+$num+1));
//                $spreadsheet->getActiveSheet()->mergeCells("AF" . ($k*2+$num)  . ":AF" . ($k*2+$num+1));
                $num_cat = $k*2+$num;
            }



            //合并单元格
            $spreadsheet->getActiveSheet()->mergeCells("A" . ($num) . ":A" . ($num_cat+1));
            $spreadsheet->getActiveSheet()->mergeCells("B" . ($num) . ":B" . ($num_cat+1));
            $spreadsheet->getActiveSheet()->mergeCells("C" . ($num) . ":C" . ($num_cat+1));
            $spreadsheet->getActiveSheet()->mergeCells("D" . ($num) . ":D" . ($num_cat+1));
            $spreadsheet->getActiveSheet()->mergeCells("E" . ($num) . ":E" . ($num_cat+1));
            $spreadsheet->getActiveSheet()->mergeCells("F" . ($num) . ":F" . ($num_cat+1));
            $spreadsheet->getActiveSheet()->mergeCells("G" . ($num) . ":G" . ($num_cat+1));
            $spreadsheet->getActiveSheet()->mergeCells("H" . ($num) . ":H" . ($num_cat+1));
            $spreadsheet->getActiveSheet()->mergeCells("I" . ($num) . ":I" . ($num_cat+1));
            $spreadsheet->getActiveSheet()->mergeCells("J" . ($num) . ":J" . ($num_cat+1));
        }


        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('AB')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('AD')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('AE')->setWidth(30);
//        $spreadsheet->getActiveSheet()->getColumnDimension('AF')->setWidth(30);


        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];

        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:AE1' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '订单数据' . date("YmdHis", time());;

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');

    }






    /**
     * 获取镜架尺寸
     */
    protected function get_frame_lens_width_height_bridge($product_id)
    {
        if ($product_id) {
            $querySql = "select cpev.entity_type_id,cpev.attribute_id,cpev.`value`,cpev.entity_id
from catalog_product_entity_varchar cpev
LEFT JOIN catalog_product_entity cpe on cpe.entity_id=cpev.entity_id 
where cpev.attribute_id in(161,163,164) and cpev.store_id=0 and cpev.entity_id=$product_id";
            $resultList = Db::connect('database.db_zeelool')->query($querySql);
            if ($resultList) {
                $result = array();
                foreach ($resultList as $key => $value) {
                    if ($value['attribute_id'] == 161) {
                        $result['lens_width'] = $value['value'];
                    }
                    if ($value['attribute_id'] == 164) {
                        $result['lens_height'] = $value['value'];
                    }
                    if ($value['attribute_id'] == 163) {
                        $result['bridge'] = $value['value'];
                    }
                }
            } else {
                $result['lens_width'] = '';
                $result['lens_height'] = '';
                $result['bridge'] = '';
            }
        }
        return $result;
    }


    /**
     * 批量导出订单成本核算xls
     *
     * @Description
     * @since 2020/6/12 15:48
     * @author jhh
     * @return void
     */
    public function account_order_batch_export_xls()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //根据传的标签切换对应站点数据库
        $label = $this->request->get('label', 1);
        switch ($label) {
            case 1:
                $model = $this->zeelool;
                break;
            case 2:
                $model = $this->voogueme;
                break;
            case 3:
                $model = $this->nihao;
                break;
            case 4:
                $model = $this->weseeoptical;
                break;
            case 5:
                $model = $this->meeloog;
                break;
            case 9:
                $model = $this->zeelool_es;
                break;
            case 10:
                $model = $this->zeelool_de;
                break;
            default:
                return false;
                break;
        }

        $ids = input('ids');
        //        $ids = "345168,259,258,256,255,254,253,252,251,250";
        if ($ids) {
            $map['entity_id'] = ['in', $ids];
        }
        $rep = $this->request->get('filter');
        //        dump($rep);die;
        $addWhere = '1=1';
        if ($rep != '{}') {
        } else {
            $addWhere  .= " AND DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(created_at)";
        }
        list($where) = $this->buildparams();

        $list = $model
            ->field('entity_id,increment_id,customer_firstname,customer_email,status,base_grand_total,base_shipping_amount,custom_order_prescription_type,order_type,created_at,base_total_paid,base_total_due')
            ->where($where)
            ->where($map)
            ->select();
        $totalId = $model
            ->where($where)
            ->where($addWhere)
            ->column('entity_id');
        $thisPageId = $model
            ->where($where)
            ->column('entity_id');
        $costInfo = $model->getOrderCostInfoExcel($totalId, $thisPageId);
        $list = collection($list)->toArray();
        //        dump($list);die;
        //遍历以获得导出所需要的数据
        foreach ($list as $k => $v) {
            //订单支付金额
            if (in_array($v['status'], ['processing', 'complete', 'creditcard_proccessing', 'free_processing'])) {
                $list[$k]['total_money']      =  round($v['base_total_paid'] + $v['base_total_due'], 2);
            }
            //订单镜架成本
            if (isset($costInfo['thispageFramePrice'])) {
                if (array_key_exists($v['increment_id'], $costInfo['thispageFramePrice'])) {
                    $list[$k]['frame_cost']   = $costInfo['thispageFramePrice'][$v['increment_id']];
                }
            }
            //订单镜片成本
            if (isset($costInfo['thispageLensPrice'])) {
                if (array_key_exists($v['increment_id'], $costInfo['thispageLensPrice'])) {
                    $list[$k]['lens_cost']    = $costInfo['thispageLensPrice'][$v['increment_id']];
                }
            }
            //订单退款金额
            if (isset($costInfo['thispageRefundMoney'])) {
                if (array_key_exists($v['increment_id'], $costInfo['thispageRefundMoney'])) {
                    $list[$k]['refund_money'] = $costInfo['thispageRefundMoney'][$v['increment_id']];
                }
            }
            //订单补差价金额
            if (isset($costInfo['thispageFullPostMoney'])) {
                if (array_key_exists($v['increment_id'], $costInfo['thispageFullPostMoney'])) {
                    $list[$k]['fill_post']    = $costInfo['thispageFullPostMoney'][$v['increment_id']];
                }
            }
            //订单加工费
            if (isset($costInfo['thisPageProcessCost'])) {
                if (array_key_exists($v['entity_id'], $costInfo['thisPageProcessCost'])) {
                    $list[$k]['process_cost'] = $costInfo['thisPageProcessCost'][$v['entity_id']];
                }
            }
        }
        //        dump($list);die;
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("A1", "记录标识")
            ->setCellValue("B1", "订单号")
            ->setCellValue("C1", "邮箱");   //利用setCellValues()填充数据
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("D1", "状态")
            ->setCellValue("E1", "支付金额($)");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("F1", "镜架成本金额(￥)")
            ->setCellValue("G1", "镜片成本金额(￥)");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("H1", "邮费成本金额(￥)")
            ->setCellValue("I1", "加工费成本金额(￥)");
        $spreadsheet->setActiveSheetIndex(0)->setCellValue("J1", "退款金额")
            ->setCellValue("K1", "补差价金额")
            ->setCellValue("L1", "创建时间");
        foreach ($list as $key => $value) {

            $spreadsheet->getActiveSheet()->setCellValueExplicit("A" . ($key * 1 + 2), $value['entity_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($key * 1 + 2), $value['increment_id']);
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 1 + 2), $value['customer_email']);
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 1 + 2), $value['status']);
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 1 + 2), $value['total_money']);
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 1 + 2), $value['frame_cost']);
            $spreadsheet->getActiveSheet()->setCellValue("G" . ($key * 1 + 2), $value['lens_cost']);
            $spreadsheet->getActiveSheet()->setCellValue("H" . ($key * 1 + 2), $value['frame_cost']);
            $spreadsheet->getActiveSheet()->setCellValue("I" . ($key * 1 + 2), $value['process_cost']);
            $spreadsheet->getActiveSheet()->setCellValue("J" . ($key * 1 + 2), $value['refund_money']);
            $spreadsheet->getActiveSheet()->setCellValue("K" . ($key * 1 + 2), $value['fill_post']);
            $spreadsheet->getActiveSheet()->setCellValue("L" . ($key * 1 + 2), $value['created_at']);
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(30);
        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color'       => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);

        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:L' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);

        $format = 'xlsx';
        $savename = '订单成本核算数据' . date("YmdHis", time());

        if ($format == 'xls') {
            //输出Excel03版本
            header('Content-Type:application/vnd.ms-excel');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        } elseif ($format == 'xlsx') {
            //输出07Excel版本
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        }

        //输出名称
        header('Content-Disposition: attachment;filename="' . $savename . '.' . $format . '"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new $class($spreadsheet);

        $writer->save('php://output');
    }
}
