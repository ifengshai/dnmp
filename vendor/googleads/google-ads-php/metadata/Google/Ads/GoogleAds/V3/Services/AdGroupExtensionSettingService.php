<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/ad_group_extension_setting_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Services;

class AdGroupExtensionSettingService
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
            "0ac7030a3c676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f657874656e73696f6e5f73657474696e675f6465766963652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73226d0a1a457874656e73696f6e53657474696e67446576696365456e756d224f0a16457874656e73696f6e53657474696e67446576696365120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120a0a064d4f42494c451002120b0a074445534b544f50100342f0010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73421b457874656e73696f6e53657474696e6744657669636550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330abb040a32676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f657874656e73696f6e5f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322f3010a11457874656e73696f6e54797065456e756d22dd010a0d457874656e73696f6e54797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112080a044e4f4e45100212070a03415050100312080a0443414c4c1004120b0a0743414c4c4f55541005120b0a074d455353414745100612090a0550524943451007120d0a0950524f4d4f54494f4e1008120c0a08534954454c494e4b100a12160a12535452554354555245445f534e4950504554100b120c0a084c4f434154494f4e100c12160a12414646494c494154455f4c4f434154494f4e100d12110a0d484f54454c5f43414c4c4f5554100f42e7010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d734212457874656e73696f6e5479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330aa0090a42676f6f676c652f6164732f676f6f676c656164732f76332f7265736f75726365732f61645f67726f75705f657874656e73696f6e5f73657474696e672e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365731a32676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f657874656e73696f6e5f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22f4040a17416447726f7570457874656e73696f6e53657474696e67124f0a0d7265736f757263655f6e616d651801200128094238e04105fa41320a30676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570457874656e73696f6e53657474696e67125b0a0e657874656e73696f6e5f7479706518022001280e323e2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e457874656e73696f6e54797065456e756d2e457874656e73696f6e547970654203e0410512580a0861645f67726f757018032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654228e04105fa41220a20676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570126b0a14657874656e73696f6e5f666565645f6974656d7318042003280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565422ffa412c0a2a676f6f676c656164732e676f6f676c65617069732e636f6d2f457874656e73696f6e466565644974656d12600a0664657669636518052001280e32502e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e457874656e73696f6e53657474696e67446576696365456e756d2e457874656e73696f6e53657474696e674465766963653a8101ea417e0a30676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570457874656e73696f6e53657474696e67124a637573746f6d6572732f7b637573746f6d65727d2f616447726f7570457874656e73696f6e53657474696e67732f7b61645f67726f75705f657874656e73696f6e5f73657474696e677d4289020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f7572636573421c416447726f7570457874656e73696f6e53657474696e6750726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56332e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56335c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5265736f7572636573620670726f746f330ac90f0a49676f6f676c652f6164732f676f6f676c656164732f76332f73657276696365732f61645f67726f75705f657874656e73696f6e5f73657474696e675f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a20676f6f676c652f70726f746f6275662f6669656c645f6d61736b2e70726f746f1a17676f6f676c652f7270632f7374617475732e70726f746f22740a21476574416447726f7570457874656e73696f6e53657474696e6752657175657374124f0a0d7265736f757263655f6e616d651801200128094238e04102fa41320a30676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570457874656e73696f6e53657474696e6722ce010a254d7574617465416447726f7570457874656e73696f6e53657474696e67735265717565737412180a0b637573746f6d65725f69641801200128094203e04102125b0a0a6f7065726174696f6e7318022003280b32422e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e416447726f7570457874656e73696f6e53657474696e674f7065726174696f6e4203e0410212170a0f7061727469616c5f6661696c75726518032001280812150a0d76616c69646174655f6f6e6c79180420012808228e020a20416447726f7570457874656e73696f6e53657474696e674f7065726174696f6e122f0a0b7570646174655f6d61736b18042001280b321a2e676f6f676c652e70726f746f6275662e4669656c644d61736b124c0a0663726561746518012001280b323a2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e416447726f7570457874656e73696f6e53657474696e674800124c0a0675706461746518022001280b323a2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e416447726f7570457874656e73696f6e53657474696e67480012100a0672656d6f76651803200128094800420b0a096f7065726174696f6e22b3010a264d7574617465416447726f7570457874656e73696f6e53657474696e6773526573706f6e736512310a157061727469616c5f6661696c7572655f6572726f7218032001280b32122e676f6f676c652e7270632e53746174757312560a07726573756c747318022003280b32452e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d7574617465416447726f7570457874656e73696f6e53657474696e67526573756c74223c0a234d7574617465416447726f7570457874656e73696f6e53657474696e67526573756c7412150a0d7265736f757263655f6e616d6518012001280932ca040a1e416447726f7570457874656e73696f6e53657474696e675365727669636512f1010a1a476574416447726f7570457874656e73696f6e53657474696e6712432e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e476574416447726f7570457874656e73696f6e53657474696e67526571756573741a3a2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e416447726f7570457874656e73696f6e53657474696e67225282d3e493023c123a2f76332f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f616447726f7570457874656e73696f6e53657474696e67732f2a7dda410d7265736f757263655f6e616d651296020a1e4d7574617465416447726f7570457874656e73696f6e53657474696e677312472e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d7574617465416447726f7570457874656e73696f6e53657474696e6773526571756573741a482e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d7574617465416447726f7570457874656e73696f6e53657474696e6773526573706f6e7365226182d3e4930242223d2f76332f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f616447726f7570457874656e73696f6e53657474696e67733a6d75746174653a012ada4116637573746f6d65725f69642c6f7065726174696f6e731a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d428a020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365734223416447726f7570457874656e73696f6e53657474696e675365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56332e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56335c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

