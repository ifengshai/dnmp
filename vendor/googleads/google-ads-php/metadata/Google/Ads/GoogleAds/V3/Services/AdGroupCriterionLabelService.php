<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/ad_group_criterion_label_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Services;

class AdGroupCriterionLabelService
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
        \GPBMetadata\Google\Protobuf\Any::initOnce();
        \GPBMetadata\Google\Rpc\Status::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0af8060a40676f6f676c652f6164732f676f6f676c656164732f76332f7265736f75726365732f61645f67726f75705f637269746572696f6e5f6c6162656c2e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365731a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22a5030a15416447726f7570437269746572696f6e4c6162656c124d0a0d7265736f757263655f6e616d651801200128094236e04105fa41300a2e676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570437269746572696f6e4c6162656c126b0a1261645f67726f75705f637269746572696f6e18022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654231e04105fa412b0a29676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570437269746572696f6e12530a056c6162656c18032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654226e04105fa41200a1e676f6f676c656164732e676f6f676c65617069732e636f6d2f4c6162656c3a7bea41780a2e676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570437269746572696f6e4c6162656c1246637573746f6d6572732f7b637573746f6d65727d2f616447726f7570437269746572696f6e4c6162656c732f7b61645f67726f75705f637269746572696f6e5f6c6162656c7d4287020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f7572636573421a416447726f7570437269746572696f6e4c6162656c50726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56332e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56335c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5265736f7572636573620670726f746f330a800e0a47676f6f676c652f6164732f676f6f676c656164732f76332f73657276696365732f61645f67726f75705f637269746572696f6e5f6c6162656c5f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a17676f6f676c652f7270632f7374617475732e70726f746f22700a1f476574416447726f7570437269746572696f6e4c6162656c52657175657374124d0a0d7265736f757263655f6e616d651801200128094236e04102fa41300a2e676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570437269746572696f6e4c6162656c22ca010a234d7574617465416447726f7570437269746572696f6e4c6162656c735265717565737412180a0b637573746f6d65725f69641801200128094203e0410212590a0a6f7065726174696f6e7318022003280b32402e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e416447726f7570437269746572696f6e4c6162656c4f7065726174696f6e4203e0410212170a0f7061727469616c5f6661696c75726518032001280812150a0d76616c69646174655f6f6e6c79180420012808228b010a1e416447726f7570437269746572696f6e4c6162656c4f7065726174696f6e124a0a0663726561746518012001280b32382e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e416447726f7570437269746572696f6e4c6162656c480012100a0672656d6f76651802200128094800420b0a096f7065726174696f6e22af010a244d7574617465416447726f7570437269746572696f6e4c6162656c73526573706f6e736512310a157061727469616c5f6661696c7572655f6572726f7218032001280b32122e676f6f676c652e7270632e53746174757312540a07726573756c747318022003280b32432e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d7574617465416447726f7570437269746572696f6e4c6162656c526573756c74223a0a214d7574617465416447726f7570437269746572696f6e4c6162656c526573756c7412150a0d7265736f757263655f6e616d6518012001280932b8040a1c416447726f7570437269746572696f6e4c6162656c5365727669636512e9010a18476574416447726f7570437269746572696f6e4c6162656c12412e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e476574416447726f7570437269746572696f6e4c6162656c526571756573741a382e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e416447726f7570437269746572696f6e4c6162656c225082d3e493023a12382f76332f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f616447726f7570437269746572696f6e4c6162656c732f2a7dda410d7265736f757263655f6e616d65128e020a1c4d7574617465416447726f7570437269746572696f6e4c6162656c7312452e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d7574617465416447726f7570437269746572696f6e4c6162656c73526571756573741a462e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d7574617465416447726f7570437269746572696f6e4c6162656c73526573706f6e7365225f82d3e4930240223b2f76332f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f616447726f7570437269746572696f6e4c6162656c733a6d75746174653a012ada4116637573746f6d65725f69642c6f7065726174696f6e731a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d4288020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365734221416447726f7570437269746572696f6e4c6162656c5365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56332e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56335c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

