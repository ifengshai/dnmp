<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;

use BadMethodCallException;
use fast\Excel;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\db\Query;
use think\exception\ValidateException;
use think\model\Collection as ModelCollection;
use think\model\Relation;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;
use think\model\relation\HasManyThrough;
use think\model\relation\HasOne;
use think\model\relation\MorphMany;
use think\model\relation\MorphOne;
use think\model\relation\MorphTo;

/**
 * Class Model
 * @package think
 * @mixin Query
 */
abstract class Model implements \JsonSerializable, \ArrayAccess
{
    // 数据库查询对象池
    protected static $links = [];
    // 数据库配置
    protected $connection = [];
    // 父关联模型对象
    protected $parent;
    // 数据库查询对象
    protected $query;
    // 当前模型名称
    protected $name;
    // 数据表名称
    protected $table;
    // 当前类名称
    protected $class;
    // 回调事件
    private static $event = [];
    // 错误信息
    protected $error;
    // 字段验证规则
    protected $validate;
    // 数据表主键 复合主键使用数组定义 不设置则自动获取
    protected $pk;
    // 数据表字段信息 留空则自动获取
    protected $field = [];
    // 数据排除字段
    protected $except = [];
    // 数据废弃字段
    protected $disuse = [];
    // 只读字段
    protected $readonly = [];
    // 显示属性
    protected $visible = [];
    // 隐藏属性
    protected $hidden = [];
    // 追加属性
    protected $append = [];
    // 数据信息
    protected $data = [];
    // 原始数据
    protected $origin = [];
    // 关联模型
    protected $relation = [];

    // 保存自动完成列表
    protected $auto = [];
    // 新增自动完成列表
    protected $insert = [];
    // 更新自动完成列表
    protected $update = [];
    // 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型
    protected $autoWriteTimestamp;
    // 创建时间字段
    protected $createTime = 'create_time';
    // 更新时间字段
    protected $updateTime = 'update_time';
    // 时间字段取出后的默认时间格式
    protected $dateFormat;
    // 字段类型或者格式转换
    protected $type = [];
    // 是否为更新数据
    protected $isUpdate = false;
    // 是否使用Replace
    protected $replace = false;
    // 是否强制更新所有数据
    protected $force = false;
    // 更新条件
    protected $updateWhere;
    // 验证失败是否抛出异常
    protected $failException = false;
    // 全局查询范围
    protected $useGlobalScope = true;
    // 是否采用批量验证
    protected $batchValidate = false;
    // 查询数据集对象
    protected $resultSetType;
    // 关联自动写入
    protected $relationWrite;

    /**
     * 初始化过的模型.
     *
     * @var array
     */
    protected static $initialized = [

    ];

    /**
     * 是否从主库读取（主从分布式有效）
     * @var array
     */
    protected static $readMaster;

    /**
     * 构造方法
     * @access public
     * @param array|object $data 数据
     */
    public function __construct($data = [])
    {
        if (is_object($data)) {
            $this->data = get_object_vars($data);
        } else {
            $this->data = $data;
        }

        if ($this->disuse) {
            // 废弃字段
            foreach ((array) $this->disuse as $key) {
                if (array_key_exists($key, $this->data)) {
                    unset($this->data[$key]);
                }
            }
        }

        // 记录原始数据
        $this->origin = $this->data;

        // 当前类名
        $this->class = get_called_class();

        if (empty($this->name)) {
            // 当前模型名
            $name       = str_replace('\\', '/', $this->class);
            $this->name = basename($name);
            if (Config::get('class_suffix')) {
                $suffix     = basename(dirname($name));
                $this->name = substr($this->name, 0, -strlen($suffix));
            }
        }

        if (is_null($this->autoWriteTimestamp)) {
            // 自动写入时间戳
            $this->autoWriteTimestamp = $this->getQuery()->getConfig('auto_timestamp');
        }

        if (is_null($this->dateFormat)) {
            // 设置时间戳格式
            $this->dateFormat = $this->getQuery()->getConfig('datetime_format');
        }

        if (is_null($this->resultSetType)) {
            $this->resultSetType = $this->getQuery()->getConfig('resultset_type');
        }
        // 执行初始化操作
        $this->initialize();

        //修改时区
        $this->query("set time_zone='+8:00'");
    }

    /**
     * 是否从主库读取数据（主从分布有效）
     * @access public
     * @param  bool     $all 是否所有模型生效
     * @return $this
     */
    public function readMaster($all = false)
    {
        $model = $all ? '*' : $this->class;

        static::$readMaster[$model] = true;
        return $this;
    }

    /**
     * 创建模型的查询对象
     * @access protected
     * @return Query
     */
    protected function buildQuery()
    {
        // 合并数据库配置
        if (!empty($this->connection)) {
            if (is_array($this->connection)) {
                $connection = array_merge(Config::get('database'), $this->connection);
            } else {
                $connection = $this->connection;
            }
        } else {
            $connection = [];
        }
        
        $con = Db::connect($connection);
        // 设置当前模型 确保查询返回模型对象
        $queryClass = $this->query ?: $con->getConfig('query');
        $query      = new $queryClass($con, $this);

        if (isset(static::$readMaster['*']) || isset(static::$readMaster[$this->class])) {
            $query->master(true);
        }

        // 设置当前数据表和模型名
        if (!empty($this->table)) {
            $query->setTable($this->table);
        } else {
            $query->name($this->name);
        }

        if (!empty($this->pk)) {
            $query->pk($this->pk);
        }
        

        return $query;
    }

    /**
     * 创建新的模型实例
     * @access public
     * @param  array|object $data 数据
     * @param  bool         $isUpdate 是否为更新
     * @param  mixed        $where 更新条件
     * @return Model
     */
    public function newInstance($data = [], $isUpdate = false, $where = null)
    {
        return (new static($data))->isUpdate($isUpdate, $where);
    }

    /**
     * 获取当前模型的查询对象
     * @access public
     * @param bool      $buildNewQuery  创建新的查询对象
     * @return Query
     */
    public function getQuery($buildNewQuery = false)
    {
        if ($buildNewQuery) {
            return $this->buildQuery();
        } elseif (!isset(self::$links[$this->class])) {
            // 创建模型查询对象
            self::$links[$this->class] = $this->buildQuery();
        }

        return self::$links[$this->class];
    }

    /**
     * 获取当前模型的数据库查询对象
     * @access public
     * @param bool $useBaseQuery 是否调用全局查询范围
     * @param bool $buildNewQuery 创建新的查询对象
     * @return Query
     */
    public function db($useBaseQuery = true, $buildNewQuery = true)
    {
        $query = $this->getQuery($buildNewQuery);

        // 全局作用域
        if ($useBaseQuery && method_exists($this, 'base')) {
            call_user_func_array([$this, 'base'], [ & $query]);
        }

        // 返回当前模型的数据库查询对象
        return $query;
    }

    /**
     *  初始化模型
     * @access protected
     * @return void
     */
    protected function initialize()
    {
        $class = get_class($this);
        if (!isset(static::$initialized[$class])) {
            static::$initialized[$class] = true;
            static::init();
        }
    }

    /**
     * 初始化处理
     * @access protected
     * @return void
     */
    protected static function init()
    {
    }

    /**
     * 设置父关联对象
     * @access public
     * @param Model $model  模型对象
     * @return $this
     */
    public function setParent($model)
    {
        $this->parent = $model;
        return $this;
    }

    /**
     * 获取父关联对象
     * @access public
     * @return Model
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * 设置数据对象值
     * @access public
     * @param mixed $data  数据或者属性名
     * @param mixed $value 值
     * @return $this
     */
    public function data($data, $value = null)
    {
        if (is_string($data)) {
            $this->data[$data] = $value;
        } else {
            // 清空数据
            $this->data = [];
            if (is_object($data)) {
                $data = get_object_vars($data);
            }
            if (true === $value) {
                // 数据对象赋值
                foreach ($data as $key => $value) {
                    $this->setAttr($key, $value, $data);
                }
            } else {
                $this->data = $data;
            }
        }
        return $this;
    }

    /**
     * 获取对象原始数据 如果不存在指定字段返回false
     * @access public
     * @param string $name 字段名 留空获取全部
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getData($name = null)
    {
        if (is_null($name)) {
            return $this->data;
        } elseif (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        } elseif (array_key_exists($name, $this->relation)) {
            return $this->relation[$name];
        } else {
            throw new InvalidArgumentException('property not exists:' . $this->class . '->' . $name);
        }
    }

    /**
     * 是否需要自动写入时间字段
     * @access public
     * @param bool $auto
     * @return $this
     */
    public function isAutoWriteTimestamp($auto)
    {
        $this->autoWriteTimestamp = $auto;
        return $this;
    }

    /**
     * 更新是否强制写入数据 而不做比较
     * @access public
     * @param bool $force
     * @return $this
     */
    public function force($force = true)
    {
        $this->force = $force;
        return $this;
    }

