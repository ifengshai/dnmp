<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/resources/campaign_experiment.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V5\Resources;

class CampaignExperiment
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Http::initOnce();
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Protobuf\Wrappers::initOnce();
        \GPBMetadata\Google\Api\Resource::initOnce();
        \GPBMetadata\Google\Api\FieldBehavior::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ad5040a3e676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f63616d706169676e5f6578706572696d656e745f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322f6010a1c43616d706169676e4578706572696d656e74537461747573456e756d22d5010a1843616d706169676e4578706572696d656e74537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112100a0c494e495449414c495a494e47100212190a15494e495449414c495a4154494f4e5f4641494c45441008120b0a07454e41424c45441003120d0a094752414455415445441004120b0a0752454d4f5645441005120d0a0950524f4d4f54494e47100612140a1050524f4d4f54494f4e5f4641494c45441009120c0a0850524f4d4f544544100712120a0e454e4445445f4d414e55414c4c59100a42f2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421d43616d706169676e4578706572696d656e7453746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330aff030a4a676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f63616d706169676e5f6578706572696d656e745f747261666669635f73706c69745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73228a010a2643616d706169676e4578706572696d656e745472616666696353706c697454797065456e756d22600a2243616d706169676e4578706572696d656e745472616666696353706c697454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112100a0c52414e444f4d5f51554552591002120a0a06434f4f4b4945100342fc010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73422743616d706169676e4578706572696d656e745472616666696353706c69745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ac00c0a3b676f6f676c652f6164732f676f6f676c656164732f76352f7265736f75726365732f63616d706169676e5f6578706572696d656e742e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365731a4a676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f63616d706169676e5f6578706572696d656e745f747261666669635f73706c69745f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f2288080a1243616d706169676e4578706572696d656e74124a0a0d7265736f757263655f6e616d651801200128094233e04105fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4578706572696d656e74122c0a02696418022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410312640a0e63616d706169676e5f647261667418032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565422ee04105fa41280a26676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4472616674122a0a046e616d6518042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512310a0b6465736372697074696f6e18052001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565123f0a15747261666669635f73706c69745f70657263656e7418062001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e041051289010a12747261666669635f73706c69745f7479706518072001280e32682e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e43616d706169676e4578706572696d656e745472616666696353706c697454797065456e756d2e43616d706169676e4578706572696d656e745472616666696353706c6974547970654203e0410512640a136578706572696d656e745f63616d706169676e18082001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654229e04103fa41230a21676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e12690a0673746174757318092001280e32542e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e43616d706169676e4578706572696d656e74537461747573456e756d2e43616d706169676e4578706572696d656e745374617475734203e0410312410a166c6f6e675f72756e6e696e675f6f7065726174696f6e180a2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312300a0a73746172745f64617465180b2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565122e0a08656e645f64617465180c2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75653a70ea416d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4578706572696d656e74123e637573746f6d6572732f7b637573746f6d65727d2f63616d706169676e4578706572696d656e74732f7b63616d706169676e5f6578706572696d656e747d4284020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f7572636573421743616d706169676e4578706572696d656e7450726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56352e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56355c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a5265736f7572636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

