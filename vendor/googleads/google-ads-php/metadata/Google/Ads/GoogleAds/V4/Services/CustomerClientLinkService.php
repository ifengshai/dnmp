<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/services/customer_client_link_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V4\Services;

class CustomerClientLinkService
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
        $pool->internalAddGeneratedFile(hex2bin(
            "0add030a37676f6f676c652f6164732f676f6f676c656164732f76342f656e756d732f6d616e616765725f6c696e6b5f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76342e656e756d73228c010a154d616e616765724c696e6b537461747573456e756d22730a114d616e616765724c696e6b537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120a0a064143544956451002120c0a08494e4143544956451003120b0a0750454e44494e471004120b0a07524546555345441005120c0a0843414e43454c4544100642eb010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d7342164d616e616765724c696e6b53746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56342e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56345c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a456e756d73620670726f746f330abb070a3c676f6f676c652f6164732f676f6f676c656164732f76342f7265736f75726365732f637573746f6d65725f636c69656e745f6c696e6b2e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365731a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22ce030a12437573746f6d6572436c69656e744c696e6b124a0a0d7265736f757263655f6e616d651801200128094233e04105fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f437573746f6d6572436c69656e744c696e6b123a0a0f636c69656e745f637573746f6d657218032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410512390a0f6d616e616765725f6c696e6b5f696418042001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410312560a0673746174757318052001280e32462e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d732e4d616e616765724c696e6b537461747573456e756d2e4d616e616765724c696e6b537461747573122a0a0668696464656e18062001280b321a2e676f6f676c652e70726f746f6275662e426f6f6c56616c75653a71ea416e0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f437573746f6d6572436c69656e744c696e6b123f637573746f6d6572732f7b637573746f6d65727d2f637573746f6d6572436c69656e744c696e6b732f7b637573746f6d65725f636c69656e745f6c696e6b7d4284020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365734217437573746f6d6572436c69656e744c696e6b50726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56342e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56345c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a5265736f7572636573620670726f746f330ac80d0a43676f6f676c652f6164732f676f6f676c656164732f76342f73657276696365732f637573746f6d65725f636c69656e745f6c696e6b5f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a20676f6f676c652f70726f746f6275662f6669656c645f6d61736b2e70726f746f226a0a1c476574437573746f6d6572436c69656e744c696e6b52657175657374124a0a0d7265736f757263655f6e616d651801200128094233e04102fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f437573746f6d6572436c69656e744c696e6b2292010a1f4d7574617465437573746f6d6572436c69656e744c696e6b5265717565737412180a0b637573746f6d65725f69641801200128094203e0410212550a096f7065726174696f6e18022001280b323d2e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e437573746f6d6572436c69656e744c696e6b4f7065726174696f6e4203e0410222ed010a1b437573746f6d6572436c69656e744c696e6b4f7065726174696f6e122f0a0b7570646174655f6d61736b18042001280b321a2e676f6f676c652e70726f746f6275662e4669656c644d61736b12470a0663726561746518012001280b32352e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e437573746f6d6572436c69656e744c696e6b480012470a0675706461746518022001280b32352e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e437573746f6d6572436c69656e744c696e6b4800420b0a096f7065726174696f6e22740a204d7574617465437573746f6d6572436c69656e744c696e6b526573706f6e736512500a06726573756c7418012001280b32402e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4d7574617465437573746f6d6572436c69656e744c696e6b526573756c7422370a1e4d7574617465437573746f6d6572436c69656e744c696e6b526573756c7412150a0d7265736f757263655f6e616d651801200128093299040a19437573746f6d6572436c69656e744c696e6b5365727669636512dd010a15476574437573746f6d6572436c69656e744c696e6b123e2e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e476574437573746f6d6572436c69656e744c696e6b526571756573741a352e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e437573746f6d6572436c69656e744c696e6b224d82d3e493023712352f76342f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f637573746f6d6572436c69656e744c696e6b732f2a7dda410d7265736f757263655f6e616d6512fe010a184d7574617465437573746f6d6572436c69656e744c696e6b12412e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4d7574617465437573746f6d6572436c69656e744c696e6b526571756573741a422e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4d7574617465437573746f6d6572436c69656e744c696e6b526573706f6e7365225b82d3e493023d22382f76342f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f637573746f6d6572436c69656e744c696e6b733a6d75746174653a012ada4115637573746f6d65725f69642c6f7065726174696f6e1a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d4285020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e7365727669636573421e437573746f6d6572436c69656e744c696e6b5365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56342e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56345c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