    /**
     * 修改器 设置数据对象值
     * @access public
     * @param string $name  属性名
     * @param mixed  $value 属性值
     * @param array  $data  数据
     * @return $this
     */
    public function setAttr($name, $value, $data = [])
    {
        if (is_null($value) && $this->autoWriteTimestamp && in_array($name, [$this->createTime, $this->updateTime])) {
            // 自动写入的时间戳字段
            $value = $this->autoWriteTimestamp($name);
        } else {
            // 检测修改器
            $method = 'set' . Loader::parseName($name, 1) . 'Attr';
            if (method_exists($this, $method)) {
                $value = $this->$method($value, array_merge($this->data, $data), $this->relation);
            } elseif (isset($this->type[$name])) {
                // 类型转换
                $value = $this->writeTransform($value, $this->type[$name]);
            }
        }

        // 设置数据对象属性
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * 获取当前模型的关联模型数据
     * @access public
     * @param string $name 关联方法名
     * @return mixed
     */
    public function getRelation($name = null)
    {
        if (is_null($name)) {
            return $this->relation;
        } elseif (array_key_exists($name, $this->relation)) {
            return $this->relation[$name];
        } else {
            return;
        }
    }

    /**
     * 设置关联数据对象值
     * @access public
     * @param string $name  属性名
     * @param mixed  $value 属性值
     * @return $this
     */
    public function setRelation($name, $value)
    {
        $this->relation[$name] = $value;
        return $this;
    }

    /**
     * 自动写入时间戳
     * @access public
     * @param string $name 时间戳字段
     * @return mixed
     */
    protected function autoWriteTimestamp($name)
    {
        if (isset($this->type[$name])) {
            $type = $this->type[$name];
            if (strpos($type, ':')) {
                list($type, $param) = explode(':', $type, 2);
            }
            switch ($type) {
                case 'datetime':
                case 'date':
                    $format = !empty($param) ? $param : $this->dateFormat;
                    $value  = $this->formatDateTime(time(), $format);
                    break;
                case 'timestamp':
                case 'integer':
                default:
                    $value = time();
                    break;
            }
        } elseif (is_string($this->autoWriteTimestamp) && in_array(strtolower($this->autoWriteTimestamp), [
            'datetime',
            'date',
            'timestamp',
        ])
        ) {
            $value = $this->formatDateTime(time(), $this->dateFormat);
        } else {
            $value = $this->formatDateTime(time(), $this->dateFormat, true);
        }
        return $value;
    }

    /**
     * 时间日期字段格式化处理
     * @access public
     * @param mixed $time      时间日期表达式
     * @param mixed $format    日期格式
     * @param bool  $timestamp 是否进行时间戳转换
     * @return mixed
     */
    protected function formatDateTime($time, $format, $timestamp = false)
    {
        if (false !== strpos($format, '\\')) {
            $time = new $format($time);
        } elseif (!$timestamp && false !== $format) {
            $time = date($format, $time);
        }
        return $time;
    }

    /**
     * 数据写入 类型转换
     * @access public
     * @param mixed        $value 值
     * @param string|array $type  要转换的类型
     * @return mixed
     */
    protected function writeTransform($value, $type)
    {
        if (is_null($value)) {
            return;
        }

        if (is_array($type)) {
            list($type, $param) = $type;
        } elseif (strpos($type, ':')) {
            list($type, $param) = explode(':', $type, 2);
        }
        switch ($type) {
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
                if (empty($param)) {
                    $value = (float) $value;
                } else {
                    $value = (float) number_format($value, $param, '.', '');
                }
                break;
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'timestamp':
                if (!is_numeric($value)) {
                    $value = strtotime($value);
                }
                break;
            case 'datetime':
                $format = !empty($param) ? $param : $this->dateFormat;
                $value  = is_numeric($value) ? $value : strtotime($value);
                $value  = $this->formatDateTime($value, $format);
                break;
            case 'object':
                if (is_object($value)) {
                    $value = json_encode($value, JSON_FORCE_OBJECT);
                }
                break;
            case 'array':
                $value = (array) $value;
            case 'json':
                $option = !empty($param) ? (int) $param : JSON_UNESCAPED_UNICODE;
                $value  = json_encode($value, $option);
                break;
            case 'serialize':
                $value = serialize($value);
                break;

        }
        return $value;
    }

    /**
     * 获取器 获取数据对象的值
     * @access public
     * @param string $name 名称
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getAttr($name)
    {
        try {
            $notFound = false;
            $value    = $this->getData($name);
        } catch (InvalidArgumentException $e) {
            $notFound = true;
            $value    = null;
        }

        // 检测属性获取器
        $method = 'get' . Loader::parseName($name, 1) . 'Attr';
        if (method_exists($this, $method)) {
            $value = $this->$method($value, $this->data, $this->relation);
        } elseif (isset($this->type[$name])) {
            // 类型转换
            $value = $this->readTransform($value, $this->type[$name]);
        } elseif (in_array($name, [$this->createTime, $this->updateTime])) {
            if (is_string($this->autoWriteTimestamp) && in_array(strtolower($this->autoWriteTimestamp), [
                'datetime',
                'date',
                'timestamp',
            ])
            ) {
                $value = $this->formatDateTime(strtotime($value), $this->dateFormat);
            } else {
                $value = $this->formatDateTime($value, $this->dateFormat);
            }
        } elseif ($notFound) {
            $relation = Loader::parseName($name, 1, false);
            if (method_exists($this, $relation)) {
                $modelRelation = $this->$relation();
                // 不存在该字段 获取关联数据
                $value = $this->getRelationData($modelRelation);
                // 保存关联对象值
                $this->relation[$name] = $value;
            } else {
                throw new InvalidArgumentException('property not exists:' . $this->class . '->' . $name);
            }
        }
        return $value;
    }

    /**
     * 获取关联模型数据
     * @access public
     * @param Relation        $modelRelation 模型关联对象
     * @return mixed
     * @throws BadMethodCallException
     */
    protected function getRelationData(Relation $modelRelation)
    {
        if ($this->parent && !$modelRelation->isSelfRelation() && get_class($modelRelation->getModel()) == get_class($this->parent)) {
            $value = $this->parent;
        } else {
            // 首先获取关联数据
            if (method_exists($modelRelation, 'getRelation')) {
                $value = $modelRelation->getRelation();
            } else {
                throw new BadMethodCallException('method not exists:' . get_class($modelRelation) . '-> getRelation');
            }
        }
        return $value;
    }

    /**
     * 数据读取 类型转换
     * @access public
     * @param mixed        $value 值
     * @param string|array $type  要转换的类型
     * @return mixed
     */
    protected function readTransform($value, $type)
    {
        if (is_null($value)) {
            return;
        }

        if (is_array($type)) {
            list($type, $param) = $type;
        } elseif (strpos($type, ':')) {
            list($type, $param) = explode(':', $type, 2);
        }
        switch ($type) {
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
                if (empty($param)) {
                    $value = (float) $value;
                } else {
                    $value = (float) number_format($value, $param, '.', '');
                }
                break;
            case 'boolean':
                $value = (bool) $value;
                break;
            case 'timestamp':
                if (!is_null($value)) {
                    $format = !empty($param) ? $param : $this->dateFormat;
                    $value  = $this->formatDateTime($value, $format);
                }
                break;
            case 'datetime':
                if (!is_null($value)) {
                    $format = !empty($param) ? $param : $this->dateFormat;
                    $value  = $this->formatDateTime(strtotime($value), $format);
                }
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
            case 'array':
                $value = empty($value) ? [] : json_decode($value, true);
                break;
            case 'object':
                $value = empty($value) ? new \stdClass() : json_decode($value);
                break;
            case 'serialize':
                try {
                    $value = unserialize($value);
                } catch (\Exception $e) {
                    $value = null;
                }
                break;
            default:
                if (false !== strpos($type, '\\')) {
                    // 对象类型
                    $value = new $type($value);
                }
        }
        return $value;
    }

    /**
     * 设置需要追加的输出属性
     * @access public
     * @param array $append   属性列表
     * @param bool  $override 是否覆盖
     * @return $this
     */
    public function append($append = [], $override = false)
    {
        $this->append = $override ? $append : array_merge($this->append, $append);
        return $this;
    }

    /**
     * 设置附加关联对象的属性
     * @access public
     * @param string       $relation 关联方法
     * @param string|array $append   追加属性名
     * @return $this
     * @throws Exception
     */
    public function appendRelationAttr($relation, $append)
    {
        if (is_string($append)) {
            $append = explode(',', $append);
        }

        $relation = Loader::parseName($relation, 1, false);

        // 获取关联数据
        if (isset($this->relation[$relation])) {
            $model = $this->relation[$relation];
        } else {
            $model = $this->getRelationData($this->$relation());
        }

        if ($model instanceof Model) {
            foreach ($append as $key => $attr) {
                $key = is_numeric($key) ? $attr : $key;
                if (isset($this->data[$key])) {
                    throw new Exception('bind attr has exists:' . $key);
                } else {
                    $this->data[$key] = $model->getAttr($attr);
                }
            }
        }
        return $this;
    }

    /**
     * 设置需要隐藏的输出属性
     * @access public
     * @param array $hidden   属性列表
     * @param bool  $override 是否覆盖
     * @return $this
     */
    public function hidden($hidden = [], $override = false)
    {
        $this->hidden = $override ? $hidden : array_merge($this->hidden, $hidden);
        return $this;
    }

    /**
     * 设置需要输出的属性
     * @access public
     * @param array $visible
     * @param bool  $override 是否覆盖
     * @return $this
     */
    public function visible($visible = [], $override = false)
    {
        $this->visible = $override ? $visible : array_merge($this->visible, $visible);
        return $this;
    }

    /**
     * 解析隐藏及显示属性
     * @access protected
     * @param array $attrs  属性
     * @param array $result 结果集
     * @param bool  $visible
     * @return array
     */
    protected function parseAttr($attrs, &$result, $visible = true)
    {
        $array = [];
        foreach ($attrs as $key => $val) {
            if (is_array($val)) {
                if ($visible) {
                    $array[] = $key;
                }
                $result[$key] = $val;
            } elseif (strpos($val, '.')) {
                list($key, $name) = explode('.', $val);
                if ($visible) {
                    $array[] = $key;
                }
                $result[$key][] = $name;
            } else {
                $array[] = $val;
            }
        }
        return $array;
    }

    /**
     * 转换子模型对象
     * @access protected
     * @param Model|ModelCollection $model
     * @param                  $visible
     * @param                  $hidden
     * @param                  $key
     * @return array
     */
    protected function subToArray($model, $visible, $hidden, $key)
    {
        // 关联模型对象
        if (isset($visible[$key])) {
            $model->visible($visible[$key]);
        } elseif (isset($hidden[$key])) {
            $model->hidden($hidden[$key]);
        }
        return $model->toArray();
    }

    /**
     * 转换当前模型对象为数组
     * @access public
     * @return array
     */
    public function toArray()
    {
        $item    = [];
        $visible = [];
        $hidden  = [];

        $data = array_merge($this->data, $this->relation);

        // 过滤属性
        if (!empty($this->visible)) {
            $array = $this->parseAttr($this->visible, $visible);
            $data  = array_intersect_key($data, array_flip($array));
        } elseif (!empty($this->hidden)) {
            $array = $this->parseAttr($this->hidden, $hidden, false);
            $data  = array_diff_key($data, array_flip($array));
        }

        foreach ($data as $key => $val) {
            if ($val instanceof Model || $val instanceof ModelCollection) {
                // 关联模型对象
                $item[$key] = $this->subToArray($val, $visible, $hidden, $key);
            } elseif (is_array($val) && reset($val) instanceof Model) {
                // 关联模型数据集
                $arr = [];
                foreach ($val as $k => $value) {
                    $arr[$k] = $this->subToArray($value, $visible, $hidden, $key);
                }
                $item[$key] = $arr;
            } else {
                // 模型属性
                $item[$key] = $this->getAttr($key);
            }
        }
        // 追加属性（必须定义获取器）
        if (!empty($this->append)) {
            foreach ($this->append as $key => $name) {
                if (is_array($name)) {
                    // 追加关联对象属性
                    $relation   = $this->getAttr($key);
                    $item[$key] = $relation->append($name)->toArray();
                } elseif (strpos($name, '.')) {
                    list($key, $attr) = explode('.', $name);
                    // 追加关联对象属性
                    $relation   = $this->getAttr($key);
                    $item[$key] = $relation->append([$attr])->toArray();
                } else {
                    $relation = Loader::parseName($name, 1, false);
                    if (method_exists($this, $relation)) {
                        $modelRelation = $this->$relation();
                        $value         = $this->getRelationData($modelRelation);

                        if (method_exists($modelRelation, 'getBindAttr')) {
                            $bindAttr = $modelRelation->getBindAttr();
                            if ($bindAttr) {
                                foreach ($bindAttr as $key => $attr) {
                                    $key = is_numeric($key) ? $attr : $key;
                                    if (isset($this->data[$key])) {
                                        throw new Exception('bind attr has exists:' . $key);
                                    } else {
                                        $item[$key] = $value ? $value->getAttr($attr) : null;
                                    }
                                }
                                continue;
                            }
                        }
                        $item[$name] = $value;
                    } else {
                        $item[$name] = $this->getAttr($name);
                    }
                }
            }
        }
        return !empty($item) ? $item : [];
    }

    /**
     * 转换当前模型对象为JSON字符串
     * @access public
     * @param integer $options json参数
     * @return string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * 移除当前模型的关联属性
     * @access public
     * @return $this
     */
    public function removeRelation()
    {
        $this->relation = [];
        return $this;
    }

    /**
     * 转换当前模型数据集为数据集对象
     * @access public
     * @param array|\think\Collection $collection 数据集
     * @return \think\Collection
     */
    public function toCollection($collection)
    {
        if ($this->resultSetType) {
            if ('collection' == $this->resultSetType) {
                $collection = new ModelCollection($collection);
            } elseif (false !== strpos($this->resultSetType, '\\')) {
                $class      = $this->resultSetType;
                $collection = new $class($collection);
            }
        }
        return $collection;
    }

    /**
     * 关联数据一起更新
     * @access public
     * @param mixed $relation 关联
     * @return $this
     */
    public function together($relation)
    {
        if (is_string($relation)) {
            $relation = explode(',', $relation);
        }
        $this->relationWrite = $relation;
        return $this;
    }

    /**
     * 获取模型对象的主键
     * @access public
     * @param string $name 模型名
     * @return mixed
     */
    public function getPk($name = '')
    {
        if (!empty($name)) {
            $table = $this->getQuery()->getTable($name);
            return $this->getQuery()->getPk($table);
        } elseif (empty($this->pk)) {
            $this->pk = $this->getQuery()->getPk();
        }
        return $this->pk;
    }

    /**
     * 判断一个字段名是否为主键字段
     * @access public
     * @param string $key 名称
     * @return bool
     */
    protected function isPk($key)
    {
        $pk = $this->getPk();
        if (is_string($pk) && $pk == $key) {
            return true;
        } elseif (is_array($pk) && in_array($key, $pk)) {
            return true;
        }
        return false;
    }

    /**
     * 新增数据是否使用Replace
     * @access public
     * @param  bool $replace
     * @return $this
     */
    public function replace($replace = true)
    {
        $this->replace = $replace;
        return $this;
    }

    /**
     * 保存当前数据对象
     * @access public
     * @param array  $data     数据
     * @param array  $where    更新条件
     * @param string $sequence 自增序列名
     * @return integer|false
     */
    public function save($data = [], $where = [], $sequence = null)
    {
        if (is_string($data)) {
            $sequence = $data;
            $data     = [];
        }

        // 数据自动验证
        if (!empty($data)) {
            if (!$this->validateData($data)) {
                return false;
            }

            // 数据对象赋值
            foreach ($data as $key => $value) {
                $this->setAttr($key, $value, $data);
            }
        }

        if (!empty($where)) {
            $this->isUpdate    = true;
            $this->updateWhere = $where;
        }

        // 自动关联写入
        if (!empty($this->relationWrite)) {
            $relation = [];
            foreach ($this->relationWrite as $key => $name) {
                if (is_array($name)) {
                    if (key($name) === 0) {
                        $relation[$key] = [];
                        foreach ($name as $val) {
                            if (isset($this->data[$val])) {
                                $relation[$key][$val] = $this->data[$val];
                                unset($this->data[$val]);
                            }
                        }
                    } else {
                        $relation[$key] = $name;
                    }
                } elseif (isset($this->relation[$name])) {
                    $relation[$name] = $this->relation[$name];
                } elseif (isset($this->data[$name])) {
                    $relation[$name] = $this->data[$name];
                    unset($this->data[$name]);
                }
            }
        }

        // 数据自动完成
        $this->autoCompleteData($this->auto);

        // 事件回调
        if (false === $this->trigger('before_write', $this)) {
            return false;
        }
        $pk = $this->getPk();
        if ($this->isUpdate) {
            // 自动更新
            $this->autoCompleteData($this->update);

            // 事件回调
            if (false === $this->trigger('before_update', $this)) {
                return false;
            }

            // 获取有更新的数据
            $data = $this->getChangedData();

            if (empty($data) || (count($data) == 1 && is_string($pk) && isset($data[$pk]))) {
                // 关联更新
                if (isset($relation)) {
                    $this->autoRelationUpdate($relation);
                }
                return 0;
            } elseif ($this->autoWriteTimestamp && $this->updateTime && !isset($data[$this->updateTime])) {
                // 自动写入更新时间
                $data[$this->updateTime]       = $this->autoWriteTimestamp($this->updateTime);
                $this->data[$this->updateTime] = $data[$this->updateTime];
            }

            if (empty($where) && !empty($this->updateWhere)) {
                $where = $this->updateWhere;
            }

            // 保留主键数据
            foreach ($this->data as $key => $val) {
                if ($this->isPk($key)) {
                    $data[$key] = $val;
                }
            }

            $array = [];

            foreach ((array) $pk as $key) {
                if (isset($data[$key])) {
                    $array[$key] = $data[$key];
                    unset($data[$key]);
                }
            }

            if (!empty($array)) {
                $where = $array;
            }

            // 检测字段
            $allowFields = $this->checkAllowField(array_merge($this->auto, $this->update));

            // 模型更新
            if (!empty($allowFields)) {
                $result = $this->getQuery()->where($where)->strict(false)->field($allowFields)->update($data);
            } else {
                $result = $this->getQuery()->where($where)->update($data);
            }

            // 关联更新
            if (isset($relation)) {
                $this->autoRelationUpdate($relation);
            }

            // 更新回调
            $this->trigger('after_update', $this);

        } else {
            // 自动写入
            $this->autoCompleteData($this->insert);

            // 自动写入创建时间和更新时间
            if ($this->autoWriteTimestamp) {
                if ($this->createTime && !isset($this->data[$this->createTime])) {
                    $this->data[$this->createTime] = $this->autoWriteTimestamp($this->createTime);
                }
                if ($this->updateTime && !isset($this->data[$this->updateTime])) {
                    $this->data[$this->updateTime] = $this->autoWriteTimestamp($this->updateTime);
                }
            }

            if (false === $this->trigger('before_insert', $this)) {
                return false;
            }

            // 检测字段
            $allowFields = $this->checkAllowField(array_merge($this->auto, $this->insert));
            if (!empty($allowFields)) {
                $result = $this->getQuery()->strict(false)->field($allowFields)->insert($this->data, $this->replace, false, $sequence);
            } else {
                $result = $this->getQuery()->insert($this->data, $this->replace, false, $sequence);
            }

            // 获取自动增长主键
            if ($result && $insertId = $this->getQuery()->getLastInsID($sequence)) {
                foreach ((array) $pk as $key) {
                    if (!isset($this->data[$key]) || '' == $this->data[$key]) {
                        $this->data[$key] = $insertId;
                    }
                }
            }

            // 关联写入
            if (isset($relation)) {
                foreach ($relation as $name => $val) {
                    $method = Loader::parseName($name, 1, false);
                    $this->$method()->save($val);
                }
            }

            // 标记为更新
            $this->isUpdate = true;

            // 新增回调
            $this->trigger('after_insert', $this);
        }
        // 写入回调
        $this->trigger('after_write', $this);

        // 重新记录原始数据
        $this->origin = $this->data;

        return $result;
    }

    protected function checkAllowField($auto = [])
    {
        if (true === $this->field) {
            $this->field = $this->getQuery()->getTableInfo('', 'fields');
            $field       = $this->field;
        } elseif (!empty($this->field)) {
            $field = array_merge($this->field, $auto);
            if ($this->autoWriteTimestamp) {
                array_push($field, $this->createTime, $this->updateTime);
            }
        } elseif (!empty($this->except)) {
            $fields      = $this->getQuery()->getTableInfo('', 'fields');
            $field       = array_diff($fields, (array) $this->except);
            $this->field = $field;
        } else {
            $field = [];
        }

        if ($this->disuse) {
            // 废弃字段
            $field = array_diff($field, (array) $this->disuse);
        }
        return $field;
    }

    protected function autoRelationUpdate($relation)
    {
        foreach ($relation as $name => $val) {
            if ($val instanceof Model) {
                $val->save();
            } else {
                unset($this->data[$name]);
                $model = $this->getAttr($name);
                if ($model instanceof Model) {
                    $model->save($val);
                }
            }
        }
    }

    /**
     * 获取变化的数据 并排除只读数据
     * @access public
     * @return array
     */
    public function getChangedData()
    {
        if ($this->force) {
            $data = $this->data;
        } else {
            $data = array_udiff_assoc($this->data, $this->origin, function ($a, $b) {
                if ((empty($a) || empty($b)) && $a !== $b) {
                    return 1;
                }
                return is_object($a) || $a != $b ? 1 : 0;
            });
        }

        if (!empty($this->readonly)) {
            // 只读字段不允许更新
            foreach ($this->readonly as $key => $field) {
                if (isset($data[$field])) {
                    unset($data[$field]);
                }
            }
        }

        return $data;
    }

    /**
     * 字段值(延迟)增长
     * @access public
     * @param string  $field    字段名
     * @param integer $step     增长值
     * @param integer $lazyTime 延时时间(s)
     * @return integer|true
     * @throws Exception
     */
    public function setInc($field, $step = 1, $lazyTime = 0)
    {
        // 更新条件
        $where = $this->getWhere();

        $result = $this->getQuery()->where($where)->setInc($field, $step, $lazyTime);
        if (true !== $result) {
            $this->data[$field] += $step;
        }

        return $result;
    }

    /**
     * 字段值(延迟)增长
     * @access public
     * @param string  $field    字段名
     * @param integer $step     增长值
     * @param integer $lazyTime 延时时间(s)
     * @return integer|true
     * @throws Exception
     */
    public function setDec($field, $step = 1, $lazyTime = 0)
    {
        // 更新条件
        $where  = $this->getWhere();
        $result = $this->getQuery()->where($where)->setDec($field, $step, $lazyTime);
        if (true !== $result) {
            $this->data[$field] -= $step;
        }

        return $result;
    }

    /**
     * 获取更新条件
     * @access protected
     * @return mixed
     */
    protected function getWhere()
    {
        // 删除条件
        $pk = $this->getPk();

        if (is_string($pk) && isset($this->data[$pk])) {
            $where = [$pk => $this->data[$pk]];
        } elseif (!empty($this->updateWhere)) {
            $where = $this->updateWhere;
        } else {
            $where = null;
        }
        return $where;
    }

    /**
     * 保存多个数据到当前数据对象
     * @access public
     * @param array   $dataSet 数据
     * @param boolean $replace 是否自动识别更新和写入
     * @return array|false
     * @throws \Exception
     */
    public function saveAll($dataSet, $replace = true)
    {
        if ($this->validate) {
            // 数据批量验证
            $validate = $this->validate;
            foreach ($dataSet as $data) {
                if (!$this->validateData($data, $validate)) {
                    return false;
                }
            }
        }

        $result = [];
        $db     = $this->getQuery();
        $db->startTrans();
        try {
            $pk = $this->getPk();
            if (is_string($pk) && $replace) {
                $auto = true;
            }
            
            foreach ($dataSet as $key => $data) {
                if ($this->isUpdate || (!empty($auto) && isset($data[$pk]))) {
                    $result[$key] = self::update($data, [], $this->field);
                } else {
                    $result[$key] = self::create($data, $this->field);
                }
            }
            $db->commit();
            return $this->toCollection($result);
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * 设置允许写入的字段
     * @access public
     * @param string|array $field 允许写入的字段 如果为true只允许写入数据表字段
     * @return $this
     */
    public function allowField($field)
    {
        if (is_string($field)) {
            $field = explode(',', $field);
        }
        $this->field = $field;
        return $this;
    }

    /**
     * 设置排除写入的字段
     * @access public
     * @param string|array $field 排除允许写入的字段
     * @return $this
     */
    public function except($field)
    {
        if (is_string($field)) {
            $field = explode(',', $field);
        }
        $this->except = $field;
        return $this;
    }

    /**
     * 设置只读字段
     * @access public
     * @param mixed $field 只读字段
     * @return $this
     */
    public function readonly($field)
    {
        if (is_string($field)) {
            $field = explode(',', $field);
        }
        $this->readonly = $field;
        return $this;
    }

    /**
     * 是否为更新数据
     * @access public
     * @param bool  $update
     * @param mixed $where
     * @return $this
     */
    public function isUpdate($update = true, $where = null)
    {
        $this->isUpdate = $update;
        if (!empty($where)) {
            $this->updateWhere = $where;
        }
        return $this;
    }

    /**
     * 数据自动完成
     * @access public
     * @param array $auto 要自动更新的字段列表
     * @return void
     */
    protected function autoCompleteData($auto = [])
    {
        foreach ($auto as $field => $value) {
            if (is_integer($field)) {
                $field = $value;
                $value = null;
            }

            if (!isset($this->data[$field])) {
                $default = null;
            } else {
                $default = $this->data[$field];
            }

            $this->setAttr($field, !is_null($value) ? $value : $default);
        }
    }

    /**
     * 删除当前的记录
     * @access public
     * @return integer
     */
    public function delete()
    {
        if (false === $this->trigger('before_delete', $this)) {
            return false;
        }

        // 删除条件
        $where = $this->getWhere();

        // 删除当前模型数据
        $result = $this->getQuery()->where($where)->delete();

        // 关联删除
        if (!empty($this->relationWrite)) {
            foreach ($this->relationWrite as $key => $name) {
                $name  = is_numeric($key) ? $name : $key;
                $model = $this->getAttr($name);
                if ($model instanceof Model) {
                    $model->delete();
                }
            }
        }

        $this->trigger('after_delete', $this);
        // 清空原始数据
        $this->origin = [];

        return $result;
    }

    /**
     * 设置自动完成的字段（ 规则通过修改器定义）
     * @access public
     * @param array $fields 需要自动完成的字段
     * @return $this
     */
    public function auto($fields)
    {
        $this->auto = $fields;
        return $this;
    }

    /**
     * 设置字段验证
     * @access public
     * @param array|string|bool $rule  验证规则 true表示自动读取验证器类
     * @param array             $msg   提示信息
     * @param bool              $batch 批量验证
     * @return $this
     */
    public function validate($rule = true, $msg = [], $batch = false)
    {
        if (is_array($rule)) {
            $this->validate = [
                'rule' => $rule,
                'msg'  => $msg,
            ];
        } else {
            $this->validate = true === $rule ? $this->name : $rule;
        }
        $this->batchValidate = $batch;
        return $this;
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access public
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    public function validateFailException($fail = true)
    {
        $this->failException = $fail;
        return $this;
    }

    /**
     * 自动验证数据
     * @access protected
     * @param array $data  验证数据
     * @param mixed $rule  验证规则
     * @param bool  $batch 批量验证
     * @return bool
     */
    protected function validateData($data, $rule = null, $batch = null)
    {
        $info = is_null($rule) ? $this->validate : $rule;

        if (!empty($info)) {
            if (is_array($info)) {
                $validate = Loader::validate();
                $validate->rule($info['rule']);
                $validate->message($info['msg']);
            } else {
                $name = is_string($info) ? $info : $this->name;
                if (strpos($name, '.')) {
                    list($name, $scene) = explode('.', $name);
                }
                $validate = Loader::validate($name);
                if (!empty($scene)) {
                    $validate->scene($scene);
                }
            }
            $batch = is_null($batch) ? $this->batchValidate : $batch;

            if (!$validate->batch($batch)->check($data)) {
                $this->error = $validate->getError();
                if ($this->failException) {
                    throw new ValidateException($this->error);
                } else {
                    return false;
                }
            }
            $this->validate = null;
        }
        return true;
    }

    /**
     * 返回模型的错误信息
     * @access public
     * @return string|array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 注册回调方法
     * @access public
     * @param string   $event    事件名
     * @param callable $callback 回调方法
     * @param bool     $override 是否覆盖
     * @return void
     */
    public static function event($event, $callback, $override = false)
    {
        $class = get_called_class();
        if ($override) {
            self::$event[$class][$event] = [];
        }
        self::$event[$class][$event][] = $callback;
    }

    /**
     * 触发事件
     * @access protected
     * @param string $event  事件名
     * @param mixed  $params 传入参数（引用）
     * @return bool
     */
    protected function trigger($event, &$params)
    {
        if (isset(self::$event[$this->class][$event])) {
            foreach (self::$event[$this->class][$event] as $callback) {
                if (is_callable($callback)) {
                    $result = call_user_func_array($callback, [ & $params]);
                    if (false === $result) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * 写入数据
     * @access public
     * @param array      $data  数据数组
     * @param array|true $field 允许字段
     * @return $this
     */
    public static function create($data = [], $field = null)
    {
        $model = new static();
        if (!empty($field)) {
            $model->allowField($field);
        }
        $model->isUpdate(false)->save($data, []);
        return $model;
    }

    /**
     * 更新数据
     * @access public
     * @param array      $data  数据数组
     * @param array      $where 更新条件
     * @param array|true $field 允许字段
     * @return $this
     */
    public static function update($data = [], $where = [], $field = null)
    {
        $model = new static();
        if (!empty($field)) {
            $model->allowField($field);
        }
        $result = $model->isUpdate(true)->save($data, $where);
        return $model;
    }

    /**
     * 查找单条记录
     * @access public
     * @param mixed        $data  主键值或者查询条件（闭包）
     * @param array|string $with  关联预查询
     * @param bool         $cache 是否缓存
     * @return static|null
     * @throws exception\DbException
     */
    public static function get($data, $with = [], $cache = false)
    {
        if (is_null($data)) {
            return;
        }

        if (true === $with || is_int($with)) {
            $cache = $with;
            $with  = [];
        }
        $query = static::parseQuery($data, $with, $cache);
        return $query->find($data);
    }

    /**
     * 查找所有记录
     * @access public
     * @param mixed        $data  主键列表或者查询条件（闭包）
     * @param array|string $with  关联预查询
     * @param bool         $cache 是否缓存
     * @return static[]|false
     * @throws exception\DbException
     */
    public static function all($data = null, $with = [], $cache = false)
    {
        if (true === $with || is_int($with)) {
            $cache = $with;
            $with  = [];
        }
        $query = static::parseQuery($data, $with, $cache);
        return $query->select($data);
    }

    /**
     * 分析查询表达式
     * @access public
     * @param mixed  $data  主键列表或者查询条件（闭包）
     * @param string $with  关联预查询
     * @param bool   $cache 是否缓存
     * @return Query
     */
    protected static function parseQuery(&$data, $with, $cache)
    {
        $result = self::with($with)->cache($cache);
        if (is_array($data) && key($data) !== 0) {
            $result = $result->where($data);
            $data   = null;
        } elseif ($data instanceof \Closure) {
            call_user_func_array($data, [ & $result]);
            $data = null;
        } elseif ($data instanceof Query) {
            $result = $data->with($with)->cache($cache);
            $data   = null;
        }
        return $result;
    }

    /**
     * 删除记录
     * @access public
     * @param mixed $data 主键列表 支持闭包查询条件
     * @return integer 成功删除的记录数
     */
    public static function destroy($data)
    {
        $model = new static();
        $query = $model->db();
        if (empty($data) && 0 !== $data) {
            return 0;
        } elseif (is_array($data) && key($data) !== 0) {
            $query->where($data);
            $data = null;
        } elseif ($data instanceof \Closure) {
            call_user_func_array($data, [ & $query]);
            $data = null;
        }
        $resultSet = $query->select($data);
        $count     = 0;
        if ($resultSet) {
            foreach ($resultSet as $data) {
                $result = $data->delete();
                $count += $result;
            }
        }
        return $count;
    }

    /**
     * 命名范围
     * @access public
     * @param string|array|\Closure $name 命名范围名称 逗号分隔
     * @internal  mixed                 ...$params 参数调用
     * @return Query
     */
    public static function scope($name)
    {
        $model  = new static();
        $query  = $model->db();
        $params = func_get_args();
        array_shift($params);
        array_unshift($params, $query);
        if ($name instanceof \Closure) {
            call_user_func_array($name, $params);
        } elseif (is_string($name)) {
            $name = explode(',', $name);
        }
        if (is_array($name)) {
            foreach ($name as $scope) {
                $method = 'scope' . trim($scope);
                if (method_exists($model, $method)) {
                    call_user_func_array([$model, $method], $params);
                }
            }
        }
        return $query;
    }

    /**
     * 设置是否使用全局查询范围
     * @param bool $use 是否启用全局查询范围
     * @access public
     * @return Query
     */
    public static function useGlobalScope($use)
    {
        $model = new static();
        return $model->db($use);
    }

    /**
     * 根据关联条件查询当前模型
     * @access public
     * @param string  $relation 关联方法名
     * @param mixed   $operator 比较操作符
     * @param integer $count    个数
     * @param string  $id       关联表的统计字段
     * @return Relation|Query
     */
    public static function has($relation, $operator = '>=', $count = 1, $id = '*')
    {
        $relation = (new static())->$relation();
        if (is_array($operator) || $operator instanceof \Closure) {
            return $relation->hasWhere($operator);
        }
        return $relation->has($operator, $count, $id);
    }

    /**
     * 根据关联条件查询当前模型
     * @access public
     * @param  string $relation 关联方法名
     * @param  mixed  $where    查询条件（数组或者闭包）
     * @param  mixed  $fields   字段
     * @return Relation|Query
     */
    public static function hasWhere($relation, $where = [], $fields = null)
    {
        return (new static())->$relation()->hasWhere($where, $fields);
    }

    /**
     * 解析模型的完整命名空间
     * @access public
     * @param string $model 模型名（或者完整类名）
     * @return string
     */
    protected function parseModel($model)
    {
        if (false === strpos($model, '\\')) {
            $path = explode('\\', get_called_class());
            array_pop($path);
            array_push($path, Loader::parseName($model, 1));
            $model = implode('\\', $path);
        }
        return $model;
    }

    /**
     * 查询当前模型的关联数据
     * @access public
     * @param string|array $relations 关联名
     * @return $this
     */
    public function relationQuery($relations)
    {
        if (is_string($relations)) {
            $relations = explode(',', $relations);
        }

        foreach ($relations as $key => $relation) {
            $subRelation = '';
            $closure     = null;
            if ($relation instanceof \Closure) {
                // 支持闭包查询过滤关联条件
                $closure  = $relation;
                $relation = $key;
            }
            if (is_array($relation)) {
                $subRelation = $relation;
                $relation    = $key;
            } elseif (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);
            }
            $method                = Loader::parseName($relation, 1, false);
            $this->data[$relation] = $this->$method()->getRelation($subRelation, $closure);
        }
        return $this;
    }

    /**
     * 预载入关联查询 返回数据集
     * @access public
     * @param array  $resultSet 数据集
     * @param string $relation  关联名
     * @return array
     */
    public function eagerlyResultSet(&$resultSet, $relation)
    {
        $relations = is_string($relation) ? explode(',', $relation) : $relation;
        foreach ($relations as $key => $relation) {
            $subRelation = '';
            $closure     = false;
            if ($relation instanceof \Closure) {
                $closure  = $relation;
                $relation = $key;
            }
            if (is_array($relation)) {
                $subRelation = $relation;
                $relation    = $key;
            } elseif (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);
            }
            $relation = Loader::parseName($relation, 1, false);
            $this->$relation()->eagerlyResultSet($resultSet, $relation, $subRelation, $closure);
        }
    }

    /**
     * 预载入关联查询 返回模型对象
     * @access public
     * @param Model  $result   数据对象
     * @param string $relation 关联名
     * @return Model
     */
    public function eagerlyResult(&$result, $relation)
    {
        $relations = is_string($relation) ? explode(',', $relation) : $relation;

        foreach ($relations as $key => $relation) {
            $subRelation = '';
            $closure     = false;
            if ($relation instanceof \Closure) {
                $closure  = $relation;
                $relation = $key;
            }
            if (is_array($relation)) {
                $subRelation = $relation;
                $relation    = $key;
            } elseif (strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);
            }
            $relation = Loader::parseName($relation, 1, false);
            $this->$relation()->eagerlyResult($result, $relation, $subRelation, $closure);
        }
    }

    /**
     * 关联统计
     * @access public
     * @param Model        $result   数据对象
     * @param string|array $relation 关联名
     * @return void
     */
    public function relationCount(&$result, $relation)
    {
        $relations = is_string($relation) ? explode(',', $relation) : $relation;

        foreach ($relations as $key => $relation) {
            $closure = false;
            if ($relation instanceof \Closure) {
                $closure  = $relation;
                $relation = $key;
            } elseif (is_string($key)) {
                $name     = $relation;
                $relation = $key;
            }
            $relation = Loader::parseName($relation, 1, false);
            $count    = $this->$relation()->relationCount($result, $closure);
            if (!isset($name)) {
                $name = Loader::parseName($relation) . '_count';
            }
            $result->setAttr($name, $count);
        }
    }

    /**
     * 获取模型的默认外键名
     * @access public
     * @param string $name 模型名
     * @return string
     */
    protected function getForeignKey($name)
    {
        if (strpos($name, '\\')) {
            $name = basename(str_replace('\\', '/', $name));
        }
        return Loader::parseName($name) . '_id';
    }

    /**
     * HAS ONE 关联定义
     * @access public
     * @param string $model      模型名
     * @param string $foreignKey 关联外键
     * @param string $localKey   当前模型主键
     * @param array  $alias      别名定义（已经废弃）
     * @param string $joinType   JOIN类型
     * @return HasOne
     */
    public function hasOne($model, $foreignKey = '', $localKey = '', $alias = [], $joinType = 'INNER')
    {
        // 记录当前关联信息
        $model      = $this->parseModel($model);
        $localKey   = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);
        return new HasOne($this, $model, $foreignKey, $localKey, $joinType);
    }

    /**
     * BELONGS TO 关联定义
     * @access public
     * @param string $model      模型名
     * @param string $foreignKey 关联外键
     * @param string $localKey   关联主键
     * @param array  $alias      别名定义（已经废弃）
     * @param string $joinType   JOIN类型
     * @return BelongsTo
     */
    public function belongsTo($model, $foreignKey = '', $localKey = '', $alias = [], $joinType = 'INNER')
    {
        // 记录当前关联信息
        $model      = $this->parseModel($model);
        $foreignKey = $foreignKey ?: $this->getForeignKey($model);
        $localKey   = $localKey ?: (new $model)->getPk();
        $trace      = debug_backtrace(false, 2);
        $relation   = Loader::parseName($trace[1]['function']);
        return new BelongsTo($this, $model, $foreignKey, $localKey, $joinType, $relation);
    }

    /**
     * HAS MANY 关联定义
     * @access public
     * @param string $model      模型名
     * @param string $foreignKey 关联外键
     * @param string $localKey   当前模型主键
     * @return HasMany
     */
    public function hasMany($model, $foreignKey = '', $localKey = '')
    {
        // 记录当前关联信息
        $model      = $this->parseModel($model);
        $localKey   = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);
        return new HasMany($this, $model, $foreignKey, $localKey);
    }

    /**
     * HAS MANY 远程关联定义
     * @access public
     * @param string $model      模型名
     * @param string $through    中间模型名
     * @param string $foreignKey 关联外键
     * @param string $throughKey 关联外键
     * @param string $localKey   当前模型主键
     * @return HasManyThrough
     */
    public function hasManyThrough($model, $through, $foreignKey = '', $throughKey = '', $localKey = '')
    {
        // 记录当前关联信息
        $model      = $this->parseModel($model);
        $through    = $this->parseModel($through);
        $localKey   = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);
        $throughKey = $throughKey ?: $this->getForeignKey($through);
        return new HasManyThrough($this, $model, $through, $foreignKey, $throughKey, $localKey);
    }

    /**
     * BELONGS TO MANY 关联定义
     * @access public
     * @param string $model      模型名
     * @param string $table      中间表名
     * @param string $foreignKey 关联外键
     * @param string $localKey   当前模型关联键
     * @return BelongsToMany
     */
    public function belongsToMany($model, $table = '', $foreignKey = '', $localKey = '')
    {
        // 记录当前关联信息
        $model      = $this->parseModel($model);
        $name       = Loader::parseName(basename(str_replace('\\', '/', $model)));
        $table      = $table ?: Loader::parseName($this->name) . '_' . $name;
        $foreignKey = $foreignKey ?: $name . '_id';
        $localKey   = $localKey ?: $this->getForeignKey($this->name);
        return new BelongsToMany($this, $model, $table, $foreignKey, $localKey);
    }

    /**
     * MORPH  MANY 关联定义
     * @access public
     * @param string       $model 模型名
     * @param string|array $morph 多态字段信息
     * @param string       $type  多态类型
     * @return MorphMany
     */
    public function morphMany($model, $morph = null, $type = '')
    {
        // 记录当前关联信息
        $model = $this->parseModel($model);
        if (is_null($morph)) {
            $trace = debug_backtrace(false, 2);
            $morph = Loader::parseName($trace[1]['function']);
        }
        $type = $type ?: get_class($this);
        if (is_array($morph)) {
            list($morphType, $foreignKey) = $morph;
        } else {
            $morphType  = $morph . '_type';
            $foreignKey = $morph . '_id';
        }
        return new MorphMany($this, $model, $foreignKey, $morphType, $type);
    }

    /**
     * MORPH  One 关联定义
     * @access public
     * @param string       $model 模型名
     * @param string|array $morph 多态字段信息
     * @param string       $type  多态类型
     * @return MorphOne
     */
    public function morphOne($model, $morph = null, $type = '')
    {
        // 记录当前关联信息
        $model = $this->parseModel($model);
        if (is_null($morph)) {
            $trace = debug_backtrace(false, 2);
            $morph = Loader::parseName($trace[1]['function']);
        }
        $type = $type ?: get_class($this);
        if (is_array($morph)) {
            list($morphType, $foreignKey) = $morph;
        } else {
            $morphType  = $morph . '_type';
            $foreignKey = $morph . '_id';
        }
        return new MorphOne($this, $model, $foreignKey, $morphType, $type);
    }

    /**
     * MORPH TO 关联定义
     * @access public
     * @param string|array $morph 多态字段信息
     * @param array        $alias 多态别名定义
     * @return MorphTo
     */
    public function morphTo($morph = null, $alias = [])
    {
        $trace    = debug_backtrace(false, 2);
        $relation = Loader::parseName($trace[1]['function']);

        if (is_null($morph)) {
            $morph = $relation;
        }
        // 记录当前关联信息
        if (is_array($morph)) {
            list($morphType, $foreignKey) = $morph;
        } else {
            $morphType  = $morph . '_type';
            $foreignKey = $morph . '_id';
        }
        return new MorphTo($this, $morphType, $foreignKey, $alias, $relation);
    }

    public function __call($method, $args)
    {
        $query = $this->db(true, false);
        if (method_exists($this, 'scope' . $method)) {
            // 动态调用命名范围
            $method = 'scope' . $method;
            array_unshift($args, $query);
            call_user_func_array([$this, $method], $args);
            return $this;
        } else {
            return call_user_func_array([$query, $method], $args);
        }
    }

    public static function __callStatic($method, $args)
    {
        $model = new static();
        $query = $model->db();
        if (method_exists($model, 'scope' . $method)) {
            // 动态调用命名范围
            $method = 'scope' . $method;
            array_unshift($args, $query);

            call_user_func_array([$model, $method], $args);
            return $query;
        } else {
            return call_user_func_array([$query, $method], $args);
        }
    }

    /**
     * 修改器 设置数据对象的值
     * @access public
     * @param string $name  名称
     * @param mixed  $value 值
     * @return void
     */
    public function __set($name, $value)
    {
        $this->setAttr($name, $value);
    }

    /**
     * 获取器 获取数据对象的值
     * @access public
     * @param string $name 名称
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getAttr($name);
    }

    /**
     * 检测数据对象的值
     * @access public
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name)
    {
        try {
            if (array_key_exists($name, $this->data) || array_key_exists($name, $this->relation)) {
                return true;
            } else {
                $this->getAttr($name);
                return true;
            }
        } catch (InvalidArgumentException $e) {
            return false;
        }

    }

    /**
     * 销毁数据对象的值
     * @access public
     * @param string $name 名称
     * @return void
     */
    public function __unset($name)
    {
        unset($this->data[$name], $this->relation[$name]);
    }

    public function __toString()
    {
        return $this->toJson();
    }

    // JsonSerializable
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    // ArrayAccess
    public function offsetSet($name, $value)
    {
        $this->setAttr($name, $value);
    }

    public function offsetExists($name)
    {
        return $this->__isset($name);
    }

    public function offsetUnset($name)
    {
        $this->__unset($name);
    }

    public function offsetGet($name)
    {
        return $this->getAttr($name);
    }

    /**
     * 解序列化后处理
     */
    public function __wakeup()
    {
        $this->initialize();
    }

    /**
     * 模型事件快捷方法
     * @param      $callback
     * @param bool $override
     */
    protected static function beforeInsert($callback, $override = false)
    {
        self::event('before_insert', $callback, $override);
    }

    protected static function afterInsert($callback, $override = false)
    {
        self::event('after_insert', $callback, $override);
    }

    protected static function beforeUpdate($callback, $override = false)
    {
        self::event('before_update', $callback, $override);
    }

    protected static function afterUpdate($callback, $override = false)
    {
        self::event('after_update', $callback, $override);
    }

    protected static function beforeWrite($callback, $override = false)
    {
        self::event('before_write', $callback, $override);
    }

    protected static function afterWrite($callback, $override = false)
    {
        self::event('after_write', $callback, $override);
    }

    protected static function beforeDelete($callback, $override = false)
    {
        self::event('before_delete', $callback, $override);
    }

    protected static function afterDelete($callback, $override = false)
    {
        self::event('after_delete', $callback, $override);
    }




    public function batch_export_xls_copy()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        //根据传的标签切换状态
        $map = [];
        $map['d.check_time'] = ['between',['1614528000','1617206400']];


        $list = $this->model
            ->alias('a')
            ->field('a.id as aid,a.item_order_number,a.sku,a.order_prescription_type,b.increment_id,b.status,b.total_qty_ordered,b.site,a.distribution_status,a.created_at,c.*,b.base_grand_total,b.order_type,b.base_currency_code,b.payment_time,b.payment_method,d.check_time')
            ->join(['fa_order' => 'b'], 'a.order_id=b.id')
            ->join(['fa_order_item_option' => 'c'], 'a.option_id=c.id')
            ->join(['fa_order_process' => 'd'], 'a.order_id=d.order_id')
            ->where($map)
            ->select();

        $list = collection($list)->toArray();

        //从数据库查询需要的数据
        $spreadsheet = new Spreadsheet();

        //常规方式：利用setCellValue()填充数据
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue("A1", "ID")
            ->setCellValue("B1", "日期")
            ->setCellValue("C1", "订单号")
            ->setCellValue("D1", "站点")
            ->setCellValue("E1", "订单类型")
            ->setCellValue("F1", "订单状态")
            ->setCellValue("G1", "子单号")
            ->setCellValue("H1", "SKU")
            ->setCellValue("I1", "眼球")
            ->setCellValue("J1", "SPH")
            ->setCellValue("K1", "CYL")
            ->setCellValue("L1", "AXI")
            ->setCellValue("M1", "ADD")
            ->setCellValue("N1", "PD")
            ->setCellValue("O1", "镜片")
            ->setCellValue("P1", "镜框宽度")
            ->setCellValue("Q1", "镜框高度")
            ->setCellValue("R1", "bridge")
            ->setCellValue("S1", "处方类型")
            ->setCellValue("T1", "Prism\n(out/in)")
            ->setCellValue("U1", "Direct\n(out/in)")
            ->setCellValue("V1", "Prism\n(up/down)")
            ->setCellValue("W1", "Direct\n(up/down)")
            ->setCellValue("X1", "订单金额")
            ->setCellValue("Y1", "原币种")
            ->setCellValue("Z1", "原支付金额")
            ->setCellValue("AA1", "支付方式")
            ->setCellValue("AB1", "订单支付时间")
            ->setCellValue("AC1", "审单时间");
        $spreadsheet->setActiveSheetIndex(0)->setTitle('订单处方');

        //站点列表
        $site_list = [
            1 => 'Zeelool',
            2 => 'Voogueme',
            3 => 'Nihao',
            4 => 'Meeloog',
            5 => 'Wesee',
            8 => 'Amazon',
            9 => 'Zeelool_es',
            10 => 'Zeelool_de',
            11 => 'Zeelool_jp'
        ];

        //子单号状态
        $distribution_status_list = [
            1 => '待打印标签',
            2 => '待配货',
            3 => '待配镜片',
            4 => '待加工',
            5 => '待印logo',
            6 => '待成品质检',
            7 => '待合单',
            8 => '合单中',
            9 => '合单完成'
        ];

        //获取更改镜框最新信息
        $change_sku = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 1,
                'a.item_order_number' => ['in', array_column($list, 'item_order_number')],
                'b.operation_type' => 1
            ])
            ->column('a.change_sku', 'a.item_order_number');

