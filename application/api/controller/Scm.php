<?php

namespace app\api\controller;

use app\admin\model\InterfaceTimeLog;
use app\common\controller\Api;
use app\admin\library\Auth;
use app\admin\model\warehouse\ProductBarCodeItem;
use app\admin\model\itemmanage\ItemPlatformSku;

/**
 * 供应链基础接口类
 * @author lzh
 * @since 2020-10-20
 */
class Scm extends Api
{
    /**
     * 无需登录验证
     * @var array|string
     * @access protected
     */
    protected $noNeedLogin = '*';

    /**
     * 无需权限验证
     * @var array|string
     * @access protected
     */
    protected $noNeedRight = '*';

    /**
     * 权限控制类
     * @var Auth
     */
    protected $auth = null;

    /**
     * 配货接口类
     * @var object
     * @access protected
     */
    protected $_distribution = null;

    /**
     * PDA菜单
     * @var array
     * @access protected
     */
    protected $menu = [
        [
            'title' => '配货管理',
            'menu' => [
                [
                    'name' => '配货',
                    'link' => 'pda/product',
                    'icon' => '/assets/img/distribution/peihuo.png',
                    'href' => 'com.nextmar.mojing.ui.distribution.OrderDistributionActivity'
                ],
                [
                    'name' => '镜片分拣',
                    'link' => 'pda/sorting',
                    'icon' => '/assets/img/distribution/jingpianfenjian.png',
                    'href' => 'com.nextmar.mojing.ui.sorting.OrderSortingActivity'
                ],
                [
                    'name' => '配镜片',
                    'link' => 'pda/withlens',
                    'icon' => '/assets/img/distribution/peijingpian.png',
                    'href' => 'com.nextmar.mojing.ui.order.OrderWithlensActivity'
                ],
                [
                    'name' => '加工',
                    'link' => 'pda/machining',
                    'icon' => '/assets/img/distribution/jiagong.png',
                    'href' => 'com.nextmar.mojing.ui.order.OrderMachiningActivity'
                ],
                [
                    'name' => '印LOGO',
                    'link' => 'pda/logo',
                    'icon' => '/assets/img/distribution/logo.png',
                    'href' => 'com.nextmar.mojing.ui.order.OrderLogoActivity'
                ],
                [
                    'name' => '成品质检',
                    'link' => 'pda/quality',
                    'icon' => '/assets/img/distribution/zhijian.png',
                    'href' => 'com.nextmar.mojing.ui.order.OrderQualityActivity'
                ],
                [
                    'name' => '合单',
                    'link' => 'pda/merge',
                    'icon' => '/assets/img/distribution/hedan.png',
                    'href' => 'com.nextmar.mojing.ui.merge.OrderMergeActivity'
                ],
                [
                    'name' => '合单待取',
                    'link' => 'pda/waitmerge',
                    'icon' => '/assets/img/distribution/hedandaiqu.png',
                    'href' => 'com.nextmar.mojing.ui.merge.OrderMergeCompletedActivity'
                ],
                [
                    'name' => '库内调拨',
                    'link' => 'warehouse/allocation',
                    'icon' => '/assets/img/distribution/kuneidiaobo.png',
                    'href' => 'com.nextmar.mojing.ui.allocation.AllocationListActivity'
                ]
            ],
        ],
        [
            'title' => '质检管理',
            'menu' => [
                [
                    'name' => '物流检索',
                    'link' => 'warehouse/logistics_info/index',
                    'icon' => '/assets/img/distribution/wuliujiansuo.png',
                    'href' => 'com.nextmar.mojing.ui.logistics.LogisticsActivity'
                ],
                [
                    'name' => '质检单',
                    'link' => 'warehouse/check',
                    'icon' => '/assets/img/distribution/zhijiandan.png',
                    'href' => 'com.nextmar.mojing.ui.qualitylist.QualityListActivity'
                ]
            ],
        ],
        [
            'title' => '出入库管理',
            'menu' => [
                [
                    'name' => '出库单',
                    'link' => 'warehouse/outstock',
                    'icon' => '/assets/img/distribution/chukudan.png',
                    'href' => 'com.nextmar.mojing.ui.outstock.OutStockActivity'
                ],
                [
                    'name' => '入库单',
                    'link' => 'warehouse/instock',
                    'icon' => '/assets/img/distribution/rukudan.png',
                    'href' => 'com.nextmar.mojing.ui.instock.InStockActivity'
                ],
                [
                    'name' => '待入库',
                    'link' => 'warehouse/prestock',
                    'icon' => '/assets/img/distribution/dairuku.png',
                    'href' => 'com.nextmar.mojing.ui.prestock.PreStockActivity'
                ],
                [
                    'name' => '盘点',
                    'link' => 'warehouse/inventory',
                    'icon' => '/assets/img/distribution/pandian.png',
                    'href' => 'com.nextmar.mojing.ui.inventory.InventoryActivity'
                ],
            ],
        ],
    ];

    protected function _initialize()
    {
        parent::_initialize();

        $this->auth = Auth::instance();

        //校验api_key
        // $this->auth->match(['login', 'version', 'order_examine', 'record_run_time'])
        // ||
        // $this->auth->id || $this->error(__('Api key invalid, please log in again'), [], 401);
        //
        // //校验请求类型
        // $this->request->isPost() || $this->error(__('Request method must be post'), [], 402);
    }

