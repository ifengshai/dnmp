<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/services/account_link_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V4\Services;

class AccountLinkService
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
        $pool->internalAddGeneratedFile(hex2bin(
            "0abd030a35676f6f676c652f6164732f676f6f676c656164732f76342f656e756d732f6d6f62696c655f6170705f76656e646f722e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76342e656e756d7322710a134d6f62696c6541707056656e646f72456e756d225a0a0f4d6f62696c6541707056656e646f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112130a0f4150504c455f4150505f53544f5245100212140a10474f4f474c455f4150505f53544f5245100342e9010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d7342144d6f62696c6541707056656e646f7250726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56342e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56345c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a456e756d73620670726f746f330ab4030a37676f6f676c652f6164732f676f6f676c656164732f76342f656e756d732f6163636f756e745f6c696e6b5f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76342e656e756d7322640a154163636f756e744c696e6b537461747573456e756d224b0a114163636f756e744c696e6b537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a07454e41424c45441002120b0a0752454d4f564544100342eb010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d7342164163636f756e744c696e6b53746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56342e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56345c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a456e756d73620670726f746f330ab9030a37676f6f676c652f6164732f676f6f676c656164732f76342f656e756d732f6c696e6b65645f6163636f756e745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76342e656e756d7322690a154c696e6b65644163636f756e7454797065456e756d22500a114c696e6b65644163636f756e7454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001121d0a1954484952445f50415254595f4150505f414e414c5954494353100242eb010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d7342164c696e6b65644163636f756e745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56342e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56345c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a456e756d73620670726f746f330aee0a0a34676f6f676c652f6164732f676f6f676c656164732f76342f7265736f75726365732f6163636f756e745f6c696e6b2e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365731a37676f6f676c652f6164732f676f6f676c656164732f76342f656e756d732f6c696e6b65645f6163636f756e745f747970652e70726f746f1a35676f6f676c652f6164732f676f6f676c656164732f76342f656e756d732f6d6f62696c655f6170705f76656e646f722e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22a2040a0b4163636f756e744c696e6b12430a0d7265736f757263655f6e616d65180120012809422ce04105fa41260a24676f6f676c656164732e676f6f676c65617069732e636f6d2f4163636f756e744c696e6b12390a0f6163636f756e745f6c696e6b5f696418022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410312560a0673746174757318032001280e32462e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d732e4163636f756e744c696e6b537461747573456e756d2e4163636f756e744c696e6b53746174757312590a047479706518042001280e32462e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d732e4c696e6b65644163636f756e7454797065456e756d2e4c696e6b65644163636f756e74547970654203e0410312710a1974686972645f70617274795f6170705f616e616c797469637318052001280b32472e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e54686972645061727479417070416e616c79746963734c696e6b4964656e7469666965724203e0410548003a5bea41580a24676f6f676c656164732e676f6f676c65617069732e636f6d2f4163636f756e744c696e6b1230637573746f6d6572732f7b637573746f6d65727d2f6163636f756e744c696e6b732f7b6163636f756e745f6c696e6b7d42100a0e6c696e6b65645f6163636f756e7422fb010a2454686972645061727479417070416e616c79746963734c696e6b4964656e74696669657212430a196170705f616e616c79746963735f70726f76696465725f696418012001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410512310a066170705f696418022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e04105125b0a0a6170705f76656e646f7218032001280e32422e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d732e4d6f62696c6541707056656e646f72456e756d2e4d6f62696c6541707056656e646f724203e0410542fd010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f757263657342104163636f756e744c696e6b50726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56342e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56345c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a5265736f7572636573620670726f746f330ae00b0a3b676f6f676c652f6164732f676f6f676c656164732f76342f73657276696365732f6163636f756e745f6c696e6b5f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f225c0a154765744163636f756e744c696e6b5265717565737412430a0d7265736f757263655f6e616d65180120012809422ce04102fa41260a24676f6f676c656164732e676f6f676c65617069732e636f6d2f4163636f756e744c696e6b22b4010a184d75746174654163636f756e744c696e6b5265717565737412180a0b637573746f6d65725f69641801200128094203e04102124e0a096f7065726174696f6e18022001280b32362e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4163636f756e744c696e6b4f7065726174696f6e4203e0410212170a0f7061727469616c5f6661696c75726518032001280812150a0d76616c69646174655f6f6e6c7918042001280822770a144163636f756e744c696e6b4f7065726174696f6e12400a0663726561746518012001280b322e2e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e4163636f756e744c696e6b480012100a0672656d6f76651803200128094800420b0a096f7065726174696f6e22660a194d75746174654163636f756e744c696e6b526573706f6e736512490a06726573756c7418012001280b32392e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4d75746174654163636f756e744c696e6b526573756c7422300a174d75746174654163636f756e744c696e6b526573756c7412150a0d7265736f757263655f6e616d6518012001280932da030a124163636f756e744c696e6b5365727669636512c1010a0e4765744163636f756e744c696e6b12372e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4765744163636f756e744c696e6b526571756573741a2e2e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e4163636f756e744c696e6b224682d3e4930230122e2f76342f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f6163636f756e744c696e6b732f2a7dda410d7265736f757263655f6e616d6512e2010a114d75746174654163636f756e744c696e6b123a2e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4d75746174654163636f756e744c696e6b526571756573741a3b2e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4d75746174654163636f756e744c696e6b526573706f6e7365225482d3e493023622312f76342f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f6163636f756e744c696e6b733a6d75746174653a012ada4115637573746f6d65725f69642c6f7065726174696f6e1a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d42fe010a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e736572766963657342174163636f756e744c696e6b5365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56342e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56345c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