        //获取更改镜片最新处方信息
        $change_lens = $this->_work_order_change_sku
            ->alias('a')
            ->join(['fa_work_order_measure' => 'b'], 'a.measure_id=b.id')
            ->where([
                'a.change_type' => 2,
                'a.item_order_number' => ['in', array_column($list, 'item_order_number')],
                'b.operation_type' => 1
            ])
            ->column('a.od_sph,a.od_cyl,a.od_axis,a.od_add,a.pd_r,a.od_pv,a.od_bd,a.od_pv_r,a.od_bd_r,a.os_sph,a.os_cyl,a.os_axis,a.os_add,a.pd_l,a.os_pv,a.os_bd,a.os_pv_r,a.os_bd_r,a.lens_number,a.recipe_type as prescription_type,a.web_lens_name', 'a.item_order_number');

        if ($change_lens) {
            foreach ($change_lens as $key => $val) {
                if ($val['pd_l'] && $val['pd_r']) {
                    $change_lens[$key]['pd'] = '';
                    $change_lens[$key]['pdcheck'] = 'on';
                } else {
                    $change_lens[$key]['pd'] = $val['pd_r'] ?: $val['pd_l'];
                    $change_lens[$key]['pdcheck'] = '';
                }
            }
        }
        //获取镜片编码及名称
        $lens_list = $this->_lens_data->column('lens_name', 'lens_number');
        foreach ($list as $key => &$value) {

            //更改镜框最新sku
            if ($change_sku[$value['item_order_number']] && $value['site'] != 5) {
                $value['sku'] = $change_sku[$value['item_order_number']];

                $getGlassInfo = $this->httpRequest($value['site'], 'magic/order/getGlassInfo', ['skus' => $value['sku']], 'POST');
                $tmp_bridge = $getGlassInfo[0];
            } else {
                //过滤饰品站 批发站
                if ($value['site'] != 12) {
                    //查询镜框尺寸
                    $tmp_bridge = $this->get_frame_lens_width_height_bridge($value['product_id'], $value['site']);
                }
            }

            $data[$value['increment_id']]['item_order'][$key]['lens_width'] = $tmp_bridge['lens_width'];
            $data[$value['increment_id']]['item_order'][$key]['lens_height'] = $tmp_bridge['lens_height'];
            $data[$value['increment_id']]['item_order'][$key]['bridge'] = $tmp_bridge['bridge'];

            //更改镜片最新数据
            if ($change_lens[$value['item_order_number']]) {
                $value = array_merge($value, $change_lens[$value['item_order_number']]);
            }

            $data[$value['increment_id']]['id'] = $value['id']; //id
            $data[$value['increment_id']]['created_at'] = $value['created_at'];//日期
            $data[$value['increment_id']]['increment_id'] = $value['increment_id'];//订单号
            $data[$value['increment_id']]['site'] = $value['site'];//站点
            $data[$value['increment_id']]['order_type'] = $value['order_type'];//订单类型
            $data[$value['increment_id']]['status'] = $value['status'];//订单状态
            $data[$value['increment_id']]['item_order_number'] = $value['item_order_number'];//子单号
            $data[$value['increment_id']]['sku'] = $value['sku'];//sku
            $data[$value['increment_id']]['od_sph'] = $value['od_sph'];
            $data[$value['increment_id']]['os_sph'] = $value['os_sph'];//sph
            $data[$value['increment_id']]['od_cyl'] = $value['od_cyl'];//cyl
            $data[$value['increment_id']]['os_cyl'] = $value['os_cyl'];
            $data[$value['increment_id']]['od_axis'] = $value['od_axis'];//axis
            $data[$value['increment_id']]['os_axis'] = $value['os_axis'];
            $data[$value['increment_id']]['od_add'] = $value['od_add'];
            $data[$value['increment_id']]['os_add'] = $value['os_add'];
            $data[$value['increment_id']]['pd'] = $value['pd'];
            $data[$value['increment_id']]['lens_number'] = $lens_list[$value['lens_number']] ?: $value['web_lens_name'];//镜片



            $data[$value['increment_id']]['item_order'][$key]['pdcheck'] = $value['pdcheck'];
            $data[$value['increment_id']]['item_order'][$key]['product_id'] = $value['product_id'];

            $data[$value['increment_id']]['item_order'][$key]['prescription_type'] = $value['prescription_type'];
            $data[$value['increment_id']]['item_order'][$key]['od_pv'] = $value['od_pv'];
            $data[$value['increment_id']]['item_order'][$key]['os_pv'] = $value['os_pv'];
            $data[$value['increment_id']]['item_order'][$key]['pd_r'] = $value['pd_r'];
            $data[$value['increment_id']]['item_order'][$key]['pd_l'] = $value['pd_l'];
            $data[$value['increment_id']]['item_order'][$key]['os_pv'] = $value['os_pv'];
            $data[$value['increment_id']]['item_order'][$key]['os_bd'] = $value['os_bd'];
            $data[$value['increment_id']]['item_order'][$key]['od_bd'] = $value['od_bd'];
            $data[$value['increment_id']]['item_order'][$key]['od_pv_r'] = $value['od_pv_r'];
            $data[$value['increment_id']]['item_order'][$key]['os_bd_r'] = $value['os_bd_r'];
            $data[$value['increment_id']]['item_order'][$key]['od_bd_r'] = $value['od_bd_r'];
            $data[$value['increment_id']]['item_order'][$key]['os_pv_r'] = $value['os_pv_r'];
            $data[$value['increment_id']]['item_order'][$key]['os_sph'] = $value['os_sph'];
            $data[$value['increment_id']]['item_order'][$key]['od_sph'] = $value['od_sph'];
            $data[$value['increment_id']]['item_order'][$key]['os_cyl'] = $value['os_cyl'];
            $data[$value['increment_id']]['item_order'][$key]['od_cyl'] = $value['od_cyl'];
            $data[$value['increment_id']]['item_order'][$key]['os_axis'] = $value['os_axis'];
            $data[$value['increment_id']]['item_order'][$key]['od_axis'] = $value['od_axis'];
            $data[$value['increment_id']]['item_order'][$key]['lens_number'] = $value['lens_number'];
            $data[$value['increment_id']]['item_order'][$key]['web_lens_name'] = $value['web_lens_name'] ?: $value['index_name'];
            $data[$value['increment_id']]['item_order'][$key]['product_id'] = $value['product_id'];
            $data[$value['increment_id']]['base_grand_total'] = $value['base_grand_total'];
            $data[$value['increment_id']]['base_currency_code'] = $value['base_currency_code'];
            $data[$value['increment_id']]['base_grand_total'] = $value['base_grand_total'];
            $data[$value['increment_id']]['payment_method'] = $value['payment_method'];
            $data[$value['increment_id']]['payment_time'] = $value['payment_time'];
            $data[$value['increment_id']]['check_time'] = $value['check_time'];
        }
        dump($data);die();
        unset($value);
        $cat = '0';
        foreach ($data as  $key => &$value) {
            $num = $cat + 2;


            //网站SKU转换仓库SKU
            $value['prescription_type'] = isset($value['prescription_type']) ? $value['prescription_type'] : '';

            $spreadsheet->getActiveSheet()->setCellValue("A" . ($num), $value['id']); //id
            $spreadsheet->getActiveSheet()->setCellValue("B" . ($num), date('Y-m-d', $value['created_at'])); //日期
            $spreadsheet->getActiveSheet()->setCellValue("C" . ($num), $value['increment_id']); //订单号
            $spreadsheet->getActiveSheet()->setCellValue("D" . ($num), $site_list[$value['site']]); //站点
            switch ($value['order_type']) {
                case 1:
                    $value['order_type'] = '普通订单';
                    break;
                case 2:
                    $value['order_type'] = '批发';
                    break;
                case 3:
                    $value['order_type'] = '网红';
                    break;
                case 4:
                    $value['order_type'] = '补发';
                    break;
                case 5:
                    $value['order_type'] = '补差价';
                    break;
                case 6:
                    $value['order_type'] = '一件代发';
                    break;
            }
            $spreadsheet->getActiveSheet()->setCellValue("E" . ($num), $value['order_type']); //订单类型
            $spreadsheet->getActiveSheet()->setCellValue("F" . ($num), $value['status']); //订单状态
            foreach ($value['item_order'] as $k => $v) {
                $v['od_sph'] = isset($v['od_sph']) ? urldecode($v['od_sph']) : '';
                $v['os_sph'] = isset($v['os_sph']) ? urldecode($v['os_sph']) : '';
                $v['od_cyl'] = isset($v['od_cyl']) ? urldecode($v['od_cyl']) : '';
                $v['os_cyl'] = isset($v['os_cyl']) ? urldecode($v['os_cyl']) : '';
                $cat += 2;
                $spreadsheet->getActiveSheet()->setCellValue("G" . ($cat), $v['item_order_number']); //子单号
                $spreadsheet->getActiveSheet()->setCellValue("H" . ($cat), $v['sku']); //sku
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($cat), '右眼'); //眼球
                $spreadsheet->getActiveSheet()->setCellValue("I" . ($cat + 1), '左眼'); //眼球
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($cat), (float)$v['od_sph'] > 0 ? ' +' . number_format($v['od_sph'] * 1, 2) : ' ' . $v['od_sph']); //SPH
                $spreadsheet->getActiveSheet()->setCellValue("J" . ($cat + 1), (float)$v['os_sph'] > 0 ? ' +' . number_format($v['os_sph'] * 1, 2) : ' ' . $v['os_sph']); //SPH
                $spreadsheet->getActiveSheet()->setCellValue("K" . ($cat), (float)$v['od_cyl'] > 0 ? ' +' . number_format($v['od_cyl'] * 1, 2) : ' ' . $v['od_cyl']); //CYL
                $spreadsheet->getActiveSheet()->setCellValue("K" . ($cat + 1), (float)$v['os_cyl'] > 0 ? ' +' . number_format($v['os_cyl'] * 1, 2) : ' ' . $v['os_cyl']); //CYL
                $spreadsheet->getActiveSheet()->setCellValue("L" . ($cat), $v['od_axis']); //AXI
                $spreadsheet->getActiveSheet()->setCellValue("L" . ($cat + 1), $v['os_axis']); //AXI
                $v['os_add'] = urldecode($v['os_add']);
                $v['od_add'] = urldecode($v['od_add']);

