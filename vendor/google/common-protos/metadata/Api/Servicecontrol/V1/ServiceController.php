<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/api/servicecontrol/v1/service_controller.proto

namespace GPBMetadata\Google\Api\Servicecontrol\V1;

class ServiceController
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Api\Servicecontrol\V1\CheckError::initOnce();
        \GPBMetadata\Google\Api\Servicecontrol\V1\Operation::initOnce();
        \GPBMetadata\Google\Rpc\Status::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0af50b0a35676f6f676c652f6170692f73657276696365636f6e74726f6c" .
            "2f76312f736572766963655f636f6e74726f6c6c65722e70726f746f121c" .
            "676f6f676c652e6170692e73657276696365636f6e74726f6c2e76311a2e" .
            "676f6f676c652f6170692f73657276696365636f6e74726f6c2f76312f63" .
            "6865636b5f6572726f722e70726f746f1a2c676f6f676c652f6170692f73" .
            "657276696365636f6e74726f6c2f76312f6f7065726174696f6e2e70726f" .
            "746f1a17676f6f676c652f7270632f7374617475732e70726f746f227b0a" .
            "0c436865636b5265717565737412140a0c736572766963655f6e616d6518" .
            "0120012809123a0a096f7065726174696f6e18022001280b32272e676f6f" .
            "676c652e6170692e73657276696365636f6e74726f6c2e76312e4f706572" .
            "6174696f6e12190a11736572766963655f636f6e6669675f696418042001" .
            "280922ed020a0d436865636b526573706f6e736512140a0c6f7065726174" .
            "696f6e5f6964180120012809121a0a12736572766963655f726f6c6c6f75" .
            "745f6964180b20012809123e0a0c636865636b5f6572726f727318022003" .
            "280b32282e676f6f676c652e6170692e73657276696365636f6e74726f6c" .
            "2e76312e436865636b4572726f7212190a11736572766963655f636f6e66" .
            "69675f696418052001280912490a0a636865636b5f696e666f1806200128" .
            "0b32352e676f6f676c652e6170692e73657276696365636f6e74726f6c2e" .
            "76312e436865636b526573706f6e73652e436865636b496e666f1a5c0a09" .
            "436865636b496e666f124f0a0d636f6e73756d65725f696e666f18022001" .
            "280b32382e676f6f676c652e6170692e73657276696365636f6e74726f6c" .
            "2e76312e436865636b526573706f6e73652e436f6e73756d6572496e666f" .
            "1a260a0c436f6e73756d6572496e666f12160a0e70726f6a6563745f6e75" .
            "6d626572180120012803227d0a0d5265706f72745265717565737412140a" .
            "0c736572766963655f6e616d65180120012809123b0a0a6f706572617469" .
            "6f6e7318022003280b32272e676f6f676c652e6170692e73657276696365" .
            "636f6e74726f6c2e76312e4f7065726174696f6e12190a11736572766963" .
            "655f636f6e6669675f696418032001280922e1010a0e5265706f72745265" .
            "73706f6e7365124f0a0d7265706f72745f6572726f727318012003280b32" .
            "382e676f6f676c652e6170692e73657276696365636f6e74726f6c2e7631" .
            "2e5265706f7274526573706f6e73652e5265706f72744572726f7212190a" .
            "11736572766963655f636f6e6669675f6964180220012809121a0a127365" .
            "72766963655f726f6c6c6f75745f69641804200128091a470a0b5265706f" .
            "72744572726f7212140a0c6f7065726174696f6e5f696418012001280912" .
            "220a0673746174757318022001280b32122e676f6f676c652e7270632e53" .
            "746174757332b9020a1153657276696365436f6e74726f6c6c6572128e01" .
            "0a05436865636b122a2e676f6f676c652e6170692e73657276696365636f" .
            "6e74726f6c2e76312e436865636b526571756573741a2b2e676f6f676c65" .
            "2e6170692e73657276696365636f6e74726f6c2e76312e436865636b5265" .
            "73706f6e7365222c82d3e493022622212f76312f73657276696365732f7b" .
            "736572766963655f6e616d657d3a636865636b3a012a1292010a06526570" .
            "6f7274122b2e676f6f676c652e6170692e73657276696365636f6e74726f" .
            "6c2e76312e5265706f7274526571756573741a2c2e676f6f676c652e6170" .
            "692e73657276696365636f6e74726f6c2e76312e5265706f727452657370" .
            "6f6e7365222d82d3e493022722222f76312f73657276696365732f7b7365" .
            "72766963655f6e616d657d3a7265706f72743a012a4292010a20636f6d2e" .
            "676f6f676c652e6170692e73657276696365636f6e74726f6c2e76314216" .
            "53657276696365436f6e74726f6c6c657250726f746f50015a4a676f6f67" .
            "6c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c6561" .
            "7069732f6170692f73657276696365636f6e74726f6c2f76313b73657276" .
            "696365636f6e74726f6cf80101a2020447415343620670726f746f33"
        ), true);

        static::$is_initialized = true;
    }
}

