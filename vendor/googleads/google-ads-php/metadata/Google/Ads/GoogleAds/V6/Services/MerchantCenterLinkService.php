<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/services/merchant_center_link_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V6\Services;

class MerchantCenterLinkService
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
        \GPBMetadata\Google\Protobuf\FieldMask::initOnce();
        \GPBMetadata\Google\Api\Client::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ad1030a3f676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f6d65726368616e745f63656e7465725f6c696e6b5f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d7322720a1c4d65726368616e7443656e7465724c696e6b537461747573456e756d22520a184d65726368616e7443656e7465724c696e6b537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a07454e41424c45441002120b0a0750454e44494e47100342f2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d73421d4d65726368616e7443656e7465724c696e6b53746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f330af5060a3c676f6f676c652f6164732f676f6f676c656164732f76362f7265736f75726365732f6d65726368616e745f63656e7465725f6c696e6b2e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365731a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22a8030a124d65726368616e7443656e7465724c696e6b124a0a0d7265736f757263655f6e616d651801200128094233e04105fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f4d65726368616e7443656e7465724c696e6b12140a0269641806200128034203e041034800880101122e0a1c6d65726368616e745f63656e7465725f6163636f756e745f6e616d651807200128094203e04103480188010112640a0673746174757318052001280e32542e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732e4d65726368616e7443656e7465724c696e6b537461747573456e756d2e4d65726368616e7443656e7465724c696e6b5374617475733a72ea416f0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f4d65726368616e7443656e7465724c696e6b1240637573746f6d6572732f7b637573746f6d65725f69647d2f6d65726368616e7443656e7465724c696e6b732f7b6d65726368616e745f63656e7465725f69647d42050a035f6964421f0a1d5f6d65726368616e745f63656e7465725f6163636f756e745f6e616d654284020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f757263657342174d65726368616e7443656e7465724c696e6b50726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56362e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56365c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a5265736f7572636573620670726f746f330ab0100a43676f6f676c652f6164732f676f6f676c656164732f76362f73657276696365732f6d65726368616e745f63656e7465725f6c696e6b5f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a20676f6f676c652f70726f746f6275662f6669656c645f6d61736b2e70726f746f223a0a1e4c6973744d65726368616e7443656e7465724c696e6b735265717565737412180a0b637573746f6d65725f69641801200128094203e0410222770a1f4c6973744d65726368616e7443656e7465724c696e6b73526573706f6e736512540a156d65726368616e745f63656e7465725f6c696e6b7318012003280b32352e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365732e4d65726368616e7443656e7465724c696e6b226a0a1c4765744d65726368616e7443656e7465724c696e6b52657175657374124a0a0d7265736f757263655f6e616d651801200128094233e04102fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f4d65726368616e7443656e7465724c696e6b2292010a1f4d75746174654d65726368616e7443656e7465724c696e6b5265717565737412180a0b637573746f6d65725f69641801200128094203e0410212550a096f7065726174696f6e18022001280b323d2e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4d65726368616e7443656e7465724c696e6b4f7065726174696f6e4203e0410222b6010a1b4d65726368616e7443656e7465724c696e6b4f7065726174696f6e122f0a0b7570646174655f6d61736b18032001280b321a2e676f6f676c652e70726f746f6275662e4669656c644d61736b12470a0675706461746518012001280b32352e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365732e4d65726368616e7443656e7465724c696e6b480012100a0672656d6f76651802200128094800420b0a096f7065726174696f6e22740a204d75746174654d65726368616e7443656e7465724c696e6b526573706f6e736512500a06726573756c7418022001280b32402e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4d75746174654d65726368616e7443656e7465724c696e6b526573756c7422370a1e4d75746174654d65726368616e7443656e7465724c696e6b526573756c7412150a0d7265736f757263655f6e616d651801200128093283060a194d65726368616e7443656e7465724c696e6b5365727669636512e7010a174c6973744d65726368616e7443656e7465724c696e6b7312402e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4c6973744d65726368616e7443656e7465724c696e6b73526571756573741a412e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4c6973744d65726368616e7443656e7465724c696e6b73526573706f6e7365224782d3e493023312312f76362f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f6d65726368616e7443656e7465724c696e6b73da410b637573746f6d65725f696412dd010a154765744d65726368616e7443656e7465724c696e6b123e2e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4765744d65726368616e7443656e7465724c696e6b526571756573741a352e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365732e4d65726368616e7443656e7465724c696e6b224d82d3e493023712352f76362f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f6d65726368616e7443656e7465724c696e6b732f2a7dda410d7265736f757263655f6e616d6512fe010a184d75746174654d65726368616e7443656e7465724c696e6b12412e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4d75746174654d65726368616e7443656e7465724c696e6b526571756573741a422e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4d75746174654d65726368616e7443656e7465724c696e6b526573706f6e7365225b82d3e493023d22382f76362f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f6d65726368616e7443656e7465724c696e6b733a6d75746174653a012ada4115637573746f6d65725f69642c6f7065726174696f6e1a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d4285020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e7365727669636573421e4d65726368616e7443656e7465724c696e6b5365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56362e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56365c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

