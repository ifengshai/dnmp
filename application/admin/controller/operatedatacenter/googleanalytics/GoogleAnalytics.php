<?php
/**
 * GoogleAnalytics.php
 * @author huangbinbin
 * @date   2021/8/10 17:11
 */

namespace app\admin\controller\operatedatacenter\googleanalytics;


use app\common\controller\Backend;
use app\enum\Site;
use think\Db;

class GoogleAnalytics  extends Backend
{
    /**
     * @throws \Google_Exception
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $site =  1;
        if ($this->request->isAjax()) {
            $end = date('Y-m-d');
            $filter = json_decode($this->request->get('filter'), true);
            $start = date('Y-m-d', strtotime('-6 day'));
            if($filter['site']){
                $site = $filter['site'];
            }
            if($filter['time']){
                $createat = explode(' ', $filter['time']);
                $start = date('Y-m-d', strtotime($createat[0]));
                $end = date('Y-m-d', strtotime($createat[3]));
            }
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $this->request->get(['filter' => json_encode($filter)]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $googleAnalytics = new \app\service\google\GoogleAnalytics($site);
            $getGaResult = $googleAnalytics->getGaResult($start,$end);
            $orders = $this->getOrder($site,$start . ' 00:00:00',$end. ' 23:59:59');
            $quotes = $this->getCart($site,$start . ' 00:00:00',$end. ' 23:59:59');

            $skus = array_unique(array_merge(array_keys($orders),array_keys($quotes)));
            $magento_list = [];
            foreach ($skus as $key => $sku) {

                $magento_list[$key]['sku_quote_counter'] = $quotes[$sku] ?? 0;
                $magento_list[$key]['sku_order_counter'] = $orders[$sku] ?? 0;

                foreach ($getGaResult as $ga_key => $ga_value) {
                    if ((strpos(strtolower($ga_value['pagePath']), strtolower($sku)) !== false   && strpos(strtolower($ga_value['pagePath']), 'goods-detail') !== false)) {
                        // echo '包含该SKU';
                        $magento_list[$key]['pagePath'][] = $ga_value['pagePath'];
                        $magento_list[$key]['pageviews'] += (int)$ga_value['pageviews'];
                        $magento_list[$key]['uniquePageviews'] += (int)$ga_value['uniquePageviews'];
                        //由于获取数据是降序排序，取uniquePageviews最大值为有效值
                        if ($magento_list[$key]['uniquePageviews'] == $ga_value['uniquePageviews']) {
                            $magento_list[$key]['avgTimeOnPage'] = $ga_value['avgTimeOnPage'];
                            $magento_list[$key]['entranceRate'] = $ga_value['entranceRate'];
                            $magento_list[$key]['exitRate'] = $ga_value['exitRate'];
                            $magento_list[$key]['pageValue'] = $ga_value['pageValue'];
                        }
                        $magento_list[$key]['entrances'] += $ga_value['entrances'];
                        $magento_list[$key]['exits'] += $ga_value['exits'];
                    }
                }
                $magento_list[$key]['sku'] = $sku;
                $magento_list[$key]['entrances'] = sprintf('%.2f',$magento_list[$key]['entrances']).'%';
                $magento_list[$key]['exits'] = sprintf('%.2f',$magento_list[$key]['exits']).'%';
                $magento_list[$key]['pageValue'] = sprintf('%.2f',$magento_list[$key]['pageValue']);
                $magento_list[$key]['time'] = date('Y-m-d H:i:s');
                $magento_list[$key]['site'] = rand(1,20);
            }

            foreach ($magento_list as $key => $magento_value) {
                if ($magento_value['sku_quote_counter'] && $magento_value['uniquePageviews']) {
                    $magento_list[$key]['quote_uniquePageviews_percent'] = round($magento_value['sku_quote_counter'] / $magento_value['uniquePageviews'] * 100, 2) . '%';
                }

                if ($magento_value['sku_quote_counter'] && $magento_value['sku_order_counter']) {
                    $magento_list[$key]['order_quote_percent'] = round($magento_value['sku_order_counter'] / $magento_value['sku_quote_counter'] * 100, 2) . '%';
                }
                if ($magento_value['uniquePageviews'] && $magento_value['sku_order_counter']) {
                    $magento_list[$key]['order_uniquePageviews_percent'] = round($magento_value['sku_order_counter'] / $magento_value['uniquePageviews'] * 100, 2) . '%';
                }
            }
            $result = array("total" => count($magento_list), "rows" => array_values($magento_list));

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author huangbinbin
     * @date   2021/8/11 18:10
     */
    public function getOrder($site,$start,$end)
    {
        $model = Db::connect('database.db_mojing_order');

        $orders = $model->table('fa_order_item_option')
            ->alias('a')
            ->join('fa_order b','b.id=a.order_id')
            ->field('round(sum(a.qty),0) as qtycount,sku')
            ->where('b.site',$site)
            ->where('b.status','in',['complete','processing','delivered','delivery'])
            ->where('b.created_at','between',[strtotime($start) - 8*3600,strtotime($end) - 8*3600])
            ->group('a.sku')
            ->select();
        if($orders) {
            return array_column($orders,'qtycount','sku');
        }
        return [];
    }

    /**
     * @param $site
     * @param $start
     * @param $end
     *
     * @return mixed
     * @author huangbinbin
     * @date   2021/8/11 18:27
     */
    public function getCart($site,$start,$end)
    {
        switch ($site) {
            case Site::ZEELOOL:
                $model = Db::connect('database.db_zeelool_online');
                break;
            case Site::VOOGUEME:
                $model = Db::connect('database.db_voogueme_online');
                break;
            case Site::NIHAO:
                $model = Db::connect('database.db_nihao_online');
                break;
            case Site::ZEELOOL_DE:
                $model = Db::connect('database.db_zeelool_de_online');
                break;
            case Site::ZEELOOL_JP:
                $model = Db::connect('database.db_zeelool_jp_online');
                break;
            case Site::WESEEOPTICAL:
                $model = Db::connect('database.db_weseeoptical_online');
                break;
            case Site::ZEELOOL_FR:
                $model = Db::connect('database.db_zeelool_fr_online');
                break;
        }
        if($site == 3 || $site == 5) {
            $quoteSKuCount = $model->table('carts');

        }else{
            $quoteSKuCount = $model->table('sales_flat_quote_item');
        }
        $quotes = $quoteSKuCount->field('round(sum(qty),0) as qtycount,sku')
            ->where('created_at','between',[$start,$end])
            ->group('sku')
            ->select();
        if($quotes) {
            return array_column($quotes,'qtycount','sku');
        }
        return [];

    }
}