                if ($v['os_add'] && $v['od_add'] && (float)($v['os_add']) * 1 != 0 && (float)($v['od_add']) * 1 != 0) {
                    $spreadsheet->getActiveSheet()->setCellValue("M" . ($cat), $v['od_add']); //ADD
                    $spreadsheet->getActiveSheet()->setCellValue("M" . ($cat + 1), $v['os_add']); //ADD
                } else {

                    if ($v['os_add'] && (float)$v['os_add'] * 1 != 0) {
                        //数值在上一行合并有效，数值在下一行合并后为空
                        $spreadsheet->getActiveSheet()->setCellValue("M" . ($cat), $v['os_add']);
                        $spreadsheet->getActiveSheet()->mergeCells("M" . ($cat) . ":M" . ($cat + 1));
                    } else {
                        //数值在上一行合并有效，数值在下一行合并后为空
                        $spreadsheet->getActiveSheet()->setCellValue("M" . ($cat), $v['od_add']);
                        $spreadsheet->getActiveSheet()->mergeCells("M" . ($cat) . ":M" . ($cat + 1));
                    }
                }

                //            $spreadsheet->getActiveSheet()->setCellValue("C" . ($key * 2 + 2), $value['item_order_number']); //子单号
                //            $spreadsheet->getActiveSheet()->setCellValue("D" . ($key * 2 + 2), $value['sku']); //sku
                //            $spreadsheet->getActiveSheet()->setCellValue("E" . ($key * 2 + 2), $site_list[$value['site']]);//站点
                //            $spreadsheet->getActiveSheet()->setCellValue("F" . ($key * 2 + 2), $distribution_status_list[$value['distribution_status']]);//子单号状态



