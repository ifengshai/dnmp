<?php

namespace app\admin\controller\operatedatacenter\userdata;

use app\admin\model\platformManage\MagentoPlatform;
use app\common\controller\Backend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Controller;
use think\Db;
use think\Request;

class UserDataAgainBuy extends Backend
{
    //增加权限设置，需要登录，无需设置权限
    protected $noNeedRight = [
        'year_again_buy_rate_line',
        'year_again_buy_num_line',
        'year_again_buy_export',
        'old_user_rate_line',
        'new_old_user_rate_line',
        'old_user_export',
        'user_define_repurchase_rate_line',
        'user_define_repurchase_num_line',
        'user_define_repurchase_rate_export'
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform;
        $this->repurchase = new \app\admin\model\operatedatacenter\Repurchase();
        $this->monthweb = new \app\admin\model\operatedatacenter\MonthWeb();
    }

    /**
     * 年复购率页面展示
     * @return string
     * @throws \think\Exception
     * @author mjj
     * @date   2021/4/2 15:18:22
     */
    public function year_again_buy_rate()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'];
            $data = $this->repurchase->getAgainData($site, 4, true);
            $str = '';
            foreach ($data as $value) {
                $str .= '<tr>';
                $str .= '<td>' . $value['day_date'] . '</td>';
                $str .= '<td>' . $value['usernum'] . '</td>';
                $str .= '<td>' . $value['againbuy_usernum'] . '</td>';
                $str .= '<td>' . $value['againbuy_usernum_ordernum'] . '</td>';
                $str .= '<td>' . $value['againbuy_rate'] . '%</td>';
                $str .= '<td>' . $value['againbuy_num_rate'] . '</td>';
                $str .= '</tr>';
            }
            $this->success('操作成功', '', $str);
        }
        $list = $this->repurchase->getAgainData(1, 4, true);
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $data = compact('list', 'magentoplatformarr');
        $this->view->assign($data);
        return $this->view->fetch();
    }

    /**
     * 年复购率数据--年复购率折线图
     * @return \think\response\Json
     * @author mjj
     * @date   2021/4/2 15:22:30
     */
    public function year_again_buy_rate_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $data = $this->repurchase->getAgainData($site, 4, true);   //获取复购用户数据
            $data = collection($data)->toArray();
            array_multisort(array_column($data, 'day_date'), SORT_ASC, $data);
            $json['xcolumnData'] = array_column($data, 'day_date');
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_column($data, 'againbuy_rate'),
                    'name' => '年复购率',
                    'smooth' => true //平滑曲线
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 年复购率数据--年复购频次折线图
     * @return \think\response\Json
     * @author mjj
     * @date   2021/4/2 15:41:44
     */
    public function year_again_buy_num_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $data = $this->repurchase->getAgainData($site, 4, true);   //获取复购用户数据
            $data = collection($data)->toArray();
            array_multisort(array_column($data, 'day_date'), SORT_ASC, $data);
            $json['xcolumnData'] = array_column($data, 'day_date');
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_column($data, 'againbuy_num_rate'),
                    'name' => '年复购频次',
                    'smooth' => true //平滑曲线
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 年复购率数据导出
     * @author mjj
     * @date   2021/4/2 15:53:24
     */
    public function year_again_buy_export()
    {
        set_time_limit(0);
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename=" . iconv("UTF-8", "GB18030", date('Y-m-d-His', time())) . ".csv");//导出文件名
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        $site = input('order_platform');

        // 将中文标题转换编码，否则乱码
        $fieldArr = array(
            '日期（月）',
            '客户数',
            '年复购客户数',
            '年复购客户订单数',
            '年复购率',
            '年复购频次'
        );
        foreach ($fieldArr as $i => $v) {
            $fieldArr[$i] = iconv('utf-8', 'GB18030', $v);
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $fieldArr);
        $list = $this->repurchase->getAgainData($site, 4);   //获取复购用户数据
        $list = collection($list)->toArray();
        //整理数据
        foreach ($list as &$val) {
            $tmpRow = [];
            $tmpRow['day_date'] = $val['day_date'];//时间
            $tmpRow['usernum'] = $val['usernum'];//客户数
            $tmpRow['againbuy_usernum'] = $val['againbuy_usernum'];//复购用户数
            $tmpRow['againbuy_usernum_ordernum'] = $val['againbuy_usernum_ordernum'];//复购用户订单数
            $tmpRow['againbuy_rate'] = $val['againbuy_rate'] . '%';//复购率
            $tmpRow['againbuy_num_rate'] = $val['againbuy_num_rate'];//复购频次
            $rows = array();
            foreach ($tmpRow as $export_obj) {
                $rows[] = iconv('utf-8', 'GB18030', $export_obj);
            }
            fputcsv($fp, $rows);
        }
        // 将已经写到csv中的数据存储变量销毁，释放内存占用
        unset($list);
        ob_flush();
        flush();
        fclose($fp);
    }

    /**
     * 老用户占比页面展示
     * @return string
     * @throws \think\Exception
     * @author mjj
     * @date   2021/4/2 15:18:22
     */
    public function old_user_rate()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'];
            $data = $this->monthweb->getOldNewUserData($site, true);
            $str = '';
            foreach ($data as $value) {
                $str .= '<tr>';
                $str .= '<td>' . $value['day_date'] . '</td>';
                $str .= '<td>' . $value['usernum'] . '</td>';
                $str .= '<td>' . $value['old_usernum'] . '</td>';
                $str .= '<td>' . $value['old_usernum_rate'] . '%</td>';
                $str .= '<td>' . $value['old_usernum_sequential'] . '%</td>';
                $str .= '<td>' . $value['new_usernum_sequential'] . '%</td>';
                $str .= '</tr>';
            }
            $this->success('操作成功', '', $str);
        }
        $list = $this->monthweb->getOldNewUserData(1, true);
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $data = compact('list', 'magentoplatformarr');
        $this->view->assign($data);
        return $this->view->fetch();
    }

    /**
     * 老用户占比数据--老用户占比折线图
     * @return \think\response\Json
     * @author mjj
     * @date   2021/4/2 15:41:44
     */
    public function old_user_rate_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $data = $this->monthweb->getOldNewUserData($site, true);   //获取老用户数据
            $data = collection($data)->toArray();
            array_multisort(array_column($data, 'day_date'), SORT_ASC, $data);
            $json['xcolumnData'] = array_column($data, 'day_date');
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_column($data, 'old_usernum_rate'),
                    'name' => '老客户占比',
                    'smooth' => true //平滑曲线
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 老用户占比数据--新老客户环比折线图
     * @author mjj
     * @date   2021/4/2 16:22:22
     */
    public function new_old_user_rate_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $data = $this->monthweb->getOldNewUserData($site, true);   //获取老用户数据
            $data = collection($data)->toArray();
            array_multisort(array_column($data, 'day_date'), SORT_ASC, $data);
            $arr['xdata'] = array_column($data, 'day_date');
            $arr['ydata']['one'] = array_column($data, 'old_usernum_sequential');
            $arr['ydata']['two'] = array_column($data, 'new_usernum_sequential');
            $json['xColumnName'] = $arr['xdata'];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => $arr['ydata']['one'],
                    'name' => '老客户环比',
                    'smooth' => true //平滑曲线
                ],
                [
                    'type' => 'line',
                    'data' => $arr['ydata']['two'],
                    'name' => '新客户环比',
                    'smooth' => true //平滑曲线
                ],
            ];

            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 老用户占比数据导出
     * @author mjj
     * @date   2021/4/2 15:53:24
     */
    public function old_user_export()
    {
        set_time_limit(0);
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename=" . iconv("UTF-8", "GB18030", date('Y-m-d-His', time())) . ".csv");//导出文件名
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        $site = input('order_platform');

        // 将中文标题转换编码，否则乱码
        $fieldArr = array(
            '日期（月）',
            '客户数',
            '老客户数',
            '老客户占比',
            '老客户环比变动',
            '新客户环比变动'
        );
        foreach ($fieldArr as $i => $v) {
            $fieldArr[$i] = iconv('utf-8', 'GB18030', $v);
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $fieldArr);
        $list = $this->monthweb->getOldNewUserData($site);   //获取老用户数据
        $list = collection($list)->toArray();
        //整理数据
        foreach ($list as &$val) {
            $tmpRow = [];
            $tmpRow['day_date'] = $val['day_date'];//时间
            $tmpRow['usernum'] = $val['usernum'];//客户数
            $tmpRow['old_usernum'] = $val['old_usernum'];//老用户数
            $tmpRow['old_usernum_rate'] = $val['old_usernum_rate'] . '%';//老用户占比
            $tmpRow['old_usernum_sequential'] = $val['old_usernum_sequential'] . '%';//老用户环比变动
            $tmpRow['new_usernum_sequential'] = $val['new_usernum_sequential'] . '%';//新用户环比变动
            $rows = array();
            foreach ($tmpRow as $export_obj) {
                $rows[] = iconv('utf-8', 'GB18030', $export_obj);
            }
            fputcsv($fp, $rows);
        }
        // 将已经写到csv中的数据存储变量销毁，释放内存占用
        unset($list);
        ob_flush();
        flush();
        fclose($fp);
    }

    /**
     * 自定义复购率数据页面
     * @return string
     * @throws \think\Exception
     * @author mjj
     * @date   2021/4/2 17:34:34
     */
    public function user_define_repurchase_rate()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'];
            $repurchaseWeek = $params['repurchase_week'];
            $data = $this->repurchase->getAgainData($site, $repurchaseWeek, true);
            $str = '';
            foreach ($data as $value) {
                $str .= '<tr>';
                $str .= '<td>' . $value['day_date'] . '</td>';
                $str .= '<td>' . $value['usernum'] . '</td>';
                $str .= '<td>' . $value['againbuy_usernum'] . '</td>';
                $str .= '<td>' . $value['againbuy_usernum_ordernum'] . '</td>';
                $str .= '<td>' . $value['againbuy_rate'] . '%</td>';
                $str .= '<td>' . $value['againbuy_num_rate'] . '</td>';
                $str .= '</tr>';
            }
            $this->success('操作成功', '', $str);
        }
        $list = $this->repurchase->getAgainData(1, 1, true);
        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        foreach ($magentoplatformarr as $key => $val) {
            if (!in_array($val['name'], ['zeelool', 'voogueme', 'nihao'])) {
                unset($magentoplatformarr[$key]);
            }
        }
        $data = compact('list', 'magentoplatformarr');
        $this->view->assign($data);
        return $this->view->fetch();
    }

    /**
     * 自定义复购率数据--年复购率折线图
     * @return \think\response\Json
     * @author mjj
     * @date   2021/4/2 15:22:30
     */
    public function user_define_repurchase_rate_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $repurchaseWeek = $params['repurchase_week'];
            $data = $this->repurchase->getAgainData($site, $repurchaseWeek, true);   //获取复购用户数据
            $data = collection($data)->toArray();
            array_multisort(array_column($data, 'day_date'), SORT_ASC, $data);
            switch ($repurchaseWeek) {
                case 1:
                    $name = '一月期复购率';
                    break;
                case 2:
                    $name = '三月期复购率';
                    break;
                case 3:
                    $name = '半年期复购率';
                    break;
                case 4:
                    $name = '一年期复购率';
                    break;
            }
            $json['xcolumnData'] = array_column($data, 'day_date');
            $json['column'] = [$name];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_column($data, 'againbuy_rate'),
                    'name' => $name,
                    'smooth' => true //平滑曲线
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 自定义复购率数据--年复购频次折线图
     * @return \think\response\Json
     * @author mjj
     * @date   2021/4/2 15:41:44
     */
    public function user_define_repurchase_num_line()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();
            $site = $params['order_platform'] ? $params['order_platform'] : 1;
            $repurchaseWeek = $params['repurchase_week'];
            $data = $this->repurchase->getAgainData($site, $repurchaseWeek, true);   //获取复购用户数据
            $data = collection($data)->toArray();
            array_multisort(array_column($data, 'day_date'), SORT_ASC, $data);
            switch ($repurchaseWeek) {
                case 1:
                    $name = '一月期复购频次';
                    break;
                case 2:
                    $name = '三月期复购频次';
                    break;
                case 3:
                    $name = '半年期复购频次';
                    break;
                case 4:
                    $name = '一年期复购频次';
                    break;
            }
            $json['xcolumnData'] = array_column($data, 'day_date');
            $json['column'] = [$name];
            $json['columnData'] = [
                [
                    'type' => 'line',
                    'data' => array_column($data, 'againbuy_num_rate'),
                    'name' => $name,
                    'smooth' => true //平滑曲线
                ],
            ];
            return json(['code' => 1, 'data' => $json]);
        }
    }

    /**
     * 年复购率数据导出
     * @author mjj
     * @date   2021/4/2 15:53:24
     */
    public function user_define_repurchase_rate_export()
    {
        set_time_limit(0);
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:filename=" . iconv("UTF-8", "GB18030", date('Y-m-d-His', time())) . ".csv");//导出文件名
        // 打开PHP文件句柄，php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        $site = input('order_platform');
        $repurchaseWeek = input('repurchase_week');

        // 将中文标题转换编码，否则乱码
        $fieldArr = array(
            '日期（月）',
            '客户数',
            '年复购客户数',
            '年复购客户订单数',
            '年复购率',
            '年复购频次'
        );
        foreach ($fieldArr as $i => $v) {
            $fieldArr[$i] = iconv('utf-8', 'GB18030', $v);
        }
        // 将标题名称通过fputcsv写到文件句柄
        fputcsv($fp, $fieldArr);
        $list = $this->repurchase->getAgainData($site, $repurchaseWeek);   //获取复购用户数据
        $list = collection($list)->toArray();
        //整理数据
        foreach ($list as &$val) {
            $tmpRow = [];
            $tmpRow['day_date'] = $val['day_date'];//时间
            $tmpRow['usernum'] = $val['usernum'];//客户数
            $tmpRow['againbuy_usernum'] = $val['againbuy_usernum'];//复购用户数
            $tmpRow['againbuy_usernum_ordernum'] = $val['againbuy_usernum_ordernum'];//复购用户订单数
            $tmpRow['againbuy_rate'] = $val['againbuy_rate'] . '%';//复购率
            $tmpRow['againbuy_num_rate'] = $val['againbuy_num_rate'];//复购频次
            $rows = array();
            foreach ($tmpRow as $export_obj) {
                $rows[] = iconv('utf-8', 'GB18030', $export_obj);
            }
            fputcsv($fp, $rows);
        }
        // 将已经写到csv中的数据存储变量销毁，释放内存占用
        unset($list);
        ob_flush();
        flush();
        fclose($fp);
    }
}
