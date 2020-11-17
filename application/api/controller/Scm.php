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
     * PDA菜单
     * @var array
     * @access protected
     */
    protected $menu = [
        [
            'title'=>'配货管理',
            'menu'=>[
                ['name'=>'配货', 'link'=>'distribution/index', 'href'=>'com.nextmar.mojing.ui.distribution.OrderDistributionActivity'],
                ['name'=>'镜片分拣', 'link'=>'distribution/sorting', 'href'=>'com.nextmar.mojing.ui.sorting.OrderSortingActivity'],
                ['name'=>'配镜片', 'link'=>'distribution/withlens', 'href'=>'com.nextmar.mojing.ui.withlens.OrderWithlensActivity'],
                ['name'=>'加工', 'link'=>'distribution/machining', 'href'=>'com.nextmar.mojing.ui.machining.OrderMachiningActivity'],
                ['name'=>'成品质检', 'link'=>'distribution/quality', 'href'=>'com.nextmar.mojing.ui.quality.OrderQualityActivity'],
                ['name'=>'合单', 'link'=>'distribution/merge', 'href'=>'com.nextmar.mojing.ui.merge.OrderMergeActivity'],
                ['name'=>'合单待取', 'link'=>'distribution/waitmerge', 'href'=>'com.nextmar.mojing.ui.merge.OrderMergeCompletedActivity'],
                ['name'=>'设置', 'link'=>'distribution/audit', 'href'=>'com.nextmar.mojing.ui.setting.SettingActivity']
            ],
        ],
        [
            'title'=>'质检管理',
            'menu'=>[
                ['name'=>'物流检索', 'link'=>'warehouse/logistics_info/index', 'href'=>'com.nextmar.mojing.ui.logistics.LogisticsActivity'],
                ['name'=>'质检单', 'link'=>'warehouse/check', 'href'=>'com.nextmar.mojing.ui.qualitylist.QualityListActivity']
            ],
        ],
        [
            'title'=>'出入库管理',
            'menu'=>[
                ['name'=>'出库单', 'link'=>'warehouse/outstock', 'href'=>'com.nextmar.mojing.ui.outstock.OutStockActivity'],
                ['name'=>'入库单', 'link'=>'warehouse/instock', 'href'=>'com.nextmar.mojing.ui.instock.InStockActivity'],
                ['name'=>'待入库', 'link'=>'warehouse/prestock', 'href'=>'com.nextmar.mojing".ui.prestock.PreStockActivity'],
                ['name'=>'盘点', 'link'=>'warehouse/inventory', 'href'=>'com.nextmar.mojing.ui.inventory.InventoryActivity'],
            ],
        ],
    ];

    protected function _initialize()
    {
        parent::_initialize();

        $this->auth = Auth::instance();

        //校验Token
        $this->auth->match(['login','version']) || $this->auth->id || $this->error(__('Token invalid, please log in again'), [], 401);

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
            $data = ['token' => $user['token']];
            $this->success(__('Logged in successful'), $data,200);
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
        $pda_version = model('Config')->get(['name'=>'pda_version']);
        $pda_download = model('Config')->get(['name'=>'pda_download']);

        $data = [
            'version'=>$pda_version['value'],
            'download'=>$this->request->domain().$pda_download['value']
        ];

        $this->success('', $data,200);
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
        foreach($this->menu as $key=>$value){
            foreach($value['menu'] as $k=>$val){
                //校验菜单展示权限
                if(!$this->auth->check($val['link'])){
                    unset($value['menu'][$k]);
                }
                unset($value['menu'][$k]['link']);
            }
            if(!empty($value['menu'])){
                $list[] = $value;
            }
        }

        $this->success('', ['list' => $list],200);
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
        $code_data = array_unique(array_filter(explode(',',$code_data)));

        $_product_bar_code_item = new ProductBarCodeItem();
        $code_list = $_product_bar_code_item
            ->field('code,sku')
            ->where(['code'=>['in',$code_data]])
            ->select()
        ;
        empty($code_list) && $this->error(__('条形码不存在'), [], 403);

        $sku_data = [];
        foreach($code_list as $key=>$value){
            $sku_data[$value['sku']]['collection'][] = $value['code'];
        }

        if($platform_id){
            $_item_platform_sku = new ItemPlatformSku();
            $stock_data = $_item_platform_sku
                ->where(['sku'=>['in',array_keys($sku_data)],'platform_type'=>$platform_id])
                ->column('stock','sku')
            ;
        }

        $list = [];
        foreach($sku_data as $k=>$v){
            $list[] = [
                'sku'=>$k,
                'collection'=>$v['collection'],
                'stock'=>isset($stock_data) ? $stock_data[$k] : 0
            ];
        }

        $this->success('', ['list' => $list],200);
    }
}
