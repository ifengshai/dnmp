<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/geographic_view_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Services;

class GeographicViewService
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
            "0ac6030a36676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f67656f5f746172676574696e675f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322780a1447656f546172676574696e6754797065456e756d22600a1047656f546172676574696e6754797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112140a10415245415f4f465f494e544552455354100212180a144c4f434154494f4e5f4f465f50524553454e4345100342ea010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73421547656f546172676574696e675479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330ac4060a37676f6f676c652f6164732f676f6f676c656164732f76332f7265736f75726365732f67656f677261706869635f766965772e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365731a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22e0020a0e47656f677261706869635669657712460a0d7265736f757263655f6e616d65180120012809422fe04103fa41290a27676f6f676c656164732e676f6f676c65617069732e636f6d2f47656f677261706869635669657712600a0d6c6f636174696f6e5f7479706518032001280e32442e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e47656f546172676574696e6754797065456e756d2e47656f546172676574696e67547970654203e04103123e0a14636f756e7472795f637269746572696f6e5f696418042001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e041033a64ea41610a27676f6f676c656164732e676f6f676c65617069732e636f6d2f47656f67726170686963566965771236637573746f6d6572732f7b637573746f6d65727d2f67656f6772617068696356696577732f7b67656f677261706869635f766965777d4280020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f7572636573421347656f677261706869635669657750726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56332e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56335c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5265736f7572636573620670726f746f330acc060a3e676f6f676c652f6164732f676f6f676c656164732f76332f73657276696365732f67656f677261706869635f766965775f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f22620a1847657447656f67726170686963566965775265717565737412460a0d7265736f757263655f6e616d65180120012809422fe04102fa41290a27676f6f676c656164732e676f6f676c65617069732e636f6d2f47656f67726170686963566965773284020a1547656f67726170686963566965775365727669636512cd010a1147657447656f6772617068696356696577123a2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e47657447656f6772617068696356696577526571756573741a312e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e47656f6772617068696356696577224982d3e493023312312f76332f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f67656f6772617068696356696577732f2a7dda410d7265736f757263655f6e616d651a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d4281020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7365727669636573421a47656f67726170686963566965775365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56332e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56335c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

