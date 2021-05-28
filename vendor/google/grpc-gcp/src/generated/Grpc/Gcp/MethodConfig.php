<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: grpc_gcp.proto

namespace Grpc\Gcp;

use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>grpc.gcp.MethodConfig</code>
 */
class MethodConfig extends \Google\Protobuf\Internal\Message
{
    /**
     * A fully qualified name of a gRPC method, or a wildcard pattern ending
     * with .*, such as foo.bar.A, foo.bar.*. Method configs are evaluated
     * sequentially, and the first one takes precedence.
     *
     * Generated from protobuf field <code>repeated string name = 1;</code>
     */
    private $name;
    /**
     * The channel affinity configurations.
     *
     * Generated from protobuf field <code>.grpc.gcp.AffinityConfig affinity = 1001;</code>
     */
    private $affinity = null;

    public function __construct()
    {
        \GPBMetadata\GrpcGcp::initOnce();
        parent::__construct();
    }

    /**
     * A fully qualified name of a gRPC method, or a wildcard pattern ending
     * with .*, such as foo.bar.A, foo.bar.*. Method configs are evaluated
     * sequentially, and the first one takes precedence.
     *
     * Generated from protobuf field <code>repeated string name = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * A fully qualified name of a gRPC method, or a wildcard pattern ending
     * with .*, such as foo.bar.A, foo.bar.*. Method configs are evaluated
     * sequentially, and the first one takes precedence.
     *
     * Generated from protobuf field <code>repeated string name = 1;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setName($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->name = $arr;

        return $this;
    }

    /**
     * The channel affinity configurations.
     *
     * Generated from protobuf field <code>.grpc.gcp.AffinityConfig affinity = 1001;</code>
     * @return \Grpc\Gcp\AffinityConfig
     */
    public function getAffinity()
    {
        return $this->affinity;
    }

    /**
     * The channel affinity configurations.
     *
     * Generated from protobuf field <code>.grpc.gcp.AffinityConfig affinity = 1001;</code>
     * @param \Grpc\Gcp\AffinityConfig $var
     * @return $this
     */
    public function setAffinity($var)
    {
        GPBUtil::checkMessage($var, \Grpc\Gcp\AffinityConfig::class);
        $this->affinity = $var;

        return $this;
    }
}
