<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/resources/feed.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Resources;

class Feed
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
        $pool->internalAddGeneratedFile(hex2bin(
            "0a83040a4d676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f616666696c696174655f6c6f636174696f6e5f666565645f72656c6174696f6e736869705f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732288010a29416666696c696174654c6f636174696f6e4665656452656c6174696f6e7368697054797065456e756d225b0a25416666696c696174654c6f636174696f6e4665656452656c6174696f6e7368697054797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112140a1047454e4552414c5f52455441494c4552100242ff010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73422a416666696c696174654c6f636174696f6e4665656452656c6174696f6e736869705479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330ad5040a37676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f666565645f6174747269627574655f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732284020a154665656441747472696275746554797065456e756d22ea010a114665656441747472696275746554797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112090a05494e5436341002120a0a06444f55424c451003120a0a06535452494e471004120b0a07424f4f4c45414e100512070a0355524c1006120d0a09444154455f54494d451007120e0a0a494e5436345f4c4953541008120f0a0b444f55424c455f4c4953541009120f0a0b535452494e475f4c495354100a12100a0c424f4f4c45414e5f4c495354100b120c0a0855524c5f4c495354100c12120a0e444154455f54494d455f4c495354100d12090a055052494345100e42eb010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d734216466565644174747269627574655479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330a93030a2f676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f666565645f6f726967696e2e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322520a0e466565644f726967696e456e756d22400a0a466565644f726967696e120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112080a04555345521002120a0a06474f4f474c45100342e4010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73420f466565644f726967696e50726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330a97030a2f676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f666565645f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322560a0e46656564537461747573456e756d22440a0a46656564537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a07454e41424c45441002120b0a0752454d4f564544100342e4010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73420f4665656453746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330a8b150a2c676f6f676c652f6164732f676f6f676c656164732f76332f7265736f75726365732f666565642e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365731a37676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f666565645f6174747269627574655f747970652e70726f746f1a2f676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f666565645f6f726967696e2e70726f746f1a2f676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f666565645f7374617475732e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22c10c0a0446656564123c0a0d7265736f757263655f6e616d651801200128094225e04105fa411f0a1d676f6f676c656164732e676f6f676c65617069732e636f6d2f46656564122c0a02696418022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e04103122f0a046e616d6518032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410512440a0a6174747269627574657318042003280b32302e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e4665656441747472696275746512570a146174747269627574655f6f7065726174696f6e7318092003280b32392e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e466565644174747269627574654f7065726174696f6e124d0a066f726967696e18052001280e32382e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e466565644f726967696e456e756d2e466565644f726967696e4203e04105124d0a0673746174757318082001280e32382e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e46656564537461747573456e756d2e466565645374617475734203e0410312630a19706c616365735f6c6f636174696f6e5f666565645f6461746118062001280b323e2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e466565642e506c616365734c6f636174696f6e4665656444617461480012690a1c616666696c696174655f6c6f636174696f6e5f666565645f6461746118072001280b32412e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e466565642e416666696c696174654c6f636174696f6e466565644461746148001ace040a16506c616365734c6f636174696f6e466565644461746112610a0a6f617574685f696e666f18012001280b32482e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e466565642e506c616365734c6f636174696f6e46656564446174612e4f41757468496e666f4203e0410512330a0d656d61696c5f6164647265737318022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512390a13627573696e6573735f6163636f756e745f6964180a2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565123a0a14627573696e6573735f6e616d655f66696c74657218042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512360a1063617465676f72795f66696c7465727318052003280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512330a0d6c6162656c5f66696c7465727318062003280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75651ab7010a094f41757468496e666f12310a0b687474705f6d6574686f6418012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512360a10687474705f726571756573745f75726c18022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565123f0a19687474705f617574686f72697a6174696f6e5f68656164657218032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75651ad7010a19416666696c696174654c6f636174696f6e4665656444617461122e0a09636861696e5f69647318012003280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75651289010a1172656c6174696f6e736869705f7479706518022001280e326e2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e416666696c696174654c6f636174696f6e4665656452656c6174696f6e7368697054797065456e756d2e416666696c696174654c6f636174696f6e4665656452656c6174696f6e73686970547970653a45ea41420a1d676f6f676c656164732e676f6f676c65617069732e636f6d2f466565641221637573746f6d6572732f7b637573746f6d65727d2f66656564732f7b666565647d421d0a1b73797374656d5f666565645f67656e65726174696f6e5f6461746122ee010a0d4665656441747472696275746512270a02696418012001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565122a0a046e616d6518022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512540a047479706518032001280e32462e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e4665656441747472696275746554797065456e756d2e466565644174747269627574655479706512320a0e69735f706172745f6f665f6b657918042001280b321a2e676f6f676c652e70726f746f6275662e426f6f6c56616c756522ec010a16466565644174747269627574654f7065726174696f6e12590a086f70657261746f7218012001280e32422e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e466565644174747269627574654f7065726174696f6e2e4f70657261746f724203e0410312440a0576616c756518022001280b32302e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e466565644174747269627574654203e0410322310a084f70657261746f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112070a03414444100242f6010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f757263657342094665656450726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56332e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56335c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5265736f7572636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

