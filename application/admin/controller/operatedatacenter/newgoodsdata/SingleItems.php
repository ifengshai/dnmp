<?php

namespace app\admin\controller\operatedatacenter\NewGoodsData;

use app\admin\model\itemmanage\ItemPlatformSku;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use think\Db;
use think\Request;

class SingleItems extends Backend
{
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        parent::_initialize();
        $this->order = new \app\admin\model\order\order\NewOrder();
        $this->orderitemoption = new \app\admin\model\order\order\NewOrderItemOption();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
    }
    /**
     * 单品查询某个sku的订单列表
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/17
     * Time: 13:43:45
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            if ($filter['time_str']) {
                $createat = explode(' ', $filter['time_str']);
                $map['o.payment_time'] = ['between', [strtotime($createat[0]), strtotime($createat[3] . ' 23:59:59')]];
            } else {
                $start = strtotime(date('Y-m-d', strtotime('-6 day')));
                $end = strtotime(date('Y-m-d 23:59:59'));
                $map['o.payment_time'] = ['between', [$start, $end]];
            }
            if ($filter['sku']) {
                $map['i.sku'] = ['like','%'.$filter['sku'].'%'];
            }

            if ($filter['order_platform']) {
                $map['o.site'] = $filter['order_platform'] ? $filter['order_platform'] : 1;
            }
            unset($filter['create_time-operate']);
            unset($filter['time_str']);
            unset($filter['sku']);
            unset($filter['order_platform']);
            $this->request->get(['filter' => json_encode($filter)]);
            $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
            $map['o.order_type'] = 1;
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($map)
                ->group('o.entity_id')
                ->count();
            $list = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($map)
                ->group('o.entity_id')
                ->order('o.payment_time','desc')
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            foreach ($list as $key=>$value){
                $list[$key]['base_grand_total'] = round($value['base_grand_total'],2);
                $list[$key]['base_discount_amount'] = round($value['base_discount_amount'],2);
                $list[$key]['payment_time'] = date('Y-m-d H:i:s',$value['payment_time']);
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'meeloog','wesee','zeelool_de','zeelool_jp'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    /**
     * 中间表格sku的订单各项指标
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/17
     * Time: 13:44:18
     */
    public function ajax_top_data()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            //站点
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $where['o.site'] = $map['o.site'] = $site;
            //时间
            $time_str = $params['time_str'] ? $params['time_str'] : '';
            $createat = explode(' ', $time_str);
            $sku = input('sku');
            //此sku的总订单量
            $map['i.sku'] = ['like', $sku . '%'];
            $where['o.status'] = $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
            $where['o.payment_time'] = $map['o.payment_time'] = ['between', [strtotime($createat[0] . ' ' . $createat[1]), strtotime($createat[3] . ' ' . $createat[4])]];
            $where['o.order_type'] = $map['o.order_type'] = 1;
            $total = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($map)
                ->count('distinct magento_order_id');
            //整站订单量
            $wholePlatformOrderNum = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($where)
                ->count('distinct magento_order_id');
            //订单占比
            $orderRate = $wholePlatformOrderNum? round($total / $wholePlatformOrderNum * 100, 2) . '%' : 0;
            //平均订单副数
            $wholeGlass = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($map)
                ->sum('i.qty');//sku总副数
            $avgOrderGlass = $total ? round($wholeGlass / $total, 2) : 0;
            //付费镜片订单数
            $where1['i.index_price|i.coating_price'] = ['>',0];
            $payLens = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($map)
                ->where($where1)
                ->count('distinct magento_order_id');
            //付费镜片订单数占比
            $payLensRate = $total ? round($payLens / $total * 100, 2) . '%' : 0;
            //只买一副的订单
            $onlyOneGlassNum = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($map)
                ->where('total_qty_ordered',1)
                ->count('distinct magento_order_id');
            //只买一副的订单占比
            $onlyOneGlassRate = $total == 0 ? 0 : round($onlyOneGlassNum / $total * 100, 2) . '%';
            //订单总金额
            $glassSql = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($map)
                ->field('distinct magento_order_id')
                ->buildSql();
            $glassWhere2 = [];
            $glassWhere2[] = ['exp', Db::raw("entity_id in " . $glassSql)];
            $wholePrice = $this->order
                ->where($glassWhere2)
                ->where('site',$site)
                ->sum('base_grand_total');
            //订单客单价
            $everyPrice = $total == 0 ? 0 : round($wholePrice / $total, 2);
            //该sku的总副数金额
            $sumSkuTotal = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($map)
                ->value('sum(base_original_price-i.base_discount_amount) as price');
            //平均每副订单金额
            $everyMoney = $total ? round($sumSkuTotal/$wholeGlass,2) : 0;
            //关联购买
            $orderIds = $this->order
                ->alias('o')
                ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
                ->where($map)
                ->field('distinct magento_order_id')
                ->buildSql();
            $orderIdsWhere = [];
            $orderIdsWhere[] = ['exp', Db::raw("magento_order_id in " . $orderIds)];
            $arraySku = $this->orderitemoption
                ->where($orderIdsWhere)
                ->where('site',$site)
                ->where('sku','not like',$sku . '%')
                ->group('sku')
                ->order('count desc')
                ->column('sum(qty) count','sku');
            $data = compact('sku', 'arraySku', 'total', 'orderPlatformList', 'wholePlatformOrderNum', 'orderRate', 'avgOrderGlass', 'payLens', 'payLensRate', 'onlyOneGlassNum', 'onlyOneGlassRate', 'everyPrice', 'wholePrice','everyMoney');
            $this->success('', '', $data);
        }
    }

    /**
     * 首单/复购用户占比
     * @author mjj
     * @date   2021/5/18 10:50:51
     */
    public function first_again_buy()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $sku = $params['sku'];
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            if ($params['time_str']) {
                $createat = explode(' ', $params['time_str']);
                $start = strtotime($createat[0]);
                $end = strtotime($createat[3].' 23:59:59');
                $userWhere['o.payment_time'] = ['between', [$start, $end]];
                $orderWhere['o.payment_time'] = ['lt',$start];
            } else{
                $start = strtotime(date('Y-m-d', strtotime('-6 day')));
                $end   = time();
                $userWhere['o.payment_time'] = ['between', [$start,$end]];
                $orderWhere['o.payment_time'] = ['lt',$start];
            }
            $map["o.site"] = $site;
            $map["o.status"] = ["in", ["free_processing", "processing", "complete", "paypal_reversed", "payment_review", "paypal_canceled_reversal","delivered"]];
            $mapSku["i.sku"] = ["like",$sku."%"];
            $map["o.order_type"] = 1;
            //在时间段内进行过购买行为的用户
            $emails = $this->order
                ->alias("o")
                ->join("fa_order_item_option i","o.entity_id=i.magento_order_id")
                ->where($map)
                ->where($mapSku)
                ->where($userWhere)
                ->where("o.customer_email is not null")
                ->field("o.customer_email")
                ->buildSql();
            $arrWhere = [];
            $arrWhere[] = ['exp', Db::raw("o.customer_email in " . $emails)];
            //是否产生购买行为
            $againUserSql = $this->order
                ->alias("o")
                ->join("fa_order_item_option i","o.entity_id=i.magento_order_id")
                ->where($map)
                ->where($orderWhere)
                ->where($arrWhere)
                ->field("count(*) count")
                ->group("o.customer_email")
                ->having("count>=1")
                ->buildSql();
            $againCount = $this->order
                ->table([$againUserSql=>"t1"])
                ->count();
            //总人数
            $count = $this->order
                ->alias("o")
                ->join("fa_order_item_option i","o.entity_id=i.magento_order_id")
                ->where($map)
                ->where($mapSku)
                ->where($userWhere)
                ->where("o.customer_email is not null")
                ->count("distinct o.customer_email");
            //首次购买人数
            $firstCount = $count-$againCount;
            $json['column'] = ['首购人数', '复购人数'];
            $json['columnData'] = [
                [
                    'name' => '首购人数',
                    'value' => $firstCount,
                ],
                [
                    'name' => '复购人数',
                    'value' => $againCount,
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 处方类型占比饼图
     * @return \think\response\Json
     * @author mjj
     * @date   2021/5/18 15:39:12
     */
    public function lens_data_pie()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $data = $this->prescrtion_data($site,$params['time_str'],$params['sku']);
            $column = array_column($data,'name');
            $json['column'] = $column;
            $json['columnData'] = $data;
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 获取处方数据
     * @param int $site  站点
     * @param string $timeStr  时间
     * @param string $sku   sku
     * @return array[]
     * @author mjj
     * @date   2021/5/18 15:39:21
     */
    function prescrtion_data($site = 1,$timeStr = '',$sku = ''){
        $orderNum = $this->prescrtion_num('',$site,$timeStr,$sku);
        if($site == 1 || $site == 2 || $site == 3 || $site == 10 || $site == 11){
            $singleVisionNum = $this->prescrtion_num('SingleVision',$site,$timeStr,$sku);
            $singleVisionArr = array(
                'name'=>'single vision',
                'value'=>$singleVisionNum,
            );
            $progressiveNum = $this->prescrtion_num('Progressive',$site,$timeStr,$sku);
            $progressiveArr = array(
                'name'=>'progressive',
                'value'=>$progressiveNum,
            );
            if($site == 1){
                $readingGlassesNum = $this->prescrtion_num('Readingglasses',$site,$timeStr,$sku);
                $readingGlassesArr = array(
                    'name'=>'reading glasses',
                    'value'=>$readingGlassesNum,
                );
                $readingGlassesNoNum = $this->prescrtion_num('ReadingGlassesNon',$site,$timeStr,$sku);
                $readingGlassesNoArr = array(
                    'name'=>'reading glasses no prescription',
                    'value'=>$readingGlassesNoNum,
                );
                $noPrescriptionNum1 = $this->prescrtion_num('Noprescription',$site,$timeStr,$sku);
                $noPrescriptionNum2 = $this->prescrtion_num('Nonprescription',$site,$timeStr,$sku);
                $noPrescriptionNum = $noPrescriptionNum1+$noPrescriptionNum2;
                $noPrescriptionArr = array(
                    'name'=>'no prescription',
                    'value'=>$noPrescriptionNum,
                );
                $sunglassesNum = $this->prescrtion_num('Sunglasses',$site,$timeStr,$sku);
                $sunglassesArr = array(
                    'name'=>'sunglasses',
                    'value'=>$sunglassesNum,
                );
                $sunglassesNoNum1 = $this->prescrtion_num('Sunglasses_NonPrescription',$site,$timeStr,$sku);
                $sunglassesNoNum2 = $this->prescrtion_num('SunGlassesNoprescription',$site,$timeStr,$sku);
                $sunglassesNoNum = $sunglassesNoNum1+$sunglassesNoNum2;
                $sunglassesNoArr = array(
                    'name'=>'sunglasses non-prescription',
                    'value'=>$sunglassesNoNum,
                );
                $sportsProgressiveNum = $this->prescrtion_num('SportsProgressive',$site,$timeStr,$sku);
                $sportsProgressiveArr = array(
                    'name'=>'sports progressive',
                    'value'=>$sportsProgressiveNum,
                );
                $frameOnlyNum = $orderNum-$singleVisionNum-$progressiveNum-$readingGlassesNum-$readingGlassesNoNum-$noPrescriptionNum-$sunglassesNum-$sunglassesNoNum-$sportsProgressiveNum;
                $frameOnlyArr = array(
                    'name'=>'frame only',
                    'value'=>$frameOnlyNum,
                );
                $arr = [$singleVisionArr,$progressiveArr,$readingGlassesArr,$readingGlassesNoArr,$noPrescriptionArr,$sunglassesArr,$sunglassesNoArr,$sportsProgressiveArr,$frameOnlyArr];
            }elseif($site == 2 || $site == 10 || $site == 11){
                $readingGlassesNum = $this->prescrtion_num('ReadingGlasses',$site,$timeStr,$sku);
                $readingGlassesArr = array(
                    'name'=>'reading glasses',
                    'value'=>$readingGlassesNum,
                );
                $readingGlassesNoNum = $this->prescrtion_num('ReadingNoprescription',$site,$timeStr,$sku);
                $readingGlassesNoArr = array(
                    'name'=>'reading glasses no prescription',
                    'value'=>$readingGlassesNoNum,
                );
                $noPrescriptionNum1 = $this->prescrtion_num('Noprescription',$site,$timeStr,$sku);
                $noPrescriptionNum2 = $this->prescrtion_num('NonPrescription',$site,$timeStr,$sku);
                $noPrescriptionNum = $noPrescriptionNum1+$noPrescriptionNum2;
                $noPrescriptionArr = array(
                    'name'=>'no prescription',
                    'value'=>$noPrescriptionNum,
                );
                if($site == 2){
                    $sunglassesNum = $this->prescrtion_num('Sunglasses',$site,$timeStr,$sku);
                    $sunglassesArr = array(
                        'name'=>'sunglasses',
                        'value'=>$sunglassesNum,
                    );
                    $sunglassesNoNum1 = $this->prescrtion_num('Sunglasses_NonPrescription',$site,$timeStr,$sku);
                    $sunglassesNoNum2 = $this->prescrtion_num('SunGlassesNoprescription',$site,$timeStr,$sku);
                    $sunglassesNoNum = $sunglassesNoNum1+$sunglassesNoNum2;
                    $sunglassesNoArr = array(
                        'name'=>'sunglasses non-prescription',
                        'value'=>$sunglassesNoNum,
                    );
                    $frameOnlyNum = $orderNum-$singleVisionNum-$progressiveNum-$readingGlassesNum-$readingGlassesNoNum-$noPrescriptionNum-$sunglassesNum-$sunglassesNoNum;
                    $frameOnlyArr = array(
                        'name'=>'frame only',
                        'value'=>$frameOnlyNum,
                    );
                    $arr = [$singleVisionArr,$progressiveArr,$readingGlassesArr,$readingGlassesNoArr,$noPrescriptionArr,$sunglassesArr,$sunglassesNoArr,$frameOnlyArr];
                }elseif($site == 11){
                    $sunglassesNum = $this->prescrtion_num('SunGlasses',$site,$timeStr,$sku);
                    $sunglassesArr = array(
                        'name'=>'sunglasses',
                        'value'=>$sunglassesNum,
                    );
                    $sunglassesNoNum = $this->prescrtion_num('SunGlassesNoprescription',$site,$timeStr,$sku);
                    $sunglassesNoArr = array(
                        'name'=>'sunglasses non-prescription',
                        'value'=>$sunglassesNoNum,
                    );
                    $frameOnlyNum = $orderNum-$singleVisionNum-$progressiveNum-$readingGlassesNum-$readingGlassesNoNum-$noPrescriptionNum-$sunglassesNum-$sunglassesNoNum;
                    $frameOnlyArr = array(
                        'name'=>'frame only',
                        'value'=>$frameOnlyNum,
                    );
                    $arr = [$singleVisionArr,$progressiveArr,$readingGlassesArr,$readingGlassesNoArr,$noPrescriptionArr,$sunglassesArr,$sunglassesNoArr,$frameOnlyArr];
                }else{
                    $frameOnlyNum = $orderNum-$singleVisionNum-$progressiveNum-$readingGlassesNum-$readingGlassesNoNum-$noPrescriptionNum;
                    $frameOnlyArr = array(
                        'name'=>'frame only',
                        'value'=>$frameOnlyNum,
                    );
                    $arr = [$singleVisionArr,$progressiveArr,$readingGlassesArr,$readingGlassesNoArr,$noPrescriptionArr,$frameOnlyArr];
                }
            }elseif($site == 3){
                $readingGlassesNum = $this->prescrtion_num('Reading Glasses',$site,$timeStr,$sku);
                $readingGlassesArr = array(
                    'name'=>'reading glasses',
                    'value'=>$readingGlassesNum,
                );
                $readingGlassesNoNum = $this->prescrtion_num('Reading Glasses2',$site,$timeStr,$sku);
                $readingGlassesNoArr = array(
                    'name'=>'reading glasses no prescription',
                    'value'=>$readingGlassesNoNum,
                );
                $noPrescriptionNum = $this->prescrtion_num('NonPrescription',$site,$timeStr,$sku);
                $noPrescriptionArr = array(
                    'name'=>'no prescription',
                    'value'=>$noPrescriptionNum,
                );
                $sunglassesNum = $this->prescrtion_num('SunSingleVision',$site,$timeStr,$sku);
                $sunglassesArr = array(
                    'name'=>'sunglasses',
                    'value'=>$sunglassesNum,
                );
                $sunglassesNoNum = $this->prescrtion_num('SunNonPrescription',$site,$timeStr,$sku);
                $sunglassesNoArr = array(
                    'name'=>'sunglasses non-prescription',
                    'value'=>$sunglassesNoNum,
                );
                $frameOnlyNum = $orderNum-$singleVisionNum-$progressiveNum-$readingGlassesNum-$readingGlassesNoNum-$noPrescriptionNum-$sunglassesNum-$sunglassesNoNum;
                $frameOnlyArr = array(
                    'name'=>'frame only',
                    'value'=>$frameOnlyNum,
                );
                $arr = [$singleVisionArr,$progressiveArr,$readingGlassesArr,$readingGlassesNoArr,$noPrescriptionArr,$sunglassesArr,$sunglassesNoArr,$frameOnlyArr];
            }
        }
        if($site == 5){
            $readingGlassesNum = $this->prescrtion_num('ReadingGlasses',$site,$timeStr,$sku);
            $readingGlassesArr = array(
                'name'=>'reading glasses',
                'value'=>$readingGlassesNum,
            );
            $noPrescriptionNum = $this->prescrtion_num('NoPrescription',$site,$timeStr,$sku);
            $noPrescriptionArr = array(
                'name'=>'no prescription',
                'value'=>$noPrescriptionNum,
            );
            $lensOnlyNum = $this->prescrtion_num('LensOnly',$site,$timeStr,$sku);
            $lensOnlyNumArr = array(
                'name'=>'lens only',
                'value'=>$lensOnlyNum,
            );
            $frameOnlyNum = $orderNum-$readingGlassesNum-$noPrescriptionNum-$lensOnlyNum;
            $frameOnlyArr = array(
                'name'=>'frame only',
                'value'=>$frameOnlyNum,
            );
            $arr = [$readingGlassesArr,$noPrescriptionArr,$lensOnlyNumArr,$frameOnlyArr];
        }
        return $arr;
    }

    /**
     * 获取sku销量
     * @param string $flag  标识：不传值查所有
     * @param $site  站点
     * @param string $time_str  时间
     * @param string $sku  sku
     * @return mixed
     * @author mjj
     * @date   2021/5/18 15:40:00
     */
    function prescrtion_num($flag = '',$site,$time_str = '',$sku = ''){
        if(!$time_str){
            $start = strtotime(date('Y-m-d', strtotime('-6 day')));
            $end   = time();

        }else{
            $createat = explode(' ', $time_str);
            $start = strtotime($createat[0]);
            $end = strtotime($createat[3].' 23:59:59');
        }

        $where['o.payment_time'] = ['between', [$start, $end]];
        $where['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $where['i.sku'] = ['like',$sku.'%'];
        $where['o.order_type'] = 1;
        $where['o.site'] = $site;

        if($flag){
            $where['i.prescription_type'] = $flag;
        }
        $count = $this->order
            ->alias('o')
            ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
            ->where($where)
            ->sum('i.qty');
        return $count;
    }

    /**
     * 商品销量/现价折线图
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/17
     * Time: 13:45:00
     */
    public function sku_sales_data_line()
    {
        if ($this->request->isAjax()) {
            $sku = input('sku');
            $site = input('order_platform');
            $time_str = input('time_str');
            $createat = explode(' ', $time_str);
            $same_where['day_date'] = ['between', [$createat[0], $createat[3]]];
            $same_where['site'] = ['=', $site];
            $same_where['platform_sku'] = ['like', $sku . '%'];
            $recent_day_num = Db::name('datacenter_sku_day')->where($same_where)->order('day_date', 'asc')->column('glass_num', 'day_date');
            $recent_day_now = Db::name('datacenter_sku_day')->where($same_where)->order('day_date', 'asc')->column('now_pricce', 'day_date');
            $json['xColumnName'] = array_keys($recent_day_num);
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_values($recent_day_num),
                    'name' => '商品销量',
                    'yAxisIndex' => 0,
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => array_values($recent_day_now),
                    'name' => '售价',
                    'yAxisIndex' => 1,
                    'smooth' => true //平滑曲线
                ],

            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 最近30天销量柱状图
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/17
     * Time: 13:45:24
     */
    public function sku_sales_data_bar()
    {
        if ($this->request->isAjax()) {
            $sku = input('sku');
            $site = input('order_platform');
            $end = date('Y-m-d');
            $start = date('Y-m-d', strtotime("-30 days", strtotime($end)));
            $same_where['day_date'] = ['between', [$start, $end]];
            $same_where['site'] = ['=', $site];
            $same_where['platform_sku'] = ['like', $sku . '%'];
            $recent_30_day = Db::name('datacenter_sku_day')->where($same_where)->order('day_date', 'asc')->column('glass_num', 'day_date');
            $json['xColumnName'] = array_keys($recent_30_day);
            $json['columnData'] = [
                'type' => 'bar',
                'data' => array_values($recent_30_day),
                'name' => '销量'
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 导出关联购买数据
     *
     * Created by Phpstorm.
     * User: jhh
     * Date: 2020/12/17
     * Time: 13:45:51
     */
    public function export(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $time_str = input('time_str') ? input('time_str') : '';
        $sku = input('sku');
        $map['o.site'] = input('order_platform') ? input('order_platform') : 1;
        //时间
        if ($time_str) {
            $createat = explode(' ', $time_str);
            $map['o.payment_time'] = ['between', [strtotime($createat[0]), strtotime($createat[3] . ' 23:59:59')]];
        } else {
            $start = strtotime(date('Y-m-d', strtotime('-6 day')));
            $end = strtotime(date('Y-m-d 23:59:59'));
            $map['o.payment_time'] = ['between', [$start, $end]];
        }
        $map['i.sku'] = ['like', $sku . '%'];
        $map['o.status'] = ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal','delivered']];
        $map['o.order_type'] = 1;
        //关联购买
        $orderIds = $this->order
            ->alias('o')
            ->join(['fa_order_item_option' => 'i'], 'o.entity_id=i.magento_order_id')
            ->where($map)
            ->field('distinct magento_order_id')
            ->buildSql();
        $orderIdsWhere = [];
        $orderIdsWhere[] = ['exp', Db::raw("magento_order_id in " . $orderIds)];
        $arraySku = $this->orderitemoption
            ->where($orderIdsWhere)
            ->where('sku','not like',$sku . '%')
            ->group('sku')
            ->order('count desc')
            ->column('sum(qty) count','sku');
        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setCellValue("A1", "sku");
        $spreadsheet->getActiveSheet()->setCellValue("B1", "数量");
        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(60);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->setActiveSheetIndex(0)->setTitle('SKU明细');
        $spreadsheet->setActiveSheetIndex(0);
        $num = 0;
        foreach ($arraySku as $k=>$v){
            $spreadsheet->getActiveSheet()->setCellValue('A' . ($num * 1 + 2), $k);
            $spreadsheet->getActiveSheet()->setCellValue('B' . ($num * 1 + 2), $v);
            $num += 1;
        }
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
        $spreadsheet->getActiveSheet()->getStyle('A1:Q' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->setActiveSheetIndex(0);
        $format = 'xlsx';
        $savename = 'sku:'.$sku .' '. $createat[0] .'至'.$createat[3] .'关联购买情况';
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










