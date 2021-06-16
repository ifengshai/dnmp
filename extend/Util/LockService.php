<?php

namespace Util;


use Redis;

/**
 * Class Lock_Service 单据锁服务
 */
class LockService
{
    /**
     * 单据锁redis key模板
     */
    const REDIS_LOCK_KEY_TEMPLATE = 'order_lock_%s';

    /**
     * 单据锁默认超时时间（秒）
     */
    const REDIS_LOCK_DEFAULT_EXPIRE_TIME = 30;

    /**
     * 加单据锁
     * 安卓前端做了相关限制，还是有可能因为网络或异常操作产生并发重复调用的情况 2021-06-16 实体仓调拨单重复提交5次 导致入库数量错误
     *
     * @param int $intOrderId    单据ID
     * @param int $intExpireTime 锁过期时间（秒）
     *
     * @return bool|int 加锁成功返回唯一锁ID，加锁失败返回false
     */
    public static function addLock(int $intOrderId, int $intExpireTime = self::REDIS_LOCK_DEFAULT_EXPIRE_TIME)
    {
        //参数校验
        if (empty($intOrderId) || $intExpireTime <= 0) {
            return false;
        }

        //获取Redis连接
        $objRedisConn = self::getRedisConn();

        //生成唯一锁ID，解锁需持有此ID
        $intUniqueLockId = self::generateUniqueLockId();

        //根据模板，结合单据ID，生成唯一Redis key（一般来说，单据ID在业务中系统中唯一的）
        $strKey = sprintf(self::REDIS_LOCK_KEY_TEMPLATE, $intOrderId);

        //加锁（通过Redis setnx指令实现，从Redis 2.6.12开始，通过set指令可选参数也可以实现setnx，同时可原子化地设置超时时间）
        $bolRes = $objRedisConn->set($strKey, $intUniqueLockId, ['nx', 'ex' => $intExpireTime]);

        //加锁成功返回锁ID，加锁失败返回false
        return $bolRes ? $intUniqueLockId : $bolRes;
    }

    /**
     * 解单据锁
     *
     * @param int $intOrderId 单据ID
     * @param int $intLockId  锁唯一ID
     *
     * @return bool
     */
    public static function releaseLock(int $intOrderId, int $intLockId): bool
    {
        //参数校验
        if (empty($intOrderId) || empty($intLockId)) {
            return false;
        }

        //获取Redis连接
        $objRedisConn = self::getRedisConn();

        //生成Redis key
        $strKey = sprintf(self::REDIS_LOCK_KEY_TEMPLATE, $intOrderId);

        //监听Redis key防止在【比对lock id】与【解锁事务执行过程中】被修改或删除，提交事务后会自动取消监控，其他情况需手动解除监控
        $objRedisConn->watch($strKey);
        if ($intLockId == $objRedisConn->get($strKey)) {
            $objRedisConn->multi()->del($strKey)->exec();

            return true;
        }
        $objRedisConn->unwatch();

        return false;
    }

    /**
     * Redis配置：IP
     */
    const REDIS_CONFIG_HOST = '127.0.0.1';

    /**
     * Redis配置：端口
     */
    const REDIS_CONFIG_PORT = 6379;

    /**
     * 获取Redis连接
     *
     * @param string $strIp   IP
     * @param int    $intPort 端口
     *
     * @return object Redis连接
     */
    public static function getRedisConn(string $strIp = self::REDIS_CONFIG_HOST, int $intPort = self::REDIS_CONFIG_PORT)
    {
        $objRedis = new Redis();
        $objRedis->connect($strIp, $intPort);

        return $objRedis;
    }

    /**
     * 用于生成唯一的锁ID的redis key
     */
    const REDIS_LOCK_UNIQUE_ID_KEY = 'lock_unique_id';

    /**
     * 生成锁唯一ID（通过Redis incr指令实现简易版本，可结合日期、时间戳、取余、字符串填充、随机数等函数，生成指定位数唯一ID）
     * @return mixed
     */
    public static function generateUniqueLockId()
    {
        return self::getRedisConn()->incr(self::REDIS_LOCK_UNIQUE_ID_KEY);
    }
}
