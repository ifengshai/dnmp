<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/services/ad_parameter_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V4\Services;

class AdParameterService
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Protobuf\Wrappers::initOnce();
        \GPBMetadata\Google\Api\Http::initOnce();
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Api\FieldBehavior::initOnce();
        \GPBMetadata\Google\Api\Resource::initOnce();
        \GPBMetadata\Google\Api\Client::initOnce();
        \GPBMetadata\Google\Protobuf\FieldMask::initOnce();
        \GPBMetadata\Google\Protobuf\Any::initOnce();
        \GPBMetadata\Google\Rpc\Status::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0aca060a34676f6f676c652f6164732f676f6f676c656164732f76342f7265736f75726365732f61645f706172616d657465722e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365731a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f228d030a0b4164506172616d6574657212430a0d7265736f757263655f6e616d65180120012809422ce04105fa41260a24676f6f676c656164732e676f6f676c65617069732e636f6d2f4164506172616d65746572126b0a1261645f67726f75705f637269746572696f6e18022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654231e04105fa412b0a29676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570437269746572696f6e12390a0f706172616d657465725f696e64657818032001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410512340a0e696e73657274696f6e5f7465787418042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75653a5bea41580a24676f6f676c656164732e676f6f676c65617069732e636f6d2f4164506172616d657465721230637573746f6d6572732f7b637573746f6d65727d2f6164506172616d65746572732f7b61645f706172616d657465727d42fd010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f757263657342104164506172616d6574657250726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56342e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56345c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a5265736f7572636573620670726f746f330acb0d0a3b676f6f676c652f6164732f676f6f676c656164732f76342f73657276696365732f61645f706172616d657465725f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a20676f6f676c652f70726f746f6275662f6669656c645f6d61736b2e70726f746f1a17676f6f676c652f7270632f7374617475732e70726f746f225c0a154765744164506172616d657465725265717565737412430a0d7265736f757263655f6e616d65180120012809422ce04102fa41260a24676f6f676c656164732e676f6f676c65617069732e636f6d2f4164506172616d6574657222b6010a194d75746174654164506172616d65746572735265717565737412180a0b637573746f6d65725f69641801200128094203e04102124f0a0a6f7065726174696f6e7318022003280b32362e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4164506172616d657465724f7065726174696f6e4203e0410212170a0f7061727469616c5f6661696c75726518032001280812150a0d76616c69646174655f6f6e6c7918042001280822ea010a144164506172616d657465724f7065726174696f6e122f0a0b7570646174655f6d61736b18042001280b321a2e676f6f676c652e70726f746f6275662e4669656c644d61736b12400a0663726561746518012001280b322e2e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e4164506172616d65746572480012400a0675706461746518022001280b322e2e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e4164506172616d65746572480012100a0672656d6f76651803200128094800420b0a096f7065726174696f6e229b010a1a4d75746174654164506172616d6574657273526573706f6e736512310a157061727469616c5f6661696c7572655f6572726f7218032001280b32122e676f6f676c652e7270632e537461747573124a0a07726573756c747318022003280b32392e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4d75746174654164506172616d65746572526573756c7422300a174d75746174654164506172616d65746572526573756c7412150a0d7265736f757263655f6e616d6518012001280932de030a124164506172616d657465725365727669636512c1010a0e4765744164506172616d6574657212372e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4765744164506172616d65746572526571756573741a2e2e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e4164506172616d65746572224682d3e4930230122e2f76342f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f6164506172616d65746572732f2a7dda410d7265736f757263655f6e616d6512e6010a124d75746174654164506172616d6574657273123b2e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4d75746174654164506172616d6574657273526571756573741a3c2e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4d75746174654164506172616d6574657273526573706f6e7365225582d3e493023622312f76342f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f6164506172616d65746572733a6d75746174653a012ada4116637573746f6d65725f69642c6f7065726174696f6e731a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d42fe010a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e736572766963657342174164506172616d657465725365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56342e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56345c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

