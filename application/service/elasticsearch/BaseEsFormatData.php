<?php
/**
 * Class BaseEsFormatData.php
 * @package app\service\elasticsearch
 * @author  crasphb
 * @date    2021/5/8 15:17
 */

namespace app\service\elasticsearch;


use app\enum\Site;

class BaseEsFormatData
{
    /**
     * 根据站点id获取站点名称
     *
     * @param $site
     *
     * @return string
     * @author crasphb
     * @date   2021/5/8 15:47
     */
    public function getSiteName($site)
    {
        switch ($site) {
            case Site::ZEELOOL;
                $siteName = 'ZEELOOL';
                break;
            case Site::VOOGUEME;
                $siteName = 'VOOGUEME';
                break;
            case Site::NIHAO;
                $siteName = 'NIHAO';
                break;
            case Site::ZEELOOL_DE;
                $siteName = 'ZEELOOL_DE';
                break;
            case Site::ZEELOOL_JP;
                $siteName = 'ZEELOOL_JP';
                break;
            case Site::WESEEOPTICAL;
                $siteName = 'WESEEOPTICAL';
                break;
        }

        return $siteName;
    }

    /**
     * 折线图标生成
     *
     * @param        $xdata
     * @param        $ydata
     * @param string $name
     *
     * @return array
     * @author crasphb
     * @date   2021/4/14 16:47
     */
    public function getEcharts($xdata, $ydata, $name = '', $smooth = true)
    {
        if (!is_array($xdata)) {
            $xdata = explode(',', $xdata);
        }
        if (!is_array($ydata)) {
            $ydata = explode(',', $ydata);
        }

        $echart['xcolumnData'] = $xdata;
        $echart['column'] = [$name];
        $echart['columnData'] = [
            [
                'type'   => 'line',
                'data'   => $ydata,
                'name'   => $name,
                'smooth' => $smooth //平滑曲线
            ],

        ];

        return $echart;
    }

    /**
     * 获取多个图标的数据
     *
     * @param        $xdata
     * @param        $ydata
     * @param bool   $smooth
     *
     * @return array
     * @author crasphb
     * @date   2021/5/8 16:33
     */
    public function getMutilEcharts($xdata, $ydata, $smooth = true)
    {
        $echart['xColumnName'] = $xdata;
        $columnData = [];
        foreach ($ydata as $key => $val) {
            $columnData[] = [
                'type'       => 'line',
                'data'       => $val['value'],
                'name'       => $val['name'],
                'yAxisIndex' => $key,
                'smooth'     => $smooth,
            ];
        }
        $echart['columnData'] = $columnData;

        return $echart;
    }

    /**
     * 百分比格式化
     *
     * @param        $molecular
     * @param        $denominator
     * @param string $symbol
     *
     * @return int|string
     * @author crasphb
     * @date   2021/5/12 10:55
     */
    public function getDecimal($molecular, $denominator, $symbol = '%')
    {
        return $denominator ? bcmul(bcdiv($molecular, $denominator, 4), 100, 2) . $symbol : 0 . $symbol;
    }
}