<?php

namespace app\admin\controller\operatedatacenter\appdata;

use app\enum\Site;
use app\enum\Store;

class Detail extends Dashboard
{
    protected $noNeedRight = ['*'];

    public function index()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $store_ids = [Store::IOS => 'ios', Store::ANDROID => 'android', 999 => '全部'];

        if ($this->request->isAjax()) {
            $filter = json_decode($this->request->get('filter'), true);
            $site = $filter['site'] ?: Site::ZEELOOL;
            $platform = $filter['store_id'] ?: Store::IOS;
            $date = $filter['date'];

            if (!in_array($site, array_column($magentoplatformarr, 'id'))) {
                return ['msg' => '平台未知'];
            }
            if (!isset($store_ids[$platform])) {
                return ['msg' => '客户端未知'];
            }
            if (!$date) {
                return json([
                    'total' => 0,
                    'rows' => []
                ]);
            }

            $time = explode(' - ', $date);
            $start_time = date('Y-m-d', strtotime($time[0]));
            $end_time = date('Y-m-d', strtotime($time[1]));

            // 当日获取数据
            $data = $this->getDetailData($site, $platform, $start_time, $end_time);

            return json([
                'total' => count($data),
                'rows' => array_values($data),
            ]);
        }
        $this->view->assign('magentoplatformarr', $magentoplatformarr);
        $this->view->assign('store_ids', $store_ids);
        return $this->view->fetch();
    }

    protected function getDetailData($site, $platform, $start_time, $end_time)
    {
        if ($platform == 999) {
            $platforms = [Store::IOS, Store::ANDROID];
        } else {
            $platforms = [$platform];
        }

        $order_list = [];
        $ga_list = [];
        $ad_list = [];
        foreach ($platforms as $plat) {
            // 获取订单数据
            $list = $this->getOrderData($site, $plat, $start_time, $end_time);
            $order_list = self::arraySum($order_list, $list);

            // 获取GA数据
            $list = $this->getGaData($site, $plat, $start_time, $end_time);
            $ga_list = self::arraySum($ga_list, $list);

            // 获取googleads数据
//            $list = $this->getAdData($site, $plat, $start_time, $end_time);
//            $ad_list = self::arraySum($ad_list, $list);
        }

        $data = [];
        for ($time = strtotime($end_time); $time >= strtotime($start_time); $time -= 86400) {
            $date = date('Y-m-d', $time);
            $data[$time] = [
                'date' => $date,
                'download_count_paid' => 0,
                'ad_cost' => 0,
                'sessions' => $ga_list[$date]['sessions'] ?: 0,
                'activeUsers' => $ga_list[$date]['activeUsers'] ?: 0,
                'first_open' => $ga_list[$date]['first_open'] ?: 0,
                'app_remove' => $ga_list[$date]['app_remove'] ?: 0,
                'order_money' => $order_list[$date]['order_money'] ?: 0,
                'order_num' => $order_list[$date]['order_num'] ?: 0,
                'money_per_user' => 0,
            ];
            $data[$time]['money_per_user'] = $data[$time]['order_num'] > 0 ? round($data[$time]['order_money'] / $data[$time]['order_num'], 2) : 0;
        }
        return array_values($data);
    }

    public function export()
    {
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $store_ids = [Store::IOS => 'ios', Store::ANDROID => 'android', 999 => '全部'];

        // 输入参数
        $site = input('site');
        $store_id = input('store_id');
        $date = input('date');
        $field = input('field');

        if (!in_array($site, array_column($magentoplatformarr, 'id'))) {
            return ['msg' => '平台未知'];
        }
        if (!isset($store_ids[$store_id])) {
            return ['msg' => '客户端未知'];
        }
        if (!$date) {
            return json([
                'total' => 0,
                'rows' => []
            ]);
        }

        set_time_limit(0);
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename=" . iconv("UTF-8", "GB18030", date('Y-m-d-His', time())) . ".csv");//导出文件名

        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        $field_arr = explode(',', $field);
        $field_info = array(
            array(
                'name' => '日期',
                'field' => 'date',
            ),
//            array(
//                'name' => '付费下载数',
//                'field' => 'download_count_paid',
//            ),
//            array(
//                'name' => '花费',
//                'field' => 'ad_cost',
//            ),
            array(
                'name' => '卸载量',
                'field' => 'app_remove',
            ),
            array(
                'name' => '会话数',
                'field' => 'sessions',
            ),
            array(
                'name' => '用户数',
                'field' => 'activeUsers',
            ),
            array(
                'name' => '首次打开',
                'field' => 'first_open',
            ),
            array(
                'name' => '订单金额',
                'field' => 'order_money',
            ),
            array(
                'name' => '订单数',
                'field' => 'order_num',
            ),
            array(
                'name' => '客单价',
                'field' => 'money_per_user',
            ),
        );
        $column_name = [];
        $columns = [];
        // 将中文标题转换编码，否则乱码
        foreach ($field_arr as $i => $v) {
            $title_name = $this->filter_by_value($field_info, 'field', $v);
            $column_name[$i] = iconv('utf-8', 'GB18030', $title_name['name']);
            $columns[$i] = $v;
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $column_name);

        $time = explode(' - ', $date);
        $start_time = date('Y-m-d', strtotime($time[0]));
        $end_time = date('Y-m-d', strtotime($time[1]));

        // 当日获取数据
        $data = $this->getDetailData($site, $store_id, $start_time, $end_time);
        foreach ($data as $datum) {
            $row = [];
            foreach ($columns as $column) {
                foreach ($datum as $key => $item) {
                    if ($key == $column) {
                        $row[] = $item;
                        break;
                    }
                }
            }
            fputcsv($fp, $row);
        }
        fclose($fp);
        exit();
    }

    protected function filter_by_value($array, $index, $value)
    {
        if (is_array($array) && count($array) > 0) {
            foreach (array_keys($array) as $key) {
                $temp[$key] = $array[$key][$index];
                if ($temp[$key] == $value) {
                    $newarray = $array[$key];
                }
            }
        }
        return $newarray;
    }
}
