<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/ad_group_feed_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V5\Services;

class AdGroupFeedService
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
        \GPBMetadata\Google\Api\Client::initOnce();
        \GPBMetadata\Google\Protobuf\FieldMask::initOnce();
        \GPBMetadata\Google\Protobuf\Any::initOnce();
        \GPBMetadata\Google\Rpc\Status::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ae7030a42676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6d61746368696e675f66756e6374696f6e5f636f6e746578745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732281010a1f4d61746368696e6746756e6374696f6e436f6e7465787454797065456e756d225e0a1b4d61746368696e6746756e6374696f6e436f6e7465787454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112100a0c464545445f4954454d5f49441002120f0a0b4445564943455f4e414d45100342f5010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7342204d61746368696e6746756e6374696f6e436f6e746578745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330af4030a3e676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6d61746368696e675f66756e6374696f6e5f6f70657261746f722e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732295010a1c4d61746368696e6746756e6374696f6e4f70657261746f72456e756d22750a184d61746368696e6746756e6374696f6e4f70657261746f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112060a02494e1002120c0a084944454e544954591003120a0a06455155414c53100412070a03414e44100512100a0c434f4e5441494e535f414e59100642f2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421d4d61746368696e6746756e6374696f6e4f70657261746f7250726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330a870e0a36676f6f676c652f6164732f676f6f676c656164732f76352f636f6d6d6f6e2f6d61746368696e675f66756e6374696f6e2e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e1a3e676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6d61746368696e675f66756e6374696f6e5f6f70657261746f722e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22b2020a104d61746368696e6746756e6374696f6e12350a0f66756e6374696f6e5f737472696e6718012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512660a086f70657261746f7218042001280e32542e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e4d61746368696e6746756e6374696f6e4f70657261746f72456e756d2e4d61746368696e6746756e6374696f6e4f70657261746f72123e0a0d6c6566745f6f706572616e647318022003280b32272e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e4f706572616e64123f0a0e72696768745f6f706572616e647318032003280b32272e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e4f706572616e6422fe070a074f706572616e6412530a10636f6e7374616e745f6f706572616e6418012001280b32372e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e4f706572616e642e436f6e7374616e744f706572616e644800125e0a16666565645f6174747269627574655f6f706572616e6418022001280b323c2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e4f706572616e642e466565644174747269627574654f706572616e64480012530a1066756e6374696f6e5f6f706572616e6418032001280b32372e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e4f706572616e642e46756e6374696f6e4f706572616e64480012600a17726571756573745f636f6e746578745f6f706572616e6418042001280b323d2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e4f706572616e642e52657175657374436f6e746578744f706572616e6448001aff010a0f436f6e7374616e744f706572616e6412340a0c737472696e675f76616c756518012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565480012310a0a6c6f6e675f76616c756518022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565480012330a0d626f6f6c65616e5f76616c756518032001280b321a2e676f6f676c652e70726f746f6275662e426f6f6c56616c7565480012340a0c646f75626c655f76616c756518042001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565480042180a16636f6e7374616e745f6f706572616e645f76616c75651a7c0a14466565644174747269627574654f706572616e64122c0a07666565645f696418012001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512360a11666565645f6174747269627574655f696418022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75651a5e0a0f46756e6374696f6e4f706572616e64124b0a116d61746368696e675f66756e6374696f6e18012001280b32302e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e4d61746368696e6746756e6374696f6e1a89010a1552657175657374436f6e746578744f706572616e6412700a0c636f6e746578745f7479706518012001280e325a2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e4d61746368696e6746756e6374696f6e436f6e7465787454797065456e756d2e4d61746368696e6746756e6374696f6e436f6e7465787454797065421b0a1966756e6374696f6e5f617267756d656e745f6f706572616e6442f0010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e42154d61746368696e6746756e6374696f6e50726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56352e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56355c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a436f6d6d6f6e620670726f746f330adc050a34676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f706c616365686f6c6465725f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732290030a13506c616365686f6c64657254797065456e756d22f8020a0f506c616365686f6c64657254797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120c0a08534954454c494e4b100212080a0443414c4c100312070a034150501004120c0a084c4f434154494f4e100512160a12414646494c494154455f4c4f434154494f4e1006120b0a0743414c4c4f5554100712160a12535452554354555245445f534e49505045541008120b0a074d455353414745100912090a055052494345100a120d0a0950524f4d4f54494f4e100b12110a0d41445f435553544f4d495a4552100c12150a1144594e414d49435f454455434154494f4e100d12120a0e44594e414d49435f464c49474854100e12120a0e44594e414d49435f435553544f4d100f12110a0d44594e414d49435f484f54454c101012170a1344594e414d49435f5245414c5f455354415445101112120a0e44594e414d49435f54524156454c101212110a0d44594e414d49435f4c4f43414c1013120f0a0b44594e414d49435f4a4f42101442e9010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734214506c616365686f6c6465725479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330aa8030a34676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f666565645f6c696e6b5f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73225e0a12466565644c696e6b537461747573456e756d22480a0e466565644c696e6b537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a07454e41424c45441002120b0a0752454d4f564544100342e8010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734213466565644c696e6b53746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ac3030a39676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f726573706f6e73655f636f6e74656e745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73226f0a17526573706f6e7365436f6e74656e7454797065456e756d22540a13526573706f6e7365436f6e74656e7454797065120f0a0b554e535045434946494544100012160a125245534f555243455f4e414d455f4f4e4c59100112140a104d555441424c455f5245534f55524345100242ed010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734218526573706f6e7365436f6e74656e745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330aab090a35676f6f676c652f6164732f676f6f676c656164732f76352f7265736f75726365732f61645f67726f75705f666565642e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365731a34676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f666565645f6c696e6b5f7374617475732e70726f746f1a34676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f706c616365686f6c6465725f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22e0040a0b416447726f75704665656412430a0d7265736f757263655f6e616d65180120012809422ce04105fa41260a24676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f75704665656412510a046665656418022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654225e04105fa411f0a1d676f6f676c656164732e676f6f676c65617069732e636f6d2f4665656412580a0861645f67726f757018032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654228e04105fa41220a20676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570125d0a11706c616365686f6c6465725f747970657318042003280e32422e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e506c616365686f6c64657254797065456e756d2e506c616365686f6c64657254797065124b0a116d61746368696e675f66756e6374696f6e18052001280b32302e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e4d61746368696e6746756e6374696f6e12550a0673746174757318062001280e32402e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e466565644c696e6b537461747573456e756d2e466565644c696e6b5374617475734203e041033a5cea41590a24676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570466565641231637573746f6d6572732f7b637573746f6d65727d2f616447726f757046656564732f7b61645f67726f75705f666565647d42fd010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365734210416447726f75704665656450726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56352e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56355c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a5265736f7572636573620670726f746f330ab50f0a3c676f6f676c652f6164732f676f6f676c656164732f76352f73657276696365732f61645f67726f75705f666565645f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365731a35676f6f676c652f6164732f676f6f676c656164732f76352f7265736f75726365732f61645f67726f75705f666565642e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a20676f6f676c652f70726f746f6275662f6669656c645f6d61736b2e70726f746f1a17676f6f676c652f7270632f7374617475732e70726f746f225c0a15476574416447726f7570466565645265717565737412430a0d7265736f757263655f6e616d65180120012809422ce04102fa41260a24676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f75704665656422a1020a194d7574617465416447726f757046656564735265717565737412180a0b637573746f6d65725f69641801200128094203e04102124f0a0a6f7065726174696f6e7318022003280b32362e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e416447726f7570466565644f7065726174696f6e4203e0410212170a0f7061727469616c5f6661696c75726518032001280812150a0d76616c69646174655f6f6e6c7918042001280812690a15726573706f6e73655f636f6e74656e745f7479706518052001280e324a2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e526573706f6e7365436f6e74656e7454797065456e756d2e526573706f6e7365436f6e74656e745479706522ea010a14416447726f7570466565644f7065726174696f6e122f0a0b7570646174655f6d61736b18042001280b321a2e676f6f676c652e70726f746f6275662e4669656c644d61736b12400a0663726561746518012001280b322e2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e416447726f757046656564480012400a0675706461746518022001280b322e2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e416447726f757046656564480012100a0672656d6f76651803200128094800420b0a096f7065726174696f6e229b010a1a4d7574617465416447726f75704665656473526573706f6e736512310a157061727469616c5f6661696c7572655f6572726f7218032001280b32122e676f6f676c652e7270632e537461747573124a0a07726573756c747318022003280b32392e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e4d7574617465416447726f757046656564526573756c7422770a174d7574617465416447726f757046656564526573756c7412150a0d7265736f757263655f6e616d6518012001280912450a0d61645f67726f75705f6665656418022001280b322e2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e416447726f75704665656432de030a12416447726f7570466565645365727669636512c1010a0e476574416447726f75704665656412372e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e476574416447726f757046656564526571756573741a2e2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e416447726f757046656564224682d3e4930230122e2f76352f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f616447726f757046656564732f2a7dda410d7265736f757263655f6e616d6512e6010a124d7574617465416447726f75704665656473123b2e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e4d7574617465416447726f75704665656473526571756573741a3c2e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e4d7574617465416447726f75704665656473526573706f6e7365225582d3e493023622312f76352f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f616447726f757046656564733a6d75746174653a012ada4116637573746f6d65725f69642c6f7065726174696f6e731a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d42fe010a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365734217416447726f7570466565645365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56352e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56355c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

