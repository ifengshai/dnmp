<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: grpc_gcp.proto

namespace Grpc\Gcp;

use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>grpc.gcp.ChannelPoolConfig</code>
 */
class ChannelPoolConfig extends \Google\Protobuf\Internal\Message
{
    /**
     * The max number of channels in the pool.
     *
     * Generated from protobuf field <code>uint32 max_size = 1;</code>
     */
    private $max_size = 0;
    /**
     * The idle timeout (seconds) of channels without bound affinity sessions.
     *
     * Generated from protobuf field <code>uint64 idle_timeout = 2;</code>
     */
    private $idle_timeout = 0;
    /**
     * The low watermark of max number of concurrent streams in a channel.
     * New channel will be created once it get hit, until we reach the max size
     * of the channel pool.
     *
     * Generated from protobuf field <code>uint32 max_concurrent_streams_low_watermark = 3;</code>
     */
    private $max_concurrent_streams_low_watermark = 0;

    public function __construct()
    {
        \GPBMetadata\GrpcGcp::initOnce();
        parent::__construct();
    }

    /**
     * The max number of channels in the pool.
     *
     * Generated from protobuf field <code>uint32 max_size = 1;</code>
     * @return int
     */
    public function getMaxSize()
    {
        return $this->max_size;
    }

    /**
     * The max number of channels in the pool.
     *
     * Generated from protobuf field <code>uint32 max_size = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setMaxSize($var)
    {
        GPBUtil::checkUint32($var);
        $this->max_size = $var;

        return $this;
    }

    /**
     * The idle timeout (seconds) of channels without bound affinity sessions.
     *
     * Generated from protobuf field <code>uint64 idle_timeout = 2;</code>
     * @return int|string
     */
    public function getIdleTimeout()
    {
        return $this->idle_timeout;
    }

    /**
     * The idle timeout (seconds) of channels without bound affinity sessions.
     *
     * Generated from protobuf field <code>uint64 idle_timeout = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setIdleTimeout($var)
    {
        GPBUtil::checkUint64($var);
        $this->idle_timeout = $var;

        return $this;
    }

    /**
     * The low watermark of max number of concurrent streams in a channel.
     * New channel will be created once it get hit, until we reach the max size
     * of the channel pool.
     *
     * Generated from protobuf field <code>uint32 max_concurrent_streams_low_watermark = 3;</code>
     * @return int
     */
    public function getMaxConcurrentStreamsLowWatermark()
    {
        return $this->max_concurrent_streams_low_watermark;
    }

    /**
     * The low watermark of max number of concurrent streams in a channel.
     * New channel will be created once it get hit, until we reach the max size
     * of the channel pool.
     *
     * Generated from protobuf field <code>uint32 max_concurrent_streams_low_watermark = 3;</code>
     * @param int $var
     * @return $this
     */
    public function setMaxConcurrentStreamsLowWatermark($var)
    {
        GPBUtil::checkUint32($var);
        $this->max_concurrent_streams_low_watermark = $var;

        return $this;
    }
}
