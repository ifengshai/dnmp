<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/rpc/status.proto

namespace GPBMetadata\Google\Rpc;

class Status
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Protobuf\Any::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ae0010a17676f6f676c652f7270632f7374617475732e70726f746f120a" .
            "676f6f676c652e727063224e0a06537461747573120c0a04636f64651801" .
            "20012805120f0a076d65737361676518022001280912250a076465746169" .
            "6c7318032003280b32142e676f6f676c652e70726f746f6275662e416e79" .
            "42610a0e636f6d2e676f6f676c652e727063420b53746174757350726f74" .
            "6f50015a37676f6f676c652e676f6c616e672e6f72672f67656e70726f74" .
            "6f2f676f6f676c65617069732f7270632f7374617475733b737461747573" .
            "f80101a20203525043620670726f746f33"
        ), true);

        static::$is_initialized = true;
    }
}

