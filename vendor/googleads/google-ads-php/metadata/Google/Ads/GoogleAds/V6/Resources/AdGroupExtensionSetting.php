<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/resources/ad_group_extension_setting.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V6\Resources;

class AdGroupExtensionSetting
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Http::initOnce();
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Api\FieldBehavior::initOnce();
        \GPBMetadata\Google\Api\Resource::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ac7030a3c676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f657874656e73696f6e5f73657474696e675f6465766963652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d73226d0a1a457874656e73696f6e53657474696e67446576696365456e756d224f0a16457874656e73696f6e53657474696e67446576696365120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120a0a064d4f42494c451002120b0a074445534b544f50100342f0010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d73421b457874656e73696f6e53657474696e6744657669636550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f330ac6040a32676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f657874656e73696f6e5f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d7322fe010a11457874656e73696f6e54797065456e756d22e8010a0d457874656e73696f6e54797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112080a044e4f4e45100212070a03415050100312080a0443414c4c1004120b0a0743414c4c4f55541005120b0a074d455353414745100612090a0550524943451007120d0a0950524f4d4f54494f4e1008120c0a08534954454c494e4b100a12160a12535452554354555245445f534e4950504554100b120c0a084c4f434154494f4e100c12160a12414646494c494154455f4c4f434154494f4e100d12110a0d484f54454c5f43414c4c4f5554100f12090a05494d414745101042e7010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d734212457874656e73696f6e5479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f330adc080a42676f6f676c652f6164732f676f6f676c656164732f76362f7265736f75726365732f61645f67726f75705f657874656e73696f6e5f73657474696e672e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365731a32676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f657874656e73696f6e5f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22d0040a17416447726f7570457874656e73696f6e53657474696e67124f0a0d7265736f757263655f6e616d651801200128094238e04105fa41320a30676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570457874656e73696f6e53657474696e67125b0a0e657874656e73696f6e5f7479706518022001280e323e2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732e457874656e73696f6e54797065456e756d2e457874656e73696f6e547970654203e04105123f0a0861645f67726f75701806200128094228e04105fa41220a20676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f75704800880101124d0a14657874656e73696f6e5f666565645f6974656d73180720032809422ffa412c0a2a676f6f676c656164732e676f6f676c65617069732e636f6d2f457874656e73696f6e466565644974656d12600a0664657669636518052001280e32502e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732e457874656e73696f6e53657474696e67446576696365456e756d2e457874656e73696f6e53657474696e674465766963653a8701ea4183010a30676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570457874656e73696f6e53657474696e67124f637573746f6d6572732f7b637573746f6d65725f69647d2f616447726f7570457874656e73696f6e53657474696e67732f7b61645f67726f75705f69647d7e7b657874656e73696f6e5f747970657d420b0a095f61645f67726f75704289020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f7572636573421c416447726f7570457874656e73696f6e53657474696e6750726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56362e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56365c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a5265736f7572636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

