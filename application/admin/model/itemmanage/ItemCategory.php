<?php

namespace app\admin\model\itemmanage;

use think\Db;
use think\Model;


class ItemCategory extends Model
{

    //制定数据库连接
    protected $connection = 'database.db_stock';
    // 表名
    protected $name = 'item_category';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [];

    /**
     * 选择上架还是下架
     * @return array
     */
    public function isPutAway()
    {
        return [1 => '上架', 0 => '下架'];
    }
    public function getLevelList()
    {
        //return config('site.level');
        return [1 => '一级分类', 2 => '二级分类', 3 => '三级分类'];
    }
    public function getAttrGroup()
    {
        return [1 => '镜框', 2 => '虚拟产品', 3 => '饰品',4=>'医用口罩',5=>'耳温枪',6=>'卫生纸'];
    }
    
    /***
     * 获取分类列表
     */
    public function getCategoryList()
    {
        $where['is_del'] = 1;
        $result = $this->where($where)->field('id,pid,name')->select();
        if (!$result) {
            $finalArr = [];
            $finalArr[0] = '无';
            return $finalArr;
        }
        $arr    = getTree($result);
        $finalArr = [];
        foreach ($arr as $k => $v) {
            $finalArr[0] = '无';
            $finalArr[$v['id']] = $v['name'];
        }
        return $finalArr;
    }
    /***
     * 根据ajax任务获取列表
     */
    public function getAjaxCategoryList()
    {
        $result = $this->field('id,name')->select();
        if (!$result) {
            return [0 => '问题不存在请添加问题'];
        }
        $arr = [];
        foreach ($result as $key => $val) {
            $arr[$val['id']] = $val['name'];
        }
        return $arr;
    }
    /***
     * 获取分类列表以及当前任务的下级任务
     * @param $level
     * @param int $pid
     * @return array|bool
     */
    public function getList($pid)
    {
        $rs = $this->where('pid', 'in', $pid)->field('id')->select();
        if (!$rs) {
            return false;
        }
        static $arr = [];
        foreach ($rs as $k => $v) {
            $arr[] = $v['id'];
            $this->getList($v['id']);
        }
        return $arr;
    }
    /***
     * 根据id获取下级分类的id
     * @param $id
     * @return array|string
     */
    public function getLowerCategory($id)
    {
        $ids = $this->getList($id);
        if ($ids) {
            $strIds = implode(',', $ids);
        } else {
            $strIds = '';
        }
        return $strIds;
    }

    /***
     * 获取所有任务列表
     */
    public function categoryList()
    {
        $where['is_del'] = 1;
        $where['is_putaway'] = 1;
        $result = $this->where($where)->field('id,pid,name')->select();
        if (!$result) {
            $finalArr = [];
            $finalArr[0] = '无';
            return $finalArr;
        }
        $arr    = getTree($result);
        $finalArr = [];
        foreach ($arr as $k => $v) {
            $finalArr[0] = '无';
            $finalArr[$v['id']] = $v['name'];
        }
        return $finalArr;
    }
    /***
     * 根据分类ID获取相关属性组信息
     */
    public function categoryPropertyInfo($categoryId, $type = 1)
    {
        //首先检查选择的分类有没有下级，如果有下级则需要重新选择分类
        $result = $this->where('pid', '=', $categoryId)->field('id,name')->find();
        if ($result) {
            return -1;
        }
        $propertyGroup = $this->where('g.id', '=', $categoryId)->where('p.status', '=', 1)->alias('g')
            ->join('item_attribute_property_group p', ' g.property_group_id = p.id')->value('p.property_id');
        if (!$propertyGroup) { //没有属性分组
            return false;
        }
        $propertyArr = explode('+', $propertyGroup);
        $itemAttrProperty = Db::name('item_attribute_property')->where('id', 'in', $propertyArr)
            ->field('id,is_required,name_cn,name_en,input_mode')->select();
        if (!$itemAttrProperty) { //没有属性项
            return false;
        }
        if ($type == 1) {
            foreach ($itemAttrProperty as $k => $v) {
                $itemAttrProperty[$k]['rows'] = "row[{$v['name_en']}][]";
                $itemAttrProperty[$k]['propertyValues'] = $this->getPropertyValue($v['id']);
            }
        } elseif ($type == 2) {
            $arr = [];
            foreach ($itemAttrProperty as $key => $val) {
                $arr[] = $val['name_en'];
            }
            return $arr;
        }
        return $itemAttrProperty;
    }

    /***
     * 获取商品属性值对应的英文的值
     * @param $id 属性值的id
     * @return array|bool
     */
    public function getPropertyValue($id)
    {
        $result = Db::name('item_attribute_property_value')->where('property_id', '=', $id)->field('id,name_value_cn')->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[$v['id']] = $v['name_value_cn'];
        }
        return $arr;
    }
    /***
     * 根据ID获取商品属性分类
     */
    public function getAttrCategoryById($id)
    {
        //首先检查选择的分类有没有下级，如果有下级则需要重新选择分类
        $result = $this->where('pid', '=', $id)->field('id,name')->find();
        if ($result) {
            return -1;
        }
        $arr = $this->where('id', '=', $id)->field('id,attribute_group_id')->find();
        if (!$arr) {
            return false;
        }
        return $arr['attribute_group_id'] ? $arr['attribute_group_id'] : false;
    }
    public function getItemCategoryList()
    {
        $where['is_del'] = 1;
        $where['is_putaway'] = 1;
//        $where['pid'] = ['>',0];
        $result = $this->where($where)->field('id,name')->select();
        if (!$result) {
            return false;
        }
        $arr = [];
        foreach ($result as $k => $v) {
            $arr[$v['id']] = $v['name'];
        }
        return $arr;
    }
    /***
     * 根据ID获取商品材质
     */
    public function getCategoryTexture($id)
    {
        $where['id'] = $id;
        $result = $this->where($where)->field('id,accessory_type,accessory_texture_value,accessory_color_value')->find();
        if (!$result) {
            return false;
        }
        $typeResult    = $result['accessory_type'];
        $textureResult = explode(',', $result['accessory_texture_value']);
        $colorResult   = explode(',', $result['accessory_color_value']);
        return ['typeResult' => $typeResult, 'textureResult' => $textureResult, 'colorResult' => $colorResult];
    }
    /***
     * 获取配饰材质（固定）
     */
}
