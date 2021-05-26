<?php
/**
 * Class OrderPrescriptionNew.php
 * @package app\admin\controller\operatedatacenter\orderdata
 * @author  crasphb
 * @date    2021/5/12 12:30
 */

namespace app\admin\controller\operatedatacenter\orderdata;


use app\admin\model\order\OrderItemOption;
use app\common\controller\Backend;
use think\Db;

class OrderPrescriptionNew extends Backend
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {

        //查询对应平台权限
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao', 'zeelool_de', 'zeelool_jp', 'wesee'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $this->view->assign(compact('data', 'total', 'coating_arr', 'coating_count', 'web_site', 'time_show', 'magentoplatformarr'));

        return $this->view->fetch();
    }

    /**
     * ajax获取数据
     * @author crasphb
     * @date   2021/5/12 13:52
     */
    public function ajaxGet()
    {
        $time_str = input('time_str');
        if (!$time_str) {
            $start = date('Y-m-d 00:00:00', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $time_between = $start . ' - ' . $end;
            $time_show = '';
        } else {
            $time_between = $time_str;
            $time_show = $time_str;
        }
        $web_site = input('order_platform') ? input('order_platform') : 1;
        $prescrition = $this->prescrtion_data($web_site, $time_between);
        $coating = $this->coating_data($web_site, $time_between);
        $this->success('', '', compact('prescrition', 'coating', 'web_site', 'time_show'));
    }

    //处方统计
    function prescrtion_data($site = 1, $time_str = '')
    {
        if (!$time_str) {
            $start = date('Y-m-d', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $time_str = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
        }
        $data = $this->get_prescrtion_num($site, $time_str);
        $order_num = $this->prescrtion_num('', $data);
        $single_vision_num = $this->prescrtion_num('SingleVision', $data);
        $single_vision_rate = $order_num ? round($single_vision_num / $order_num * 100, 0) . '%' : 0;
        $single_vision_arr = [
            'name' => 'single vision',
            'num'  => $single_vision_num,
            'rate' => $single_vision_rate,
        ];
        $progressive_num = $this->prescrtion_num('Progressive', $data);
        $progressive_rate = $order_num ? round($progressive_num / $order_num * 100, 0) . '%' : 0;
        $progressive_arr = [
            'name' => 'progressive',
            'num'  => $progressive_num,
            'rate' => $progressive_rate,
        ];
        if ($site == 3) {
            $reading_glasses_num = $this->prescrtion_num('Reading Glasses', $data);
        } elseif ($site == 10 || $site == 11) {
            $reading_glasses_num = $this->prescrtion_num('ReadingGlasses', $data);
        } else {
            $reading_glasses_num = $this->prescrtion_num('Readingglasses', $data);
        }
        $reading_glasses_rate = $order_num ? round($reading_glasses_num / $order_num * 100, 0) . '%' : 0;
        $reading_glasses_arr = [
            'name' => 'reading glasses',
            'num'  => $reading_glasses_num,
            'rate' => $reading_glasses_rate,
        ];
        if ($site == 2 || $site == 10 || $site == 11) {
            $reading_glassesno_num = $this->prescrtion_num('ReadingNoprescription', $data);
        } elseif ($site == 3) {
            $reading_glassesno_num = $this->prescrtion_num('Reading Glasses2', $data);
        } else {
            $reading_glassesno_num = $this->prescrtion_num('ReadingGlassesNon', $data);
        }
        $reading_glassesno_rate = $order_num ? round($reading_glassesno_num / $order_num * 100, 0) . '%' : 0;
        $reading_glassesno_arr = [
            'name' => 'reading glasses no prescription',
            'num'  => $reading_glassesno_num,
            'rate' => $reading_glassesno_rate,
        ];
        if ($site == 2 || $site == 10 || $site == 11 || $site == 5) {
            $no_prescription_num1 = $this->prescrtion_num('NonPrescription', $data);
            $no_prescription_num2 = $this->prescrtion_num('Noprescription', $datar);
            $no_prescription_num = $no_prescription_num1 + $no_prescription_num2;
        } else {
            $no_prescription_num = $this->prescrtion_num('NonPrescription', $data);
        }
        $no_prescription_rate = $order_num ? round($no_prescription_num / $order_num * 100, 0) . '%' : 0;
        $no_prescription_arr = [
            'name' => 'no prescription',
            'num'  => $no_prescription_num,
            'rate' => $no_prescription_rate,
        ];
        if ($site == 3) {
            $sunglasses_num = $this->prescrtion_num('SunSingleVision', $data);
        } elseif ($site == 11) {
            $sunglasses_num = $this->prescrtion_num('SunGlasses', $data);
        } else {
            $sunglasses_num = $this->prescrtion_num('Sunglasses', $data);
        }

        $sunglasses_rate = $order_num ? round($sunglasses_num / $order_num * 100, 0) . '%' : 0;
        $sunglasses_arr = [
            'name' => 'sunglasses',
            'num'  => $sunglasses_num,
            'rate' => $sunglasses_rate,
        ];
        if ($site == 2) {
            $sunglassesno_num1 = $this->prescrtion_num('Sunglasses_NonPrescription', $data);
            $sunglassesno_num2 = $this->prescrtion_num('SunGlassesNoprescription', $data);
            $sunglassesno_num = $sunglassesno_num1 + $sunglassesno_num2;
        } elseif ($site == 1) {
            $sunglassesno_num1 = $this->prescrtion_num('SunGlassesNoprescription', $data);
            $sunglassesno_num2 = $this->prescrtion_num('Non', $data);
            $sunglassesno_num = $sunglassesno_num1 + $sunglassesno_num2;
        } elseif ($site == 3) {
            $sunglassesno_num = $this->prescrtion_num('SunNonPrescription', $data);
        } else {
            $sunglassesno_num = $this->prescrtion_num('SunGlassesNoprescription', $data);
        }

        $sunglassesno_rate = $order_num ? round($sunglassesno_num / $order_num * 100, 0) . '%' : 0;
        $sunglassesno_arr = [
            'name' => 'sunglasses non-prescription',
            'num'  => $sunglassesno_num,
            'rate' => $sunglassesno_rate,
        ];
        $sports_single_vision_num = $this->prescrtion_num('SportsSingleVision', $data);
        $sports_single_vision_rate = $order_num ? round($sports_single_vision_num / $order_num * 100, 0) . '%' : 0;
        $sports_single_vision_arr = [
            'name' => 'sports single vision',
            'num'  => $sports_single_vision_num,
            'rate' => $sports_single_vision_rate,
        ];
        $sports_progressive_num = $this->prescrtion_num('SportsProgressive', $data);
        $sports_progressive_rate = $order_num ? round($sports_progressive_num / $order_num * 100, 0) . '%' : 0;
        $sports_progressive_arr = [
            'name' => 'sports progressive',
            'num'  => $sports_progressive_num,
            'rate' => $sports_progressive_rate,
        ];
        $frame_only_num = $order_num - $single_vision_num - $progressive_num - $reading_glasses_num - $reading_glassesno_num - $no_prescription_num - $sunglasses_num - $sunglassesno_num - $sports_single_vision_num - $sports_progressive_num;
        $frame_only_rate = $order_num ? round($frame_only_num / $order_num * 100, 0) . '%' : 0;
        $frame_only_arr = [
            'name' => 'frame only',
            'num'  => $frame_only_num,
            'rate' => $frame_only_rate,
        ];
        $arr = [$frame_only_arr, $single_vision_arr, $progressive_arr, $reading_glasses_arr, $reading_glassesno_arr, $no_prescription_arr, $sunglasses_arr, $sunglassesno_arr, $sports_single_vision_arr, $sports_progressive_arr];
        $result = "";
        foreach ($arr as $key => $val) {
            $num = $key + 1;
            $result .= "<tr>
                                    <td>{$num}</td>
                                    <td>{$val['name']}</td>
                                    <td>{$val['num']}</td>
                                    <td>{$val['rate']}</td>
                                </tr>";
        }
        $result .= "<tr>
                                    <td></td>
                                    <td>合计</td>
                                    <td>{$order_num}</td>
                                    <td></td>
                                </tr>";

        return $result;
    }


    public function coating_data($site = 1, $timeStr = '')
    {
        if ($site == 2) {
            $type = [4.95, 8.95, 9.95];
        } elseif ($site == 10) {
            $type = [4.95, 8.95, 9.95];
        } elseif ($site == 11) {
            $type = [4.95, 8.95, 9.95];
        } elseif ($site == 1 || $site == 2) {
            $type = [0, 5, 9];
        }
        if (!$timeStr) {
            $start = date('Y-m-d', strtotime('-6 day'));
            $end = date('Y-m-d 23:59:59');
            $timeStr = $start . ' 00:00:00 - ' . $end . ' 00:00:00';
        }
        $createat = explode(' ', $timeStr);
        $begin = strtotime($createat[0]);
        $end = strtotime($createat[3] . ' 23:59:59');
        $coatingNumArr = Db::connect('database.db_mojing_order')->table('fa_order_item_option')
            ->alias('a')
            ->join(['fa_order' => 'b'], 'b.id= a.order_id')
            ->where([
                'b.payment_time' => ['between', [$begin, $end]],
                'b.status'       => ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']],
                'b.order_type'   => 1,
            ])
            ->where(['a.site' => $site])
            ->where('coating_id', 'in', ['coating_1', 'coating_2', 'coating_3'])
            ->field('count(a.id) as count,coating_id')
            ->group('coating_id')
            ->select();
        $coatingRate1 = $coatingRate2 = $coatingRate3 = '0%';
        $coatingNum = $coatingNum1 = $coatingNum2 = $coatingNum3 = '0';
        foreach ($coatingNumArr as $key => $val) {
            if ($val['coating_id'] == 'coating_1') {
                $coatingNum1 += $val['count'];
            } elseif ($val['coating_id'] == 'coating_2') {
                $coatingNum2 += $val['count'];
            } elseif ($val['coating_id'] == 'coating_3') {
                $coatingNum3 += $val['count'];
            }
            $coatingNum += $val['count'];
        }

        $coatingRate1 = $coatingNum ? round($coatingNum1 / $coatingNum * 100, 0) . '%' : 0;
        $coatingRate2 = $coatingNum ? round($coatingNum2 / $coatingNum * 100, 0) . '%' : 0;
        $coatingRate3 = $coatingNum ? round($coatingNum3 / $coatingNum * 100, 0) . '%' : 0;
        $result = "<tr>
                                    <td>1</td>
                                    <td>{$type[0]}</td>
                                    <td>{$coatingNum1}</td>
                                    <td>{$coatingRate1}</td>
                                </tr><tr>
                                    <td>2</td>
                                    <td>{$type[1]}</td>
                                    <td>{$coatingNum2}</td>
                                    <td>{$coatingRate2}</td>
                                </tr><tr>
                                    <td>3</td>
                                    <td>{$type[2]}</td>
                                    <td>{$coatingNum3}</td>
                                    <td>{$coatingRate3}</td>
                                </tr><tr>
                                    <td></td>
                                    <td>合计</td>
                                    <td>{$coatingNum}</td>
                                    <td></td>
                                </tr>";

        return $result;
    }

    //镀膜

    /**
     * 数目
     *
     * @param string $flag
     * @param int    $site
     * @param string $timeStr
     *
     * @return int|string
     * @throws \think\Exception
     * @author crasphb
     * @date   2021/5/12 12:52
     */
    function get_prescrtion_num($site = 1, $timeStr = '')
    {
        $createat = explode(' ', $timeStr);
        $begin = strtotime($createat[0]);
        $end = strtotime($createat[3] . ' 23:59:59');
       return Db::connect('database.db_mojing_order')->table('fa_order_item_option')
            ->alias('a')
            ->join(['fa_order' => 'b'], 'b.id= a.order_id')
            ->where([
                'b.payment_time' => ['between', [$begin, $end]],
                'b.status'       => ['in', ['free_processing', 'processing', 'complete', 'paypal_reversed', 'payment_review', 'paypal_canceled_reversal', 'delivered']],
                'b.order_type'   => 1,
            ])
            ->where(['a.site' => $site])
            ->field('count(*) as count,prescription_type')
            ->group('prescription_type')
            ->select();

    }
    function prescrtion_num($flag = '', $data)
    {
        $count = 0;
        if(empty($data)){
            $data = [];
        }
        foreach($data as $key => $val) {
            if($flag) {
                if($val['prescription_type'] == $flag){
                    return $val['count'];
                }
            }else{
                $count += $val['count'];
            }

        }
        return $count;
    }
}