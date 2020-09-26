<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 商品条形码管理
 */
class ProductBarCode extends Backend
{

    /**
     * ProductBarCode模型对象
     * @var \app\admin\model\warehouse\ProductBarCode
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\ProductBarCode;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 管理列表
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

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 创建
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }

                    $sku = $this->request->post("sku/a");
                    if (count(array_filter($sku)) < 1) {
                        $this->error('sku不能为空！！');
                    }

                    //存在平台id 代表把当前入库单的sku分给这个平台 首先做判断 判断入库单的sku是否都有此平台对应的映射关系
                    if ($params['platform_id']) {
                        foreach (array_filter($sku) as $k => $v) {
                            $item_platform_sku = new \app\admin\model\itemmanage\ItemPlatformSku();

                            $sku_platform = $item_platform_sku->where(['sku' => $v, 'platform_type' => $params['platform_id']])->find();
                            if (!$sku_platform) {
                                $this->error('此sku：' . $v . '没有同步至此平台，请先同步后重试');
                            }
                        }
                        $params['create_person'] = session('admin.nickname');
                        $params['createtime'] = date('Y-m-d H:i:s', time());
                        $result = $this->model->allowField(true)->save($params);

                        //添加入库信息
                        if ($result !== false) {

                            $in_stock_num = $this->request->post("in_stock_num/a");
                            $sample_num = $this->request->post("sample_num/a");
                            $purchase_id = $this->request->post("purchase_id/a");
                            $data = [];
                            foreach (array_filter($sku) as $k => $v) {
                                $data[$k]['sku'] = $v;
                                $data[$k]['in_stock_num'] = $in_stock_num[$k];
                                $data[$k]['sample_num'] = $sample_num[$k];
                                $data[$k]['no_stock_num'] = $in_stock_num[$k];
                                $data[$k]['purchase_id'] = $purchase_id[$k];
                                $data[$k]['in_stock_id'] = $this->model->id;
                            }
                            //批量添加
                            $this->instockItem->allowField(true)->saveAll($data);
                        }
                    } else {
                        $params['create_person'] = session('admin.nickname');
                        $params['createtime'] = date('Y-m-d H:i:s', time());
                        $result = $this->model->allowField(true)->save($params);

                        //添加入库信息
                        if ($result !== false) {
                            //更改质检单为已创建入库单
                            $check = new \app\admin\model\warehouse\Check;
                            $check->allowField(true)->save(['is_stock' => 1], ['id' => $params['check_id']]);


                            $in_stock_num = $this->request->post("in_stock_num/a");
                            $sample_num = $this->request->post("sample_num/a");
                            $purchase_id = $this->request->post("purchase_id/a");
                            $data = [];
                            foreach (array_filter($sku) as $k => $v) {
                                $data[$k]['sku'] = $v;
                                $data[$k]['in_stock_num'] = $in_stock_num[$k];
                                $data[$k]['sample_num'] = $sample_num[$k];
                                $data[$k]['no_stock_num'] = $in_stock_num[$k];
                                $data[$k]['purchase_id'] = $purchase_id[$k];
                                $data[$k]['in_stock_id'] = $this->model->id;
                            }
                            //批量添加
                            $this->instockItem->allowField(true)->saveAll($data);
                        }
                    }


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
                    $this->success('添加成功！！', '', url('index'));
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        return $this->view->fetch();
    }

}
