<?php

namespace app\api\controller;

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
                    'link' => 'distribution/index',
                    'icon' => '/assets/img/distribution/peihuo.png',
                    'href' => 'com.nextmar.mojing.ui.distribution.OrderDistributionActivity'
                ],
                [
                    'name' => '镜片分拣',
                    'link' => 'distribution/sorting',
                    'icon' => '/assets/img/distribution/jingpianfenjian.png',
                    'href' => 'com.nextmar.mojing.ui.sorting.OrderSortingActivity'
                ],
                [
                    'name' => '配镜片',
                    'link' => 'distribution/withlens',
                    'icon' => '/assets/img/distribution/peijingpian.png',
                    'href' => 'com.nextmar.mojing.ui.order.OrderWithlensActivity'
                ],
                [
                    'name' => '加工',
                    'link' => 'distribution/machining',
                    'icon' => '/assets/img/distribution/jiagong.png',
                    'href' => 'com.nextmar.mojing.ui.order.OrderMachiningActivity'
                ],
                [
                    'name' => '印LOGO',
                    'link' => 'distribution/logo',
                    'icon' => '/assets/img/distribution/logo.png',
                    'href' => 'com.nextmar.mojing.ui.order.OrderLogoActivity'
                ],
                [
                    'name' => '成品质检',
                    'link' => 'distribution/quality',
                    'icon' => '/assets/img/distribution/zhijian.png',
                    'href' => 'com.nextmar.mojing.ui.order.OrderQualityActivity'
                ],
                [
                    'name' => '合单',
                    'link' => 'distribution/merge',
                    'icon' => '/assets/img/distribution/hedan.png',
                    'href' => 'com.nextmar.mojing.ui.merge.OrderMergeActivity'
                ],
                [
                    'name' => '合单待取',
                    'link' => 'distribution/waitmerge',
                    'icon' => '/assets/img/distribution/hedandaiqu.png',
                    'href' => 'com.nextmar.mojing.ui.merge.OrderMergeCompletedActivity'],
                [
                    'name' => '设置',
                    'link' => 'distribution/audit',
                    'icon' => '',
                    'href' => 'com.nextmar.mojing.ui.setting.SettingActivity'
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
        $this->auth->match(['login', 'version', 'order_examine']) || $this->auth->id || $this->error(__('Api key invalid, please log in again'), [], 401);

        //校验请求类型
        $this->request->isPost() || $this->error(__('Request method must be post'), [], 402);
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

        $data = [
            'version' => $pda_version['value'],
            'download' => $this->request->domain() . $pda_download['value']
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
                    unset($value['menu'][$k]);
                }else{
                    unset($value['menu'][$k]['link']);

                    //图片链接
                    $value['menu'][$k]['icon'] = $val['icon'] ? $this->request->domain().$val['icon'] : '';

                    //镜片未分拣数量
                    if ('镜片分拣' == $val['name']) {
                        $this->_distribution = new ScmDistribution();
                        $value['menu'][$k]['count'] = $this->_distribution->no_sorting();
                    }
                }
            }
            if (!empty($value['menu'])) {
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
}