                if ($v['pdcheck'] == 'on') {
                    $spreadsheet->getActiveSheet()->setCellValue("N" . ($cat), $v['pd_r']); //单PD
                    $spreadsheet->getActiveSheet()->setCellValue("N" . ($cat + 1), $v['pd_l']); //单PD
                } else {
                    $spreadsheet->getActiveSheet()->setCellValue("N" . ($cat), $v['pd']); //PD
                    $spreadsheet->getActiveSheet()->mergeCells("N" . ($cat) . ":N" . ($cat + 1)); //PD
                }

                $lens_name = $lens_list[$v['lens_number']] ?: $v['web_lens_name'];
                $spreadsheet->getActiveSheet()->setCellValue("O" . ($cat), $lens_name); //镜片
                $spreadsheet->getActiveSheet()->setCellValue("P" . ($cat), $v['lens_width']); //镜框宽度
                $spreadsheet->getActiveSheet()->setCellValue("Q" . ($cat), $v['lens_height']); //镜框高度
                $spreadsheet->getActiveSheet()->setCellValue("R" . ($cat), $v['bridge']); //bridge
                $spreadsheet->getActiveSheet()->setCellValue("S" . ($cat), $v['prescription_type']); //处方类型

                $spreadsheet->getActiveSheet()->setCellValue("T" . ($cat), isset($v['od_pv']) ? $v['od_pv'] : ''); //Prism
                $spreadsheet->getActiveSheet()->setCellValue("T" . ($cat + 1), isset($v['os_pv']) ? $v['os_pv'] : ''); //Prism