    /**
     * 登录
     *
     * @参数 string account  账号
     * @参数 string password  密码
     * @author lzh
     * @return mixed
     */
    public function login()
    {
        $account = $this->request->request('account');
        $password = $this->request->request('password');
        empty($account) && $this->error(__('Username can not be empty'), [], 403);
        empty($password) && $this->error(__('Password can not be empty'), [], 403);

        if ($this->auth->login($account, $password)) {
            $user = $this->auth->getUserinfo();
            $data = ['api_key' => $user['api_key']];
            $this->success(__('Logged in successful'), $data, 200);
        } else {
            $this->error($this->auth->getError(), [], 404);
        }
    }

    /**
     * PDA版本
     *
     * @author lzh
     * @return mixed
     */
    public function version()
    {
        $pda_version = model('Config')->get(['name' => 'pda_version']);
        $pda_download = model('Config')->get(['name' => 'pda_download']);
        $pda_md5 = model('Config')->get(['name' => 'pda_md5']);

        $data = [
            'version' => $pda_version['value'],
            'download' => $this->request->domain() . $pda_download['value'],
            'pda_md5' => $pda_md5
        ];

        $this->success('', $data, 200);
    }

    /**
     * 首页
     *
     * @author lzh
     * @return mixed
     */
    public function index()
    {
        //重新组合菜单
        $list = [];
        foreach ($this->menu as $key => $value) {
            foreach ($value['menu'] as $k => $val) {
                //校验菜单展示权限
                if (!$this->auth->check($val['link'])) {
                    //当前用户无此菜单权限
                    unset($value['menu'][$k]);
                } else {
                    //图片链接
                    $value['menu'][$k]['icon'] = $val['icon'] ? $this->request->domain() . $val['icon'] : '';

                    //镜片未分拣数量
                    if ('镜片分拣' == $val['name']) {
                        $this->_distribution = new ScmDistribution();
                        $value['menu'][$k]['count'] = $this->_distribution->no_sorting();
                    }

                    //移除权限链接
                    unset($value['menu'][$k]['link']);
                }
            }
            if (!empty($value['menu'])) {
                $value['menu'] = array_values($value['menu']);
                $list[] = $value;
            }
        }

        $this->success('', ['list' => $list], 200);
    }

    /**
     * 根据条形码获取商品信息
     *
     * @参数 string code_data  条形码集合（以英文逗号分隔）
     * @参数 int platform_id  平台ID
     * @author lzh
     * @return mixed
     */
    public function scan_code_product()
    {
        $platform_id = $this->request->request('platform_id');
        $code_data = $this->request->request('code_data');
        empty($code_data) && $this->error(__('条形码集合不能为空'), [], 403);
        $code_data = array_unique(array_filter(explode(',', $code_data)));

        $_product_bar_code_item = new ProductBarCodeItem();
        $code_list = $_product_bar_code_item
            ->field('code,sku')
            ->where(['code' => ['in', $code_data]])
            ->select();
        empty($code_list) && $this->error(__('条形码不存在'), [], 403);

        $sku_data = [];
        foreach ($code_list as $key => $value) {
            $sku_data[$value['sku']]['collection'][] = $value['code'];
        }

        if ($platform_id) {
            $_item_platform_sku = new ItemPlatformSku();
            $stock_data = $_item_platform_sku
                ->where(['sku' => ['in', array_keys($sku_data)], 'platform_type' => $platform_id])
                ->column('stock', 'sku');
        }

        $list = [];
        foreach ($sku_data as $k => $v) {
            $list[] = [
                'sku' => $k,
                'collection' => $v['collection'],
                'stock' => isset($stock_data) ? $stock_data[$k] : 0
            ];
        }

        $this->success('', ['list' => $list], 200);
    }

    /**
     * 记录接口访问时间
     *
     * @参数 json time_data  时间数据集合
     * @author lzh
     * @return mixed
     */
    public function record_run_time()
    {
        $time_data = $this->request->request("time_data");
        $time_data = json_decode(htmlspecialchars_decode($time_data), true);
        empty($time_data) && $this->error(__('时间集合不能为空'), [], 400);
        $time_data = array_filter($time_data);

        $save_data = [];
        foreach ($time_data as $key => $value) {
            $start_time = $value['start_time'];
            $end_time = $value['end_time'];
            $difference = $value['difference'];
            $function = $value['function'];
            empty($start_time) && $this->error(__("第{$key}列开始时间不能为空"), [], 402);
            empty($end_time) && $this->error(__("第{$key}列结束时间不能为空"), [], 403);
            empty($difference) && $this->error(__("第{$key}列耗费时间差不能为空"), [], 404);
            empty($function) && $this->error(__("第{$key}列方法名不能为空"), [], 405);

            $save_data[] = [
                'type' => 2,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'difference' => $difference * 0.001,
                'function' => $function
            ];
        }

        empty($save_data) && $this->error(__('数据获取失败'), [], 406);

        $_interface_time_log = new InterfaceTimeLog();
        $_interface_time_log->record($save_data);

        $this->success('记录成功', [], 200);
    }

    /**
     * 操作成功返回的数据（重写）
     * @param string $msg 提示信息
     * @param mixed $data 要返回的数据
     * @param int $code 错误码，默认为1
     * @param string $type 输出类型
     * @param array $header 发送的 Header 信息
     */
    protected function success($msg = '', $data = null, $code = 1, $type = null, array $header = [])
    {
        $start_time = $GLOBALS['code_run_start_time'];
        $end_time = microtime(true);
        $difference = round($start_time - $end_time, 3);
        $_interface_time_log = new InterfaceTimeLog();
        $_interface_time_log
            ->record([
                [
                    'type' => 1,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'difference' => $difference,
                    'function' => $this->request->url()
                ]
            ]);
        $this->result($msg, $data, $code, $type, $header);
    }

}
