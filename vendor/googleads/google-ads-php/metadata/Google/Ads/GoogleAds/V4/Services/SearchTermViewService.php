<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/services/search_term_view_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V4\Services;

class SearchTermViewService
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
            "0af3030a40676f6f676c652f6164732f676f6f676c656164732f76342f656e756d732f7365617263685f7465726d5f746172676574696e675f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76342e656e756d732291010a1d5365617263685465726d546172676574696e67537461747573456e756d22700a195365617263685465726d546172676574696e67537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112090a0541444445441002120c0a084558434c55444544100312120a0e41444445445f4558434c55444544100412080a044e4f4e45100542f3010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d73421e5365617263685465726d546172676574696e6753746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56342e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56345c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a456e756d73620670726f746f330aa3070a38676f6f676c652f6164732f676f6f676c656164732f76342f7265736f75726365732f7365617263685f7465726d5f766965772e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365731a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22be030a0e5365617263685465726d5669657712460a0d7265736f757263655f6e616d65180120012809422fe04103fa41290a27676f6f676c656164732e676f6f676c65617069732e636f6d2f5365617263685465726d5669657712360a0b7365617263685f7465726d18022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312580a0861645f67726f757018032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654228e04103fa41220a20676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570126b0a0673746174757318042001280e32562e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d732e5365617263685465726d546172676574696e67537461747573456e756d2e5365617263685465726d546172676574696e675374617475734203e041033a65ea41620a27676f6f676c656164732e676f6f676c65617069732e636f6d2f5365617263685465726d566965771237637573746f6d6572732f7b637573746f6d65727d2f7365617263685465726d56696577732f7b7365617263685f7465726d5f766965777d4280020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f757263657342135365617263685465726d5669657750726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56342e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56345c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a5265736f7572636573620670726f746f330acd060a3f676f6f676c652f6164732f676f6f676c656164732f76342f73657276696365732f7365617263685f7465726d5f766965775f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f22620a184765745365617263685465726d566965775265717565737412460a0d7265736f757263655f6e616d65180120012809422fe04102fa41290a27676f6f676c656164732e676f6f676c65617069732e636f6d2f5365617263685465726d566965773284020a155365617263685465726d566965775365727669636512cd010a114765745365617263685465726d56696577123a2e676f6f676c652e6164732e676f6f676c656164732e76342e73657276696365732e4765745365617263685465726d56696577526571756573741a312e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e5365617263685465726d56696577224982d3e493023312312f76342f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f7365617263685465726d56696577732f2a7dda410d7265736f757263655f6e616d651a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d4281020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e7365727669636573421a5365617263685465726d566965775365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56342e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56345c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

