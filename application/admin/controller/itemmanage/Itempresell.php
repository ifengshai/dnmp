<?php

namespace app\admin\controller\itemmanage;

use think\Db;
use app\common\controller\Backend;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\admin\model\itemmanage\Item_presell_log;


/**
 * 平台SKU预售管理
 *
 * @icon fa fa-circle-o
 */
class Itempresell extends Backend
{

    /**
     * Itempresell模型对象
     * @var \app\admin\model\itemmanage\Itempresell
     */
    protected $model = null;
    protected $platformSku = null;
    protected $noNeedLogin = ['updateItemPresellStatus'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\itemmanage\ItemPlatformSku();
        $this->magentoplatform = new \app\admin\model\platformmanage\MagentoPlatform();
        $this->item = new \app\admin\model\itemmanage\Item();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 商品预售首页
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

            //默认站点
            $platform_type = input('label');
            if ($platform_type) {
                $map['platform_type'] = $platform_type;
            }
            //如果切换站点清除默认值
            $filter = json_decode($this->request->get('filter'), true);
            if ($filter['platform_type']) {
                unset($map['platform_type']);
            }
            //默认显示 开启过预售的SKU
            if (!$filter) {
                $map['presell_status'] = ['in', [1, 2]];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $skus = array_column($list, 'sku');

            $sku_stock = $this->item->where(['sku' => ['in', $skus]])->column('available_stock', 'sku');
            //查询可用库存
            foreach ($list as &$v) {
                $v['available_stock'] = $sku_stock[$v['sku']];
            }
            unset($v);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        //查询对应平台权限
        $magentoplatformarr = $this->magentoplatform->getAuthSite();
        //取第一个key为默认站点
        $site = input('site', $magentoplatformarr[0]['id']);

        $this->assignconfig('label', $site);
        $this->assign('site', $site);
        $this->assign('magentoplatformarr', $magentoplatformarr);
        return $this->view->fetch();
    }

    /***
     * 开启预售
     */
    public function openStart($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $params = $this->preExcludeFields($params);
            if ($params['presell_start_time'] >= $params['presell_end_time']) {
                $this->error('预售开始时间必须小于结束时间');
            }
            $result = false;
            Db::startTrans();
            try {
                $params['presell_residue_num'] = $row['presell_residue_num'] + $params['presell_change_num'];
                $params['presell_num'] = $row['presell_num'] + $params['presell_change_num'];
                $now_time =  date("Y-m-d H:i:s", time());
                if ($now_time >= $params['presell_end_time']) { //如果当前时间大于结束时间
                    $params['presell_status'] = 2;
                } else {
                    $params['presell_status'] = 1;
                }
                $params['presell_create_time'] = date("Y-m-d H:i:s", time());
                $result = $row->allowField(true)->save($params);
                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                //添加日志
                $data['platform_id'] = $row->id;
                $data['sku'] = $row->sku;
                $data['site'] = $row->platform_type;
                $data['presell_change_num'] = $params['presell_change_num'];
                $data['old_presell_num'] = $row->presell_num;
                $data['old_presell_residue_num'] = $row->presell_residue_num;
                $data['presell_start_time'] = $params['presell_start_time'];
                $data['presell_end_time'] = $params['presell_end_time'];
                $data['type'] = 1; //操作类型 添加
                (new Item_presell_log())->setData($data);

                $this->success();
            } else {
                $this->error();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }

    /***
     * 结束预售
     */
    public function openEnd($ids = null)
    {
        if ($this->request->isAjax()) {
            $row = $this->model->get($ids);
            if ($row['presell_status'] == 3) {
                $this->error(__('Pre-sale closure, do not repeat the closure'));
            }
            $map['id'] = $ids;
            $data['presell_status'] = 2;
            $res = $this->model->allowField(true)->isUpdate(true, $map)->save($data);
            if ($res) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error('404 Not found');
        }
    }

    /***
     * 预售历史记录
     */
    public function presell_history($ids = null)
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $ids = input('ids');
            if ($ids) {
                $map['platform_id'] = $ids;
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = (new Item_presell_log())
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = (new Item_presell_log())
                ->where($map)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig('ids', $ids);
        return $this->view->fetch();
    }

    /**
     * 每10分钟执行一次
     * 更新商品预售状态
     */
    public function updateItemPresellStatus()
    {
        $now_time =  date("Y-m-d H:i:s", time());
        //更新到已结束
        $sql = "update fa_item_platform_sku set presell_status=2 where presell_end_time < '{$now_time}'  and presell_status=1 and is_del=1";
        DB::connect('database.db_stock')->query($sql);
    }
}
