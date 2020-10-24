<?php

namespace app\admin\controller\warehouse;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Loader;

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

    /**
     * ProductBarCodeItem模型对象
     * @var \app\admin\model\warehouse\ProductBarCodeItem
     */
    protected $item = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\warehouse\ProductBarCode;
        $this->item = new \app\admin\model\warehouse\ProductBarCodeItem;
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

            foreach ($list as $k=>$val){
                $list[$k]['range'] = $val['start'].'-'.$val['end'];
            }

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

                    $number = $params['number'];
                    $number > 1000 && $this->error('单次创建数量不超过 1000 条！');

                    $where['create_time'] = ['between', [date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59")]];
                    $check_end = $this->model->where($where)->order('id desc')->value('end');
                    $s_num = isset($check_end) ? $check_end : 0;
                    $e_num = $s_num + $number;
                    $e_num > 999999 && $this->error('当天创建数量不能超过 999999 条！');

                    $params['create_person'] = session('admin.nickname');
                    $params['create_time'] = date('Y-m-d H:i:s');
                    $params['start'] = sprintf("%09d", $s_num+1);
                    $params['end'] = sprintf("%09d", $e_num);
                    $result = $this->model->allowField(true)->save($params);
                    $barcode_id = $this->model->id;

                    $data = [];
                    for ($i = 1; $i <= $number; $i++) {
                        $code = substr(date('Ymd'), 2).sprintf("%09d", $s_num+$i);
                        $data[] = ['code'=>$code,'barcode_id'=>$barcode_id];
                    }
                    $this->item->allowField(true)->saveAll($data);

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

    /**
     * 打印
     */
    public function print_label($ids = null,$do_type = null)
    {
        //批量打印
        if(1 == $do_type){
            $where['barcode_id'] = ['in',$ids];
        }else{
            //检测打印状态
            $check_status = $this->model->where(['id' => $ids])->value('status');
            1 == $check_status && $this->error('请勿重复打印！');

            $where['barcode_id'] = $ids;

            //标记打印
            $this->model->allowField(true)->save(['status' => 1], ['id' => $ids]);
        }

        ob_start();
        $file_header =
<<<EOF
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
body{ margin:0; padding:0}
.single_box{margin:0 auto;}
table.addpro {clear: both;table-layout: fixed; margin-top:6px; border-top:1px solid #000;border-left:1px solid #000; font-size:12px;}
table.addpro .title {background: none repeat scroll 0 0 #f5f5f5; }
table.addpro .title  td {border-collapse: collapse;color: #000;text-align: center; font-weight:normal; }
table.addpro tbody td {word-break: break-all; text-align: center;border-bottom:1px solid #000;border-right:1px solid #000;}
table.addpro.re tbody td{ position:relative}
</style>
EOF;

        $list = $this->item
            ->where($where)
            ->order('id', 'asc')
            ->field('code')
            ->select();
        $list = collection($list)->toArray();

        $file_content = '';
        foreach ($list as $value) {
            //检测文件夹
            $dir = ROOT_PATH . "public" . DS . "uploads" . DS . "product" . DS . "bar_code";
            !file_exists($dir) && mkdir($dir, 0777, true);

            //生成条形码
            $fileName = ROOT_PATH . "public" . DS . "uploads" . DS . "product" . DS . "bar_code" . DS . $value['code'] .".png";
            $this->generate_barcode($value['code'], $fileName);

            //拼接条形码
            $img_url = "/uploads/product/bar_code/{$value['code']}.png";
            $file_content .= "<div style='display:list-item;margin: 0mm auto;padding-top:4mm;padding-right:2mm;text-align:center;'>
            <img src='" . $img_url . "' style='width:36mm'></div>";
        }
        echo $file_header . $file_content;
    }

    /**
     * 生成条形码
     */
    protected function generate_barcode($text, $fileName)
    {
        // $text = '1007000000030';
        // 引用barcode文件夹对应的类
        Loader::import('BCode.BCGFontFile', EXTEND_PATH);
        //Loader::import('BCode.BCGColor',EXTEND_PATH);
        Loader::import('BCode.BCGDrawing', EXTEND_PATH);
        // 条形码的编码格式
        // Loader::import('BCode.BCGcode39',EXTEND_PATH,'.barcode.php');
        Loader::import('BCode.BCGcode128', EXTEND_PATH, '.barcode.php');

        // $code = '';
        // 加载字体大小
        $font = new \BCGFontFile(EXTEND_PATH . '/BCode/font/Arial.ttf', 20);
        //颜色条形码
        $color_black = new \BCGColor(0, 0, 0);
        $color_white = new \BCGColor(255, 255, 255);
        $drawException = null;
        try {
            // $code = new \BCGcode39();
            $code = new \BCGcode128();
            $code->setScale(2);
            $code->setThickness(60); // 条形码的厚度
            $code->setForegroundColor($color_black); // 条形码颜色
            $code->setBackgroundColor($color_white); // 空白间隙颜色
            $code->setFont($font); //设置字体
            // $code->setOffsetX(10); //设置字体
            $code->parse($text); // 条形码需要的数据内容
        } catch (\Exception $exception) {
            $drawException = $exception;
        }
        //根据以上条件绘制条形码
        $drawing = new \BCGDrawing('', $color_white);
        if ($drawException) {
            $drawing->drawException($drawException);
        } else {
            $drawing->setBarcode($code);
            if ($fileName) {
                // echo 'setFilename<br>';
                $drawing->setFilename($fileName);
            }
            $drawing->draw();
        }
        // 生成PNG格式的图片
        header('Content-Type: image/png');
        // header('Content-Disposition:attachment; filename="barcode.png"'); //自动下载
        $drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);
    }

    /**
     * 条形码绑定关系列表
     */
    public function binding()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $map = [];
            $filter = json_decode($this->request->get('filter'), true);
            $_purchase_order = new \app\admin\model\purchase\PurchaseOrder;
            //检测采购单号
            if ($filter['purchase_number']) {
                $purchase_ids = $_purchase_order->where(['purchase_number'=>['like', '%' . $filter['purchase_number'] . '%']])->column('id');
                $map['purchase_id'] = ['in',$purchase_ids];
                unset($filter['purchase_number']);
                $this->request->get(['filter' => json_encode($filter)]);
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->item
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->count();

            $list = $this->item
                ->where($where)
                ->where($map)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();

            //获取采购单数据
            $purchase_list = $_purchase_order
                ->where(['purchase_status'=>[['=',6], ['=',7], 'or']])
                ->column('purchase_number','id');

            foreach ($list as $k=>$val){
                $list[$k]['purchase_number'] = $purchase_list[$val['purchase_id']];
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

}
