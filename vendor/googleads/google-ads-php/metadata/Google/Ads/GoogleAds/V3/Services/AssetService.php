<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/asset_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Services;

class AssetService
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
            "0a9a040a2d676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f6d696d655f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322dc010a0c4d696d6554797065456e756d22cb010a084d696d6554797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120e0a0a494d4147455f4a5045471002120d0a09494d4147455f4749461003120d0a09494d4147455f504e47100412090a05464c4153481005120d0a09544558545f48544d4c100612070a035044461007120a0a064d53574f52441008120b0a074d53455843454c100912070a03525446100a120d0a09415544494f5f574156100b120d0a09415544494f5f4d5033100c12100a0c48544d4c355f41445f5a4950100d42e2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73420d4d696d655479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330ae5070a30676f6f676c652f6164732f676f6f676c656164732f76332f636f6d6d6f6e2f61737365745f74797065732e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f224b0a11596f7574756265566964656f417373657412360a10796f75747562655f766964656f5f696418012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565223d0a104d6564696142756e646c65417373657412290a046461746118012001280b321b2e676f6f676c652e70726f746f6275662e427974657356616c756522f3010a0a496d616765417373657412290a046461746118012001280b321b2e676f6f676c652e70726f746f6275662e427974657356616c7565122e0a0966696c655f73697a6518022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512470a096d696d655f7479706518032001280e32342e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e4d696d6554797065456e756d2e4d696d655479706512410a0966756c6c5f73697a6518042001280b322e2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e496d61676544696d656e73696f6e22a2010a0e496d61676544696d656e73696f6e12320a0d6865696768745f706978656c7318012001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512310a0c77696474685f706978656c7318022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512290a0375726c18032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756522370a09546578744173736574122a0a047465787418012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756542ea010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e420f4173736574547970657350726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56332e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56335c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a436f6d6d6f6e620670726f746f330ab3030a2e676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f61737365745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322740a0d417373657454797065456e756d22630a09417373657454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112110a0d594f55545542455f564944454f100212100a0c4d454449415f42554e444c45100312090a05494d414745100412080a0454455854100542e3010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73420e41737365745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330afe080a2d676f6f676c652f6164732f676f6f676c656164732f76332f7265736f75726365732f61737365742e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365731a2e676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f61737365745f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22fd040a054173736574123d0a0d7265736f757263655f6e616d651801200128094226e04105fa41200a1e676f6f676c656164732e676f6f676c65617069732e636f6d2f4173736574122c0a02696418022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e04103122a0a046e616d6518032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512490a047479706518042001280e32362e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e417373657454797065456e756d2e4173736574547970654203e0410312550a13796f75747562655f766964656f5f617373657418052001280b32312e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e596f7574756265566964656f41737365744203e04105480012530a126d656469615f62756e646c655f617373657418062001280b32302e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e4d6564696142756e646c6541737365744203e04105480012460a0b696d6167655f617373657418072001280b322a2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e496d61676541737365744203e04103480012440a0a746578745f617373657418082001280b32292e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e5465787441737365744203e0410348003a48ea41450a1e676f6f676c656164732e676f6f676c65617069732e636f6d2f41737365741223637573746f6d6572732f7b637573746f6d65727d2f6173736574732f7b61737365747d420c0a0a61737365745f6461746142f7010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f7572636573420a417373657450726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56332e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56335c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5265736f7572636573620670726f746f330aac0a0a34676f6f676c652f6164732f676f6f676c656164732f76332f73657276696365732f61737365745f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f22500a0f476574417373657452657175657374123d0a0d7265736f757263655f6e616d651801200128094226e04102fa41200a1e676f6f676c656164732e676f6f676c65617069732e636f6d2f4173736574227a0a134d75746174654173736574735265717565737412180a0b637573746f6d65725f69641801200128094203e0410212490a0a6f7065726174696f6e7318022003280b32302e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e41737365744f7065726174696f6e4203e0410222590a0e41737365744f7065726174696f6e123a0a0663726561746518012001280b32282e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e41737365744800420b0a096f7065726174696f6e225c0a144d7574617465417373657473526573706f6e736512440a07726573756c747318022003280b32332e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d75746174654173736574526573756c74222a0a114d75746174654173736574526573756c7412150a0d7265736f757263655f6e616d6518012001280932a8030a0c41737365745365727669636512a9010a08476574417373657412312e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4765744173736574526571756573741a282e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e4173736574224082d3e493022a12282f76332f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f6173736574732f2a7dda410d7265736f757263655f6e616d6512ce010a0c4d757461746541737365747312352e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d7574617465417373657473526571756573741a362e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d7574617465417373657473526573706f6e7365224f82d3e4930230222b2f76332f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f6173736574733a6d75746174653a012ada4116637573746f6d65725f69642c6f7065726174696f6e731a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d42f8010a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7365727669636573421141737365745365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56332e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56335c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