                $spreadsheet->getActiveSheet()->setCellValue("U" . ($cat), isset($v['od_bd']) ? $v['od_bd'] : ''); //Direct
                $spreadsheet->getActiveSheet()->setCellValue("U" . ($cat + 1), isset($v['os_bd']) ? $v['os_bd'] : ''); //Direct

                $spreadsheet->getActiveSheet()->setCellValue("V" . ($cat), isset($v['od_pv_r']) ? $v['od_pv_r'] : ''); //Prism
                $spreadsheet->getActiveSheet()->setCellValue("V" . ($cat + 1), isset($v['os_pv_r']) ? $v['os_pv_r'] : ''); //Prism

                $spreadsheet->getActiveSheet()->setCellValue("W" . ($cat), isset($v['od_bd_r']) ? $v['od_bd_r'] : ''); //Direct
                $spreadsheet->getActiveSheet()->setCellValue("W" . ($cat + 1), isset($v['os_bd_r']) ? $v['os_bd_r'] : ''); //Direct
                //单元格合并
                $spreadsheet->getActiveSheet()->mergeCells("G" . ($cat) . ":G" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("H" . ($cat) . ":H" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("O" . ($cat) . ":O" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("P" . ($cat) . ":P" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("Q" . ($cat) . ":Q" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("R" . ($cat) . ":R" . ($cat + 1));
                $spreadsheet->getActiveSheet()->mergeCells("S" . ($cat) . ":S" . ($cat + 1));
            }


            $spreadsheet->getActiveSheet()->setCellValue("X" . ($num), $value['base_grand_total']); //订单金额
            $spreadsheet->getActiveSheet()->setCellValue("Y" . ($num), $value['base_currency_code']); //原币种
            $spreadsheet->getActiveSheet()->setCellValue("Z" . ($num), $value['base_grand_total']); //原支付金额
            $spreadsheet->getActiveSheet()->setCellValue("AA" . ($num), $value['payment_method']); //支付方式
            $spreadsheet->getActiveSheet()->setCellValue("AB" . ($num),  date('Y-m-d H:i:s', $value['payment_time'])); //订单支付时间

            $spreadsheet->getActiveSheet()->setCellValue("AC" . ($num),  $value['check_time'] ); //审单时间

            //合并单元格

            $spreadsheet->getActiveSheet()->mergeCells("A" . ($num) . ":A" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("B" . ($num) . ":B" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("C" . ($num) . ":C" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("D" . ($num) . ":D" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("E" . ($num) . ":E" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("F" . ($num) . ":F" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("G" . ($num) . ":G" . ($num + 1));
            $spreadsheet->getActiveSheet()->mergeCells("H" . ($num) . ":H" . ($num + 1));


            $spreadsheet->getActiveSheet()->mergeCells("X" . ($num) . ":X" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("Y" . ($num) . ":Y" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("Z" . ($num) . ":Z" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("AA" . ($num) . ":AA" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("AB" . ($num) . ":AB" . ($cat + 1));
            $spreadsheet->getActiveSheet()->mergeCells("AC" . ($num) . ":AC" . ($cat + 1));
        }

        //设置宽度
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('S')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('T')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('U')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('V')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('AA')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('AB')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('AC')->setWidth(30);
        //自动换行
        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑')->setSize(12);

        //设置边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, // 设置border样式
                    'color' => ['argb' => 'FF000000'], // 设置border颜色
                ],
            ],
        ];


        $setBorder = 'A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . $spreadsheet->getActiveSheet()->getHighestRow();
        $spreadsheet->getActiveSheet()->getStyle($setBorder)->applyFromArray($border);

        $spreadsheet->getActiveSheet()->getStyle('A1:AC' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:AC' . $spreadsheet->getActiveSheet()->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $spreadsheet->setActiveSheetIndex(0);

        $save_name = '配货列表' . date("YmdHis", time());
        //输出07Excel版本
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        //输出名称
        header('Content-Disposition: attachment;filename="' . $save_name . '.xlsx"');
        //禁止缓存
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        $headlist = [
            '订单号', '站点', '订单类型', '订单状态', '子单号',
            'SKU', 'SPH-L', 'SPH-R', 'CYL-L', 'CYL-R',
            'AXI-L', 'AXL-R', 'ADD-L', 'ADD-R', 'PD-L',
            'PD-R', '镜片', '镜框宽度', '镜框高度', 'bridge',
            '处方类型', 'Prism(out/in)-L', 'Prism(out/in)-R', 'Direct(out/in)-L', 'Direct(out/in)-R',
            'Prism(up/down)-L', 'Prism(up/down)-R', 'Direct(up/down)-L', 'Direct(up/down)-R', '订单金额',
            '原币种', '原支付金额', '支付方式', '订单支付时间', '审单时间',
        ];

        $path = "/uploads/";
        $fileName = 'Zeelool站配货列表十二月份数据';
        Excel::writeCsv($csv, $headlist, $path . $fileName);


    }



}
