<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/resources/ad_group.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Resources;

class AdGroup
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
            "0ada030a35676f6f676c652f6164732f676f6f676c656164732f76332f636f6d6d6f6e2f637573746f6d5f706172616d657465722e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22690a0f437573746f6d506172616d6574657212290a036b657918012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565122b0a0576616c756518022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756542ef010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e4214437573746f6d506172616d6574657250726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56332e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56335c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a436f6d6d6f6e620670726f746f330ad7030a44676f6f676c652f6164732f676f6f676c656164732f76332f636f6d6d6f6e2f6578706c6f7265725f6175746f5f6f7074696d697a65725f73657474696e672e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f224a0a1c4578706c6f7265724175746f4f7074696d697a657253657474696e67122a0a066f70745f696e18012001280b321a2e676f6f676c652e70726f746f6275662e426f6f6c56616c756542fc010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e42214578706c6f7265724175746f4f7074696d697a657253657474696e6750726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56332e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56335c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a436f6d6d6f6e620670726f746f330a96040a37676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f746172676574696e675f64696d656e73696f6e2e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322c4010a16546172676574696e6744696d656e73696f6e456e756d22a9010a12546172676574696e6744696d656e73696f6e120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a074b4559574f52441002120c0a0841554449454e4345100312090a05544f5049431004120a0a0647454e4445521005120d0a094147455f52414e47451006120d0a09504c4143454d454e54100712130a0f504152454e54414c5f535441545553100812100a0c494e434f4d455f52414e4745100942ec010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d734217546172676574696e6744696d656e73696f6e50726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330afb070a36676f6f676c652f6164732f676f6f676c656164732f76332f636f6d6d6f6e2f746172676574696e675f73657474696e672e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22c5010a10546172676574696e6753657474696e67124e0a137461726765745f7265737472696374696f6e7318012003280b32312e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e5461726765745265737472696374696f6e12610a1d7461726765745f7265737472696374696f6e5f6f7065726174696f6e7318022003280b323a2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e5461726765745265737472696374696f6e4f7065726174696f6e22a8010a115461726765745265737472696374696f6e12650a13746172676574696e675f64696d656e73696f6e18012001280e32482e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e546172676574696e6744696d656e73696f6e456e756d2e546172676574696e6744696d656e73696f6e122c0a086269645f6f6e6c7918022001280b321a2e676f6f676c652e70726f746f6275662e426f6f6c56616c756522f4010a1a5461726765745265737472696374696f6e4f7065726174696f6e12550a086f70657261746f7218012001280e32432e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e5461726765745265737472696374696f6e4f7065726174696f6e2e4f70657261746f7212400a0576616c756518022001280b32312e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e5461726765745265737472696374696f6e223d0a084f70657261746f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112070a034144441002120a0a0652454d4f5645100342f0010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e4215546172676574696e6753657474696e6750726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56332e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56335c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a436f6d6d6f6e620670726f746f330ace030a3d676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f61645f67726f75705f61645f726f746174696f6e5f6d6f64652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322740a19416447726f75704164526f746174696f6e4d6f6465456e756d22570a15416447726f75704164526f746174696f6e4d6f6465120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120c0a084f5054494d495a45100212120a0e524f544154455f464f5245564552100342ef010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73421a416447726f75704164526f746174696f6e4d6f646550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330ab0030a33676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f61645f67726f75705f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322680a11416447726f7570537461747573456e756d22530a0d416447726f7570537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a07454e41424c45441002120a0a065041555345441003120b0a0752454d4f564544100442e7010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d734212416447726f757053746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330ae4050a31676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f61645f67726f75705f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73229f030a0f416447726f757054797065456e756d228b030a0b416447726f757054797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112130a0f5345415243485f5354414e44415244100212140a10444953504c41595f5354414e44415244100312180a1453484f5050494e475f50524f445543545f4144531004120d0a09484f54454c5f414453100612160a1253484f5050494e475f534d4152545f414453100712100a0c564944454f5f42554d5045521008121d0a19564944454f5f545255455f564945575f494e5f53545245414d1009121e0a1a564944454f5f545255455f564945575f494e5f444953504c4159100a12210a1d564944454f5f4e4f4e5f534b49505041424c455f494e5f53545245414d100b12130a0f564944454f5f4f555453545245414d100c12160a125345415243485f44594e414d49435f414453100d12230a1f53484f5050494e475f434f4d50415249534f4e5f4c495354494e475f414453100e12160a1250524f4d4f5445445f484f54454c5f414453100f12140a10564944454f5f524553504f4e53495645101042e5010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d734210416447726f75705479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330acf030a32676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f62696464696e675f736f757263652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732287010a1142696464696e67536f75726365456e756d22720a0d42696464696e67536f75726365120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001121d0a1943414d504149474e5f42494444494e475f53545241544547591005120c0a0841445f47524f5550100612160a1241445f47524f55505f435249544552494f4e100742e7010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73421242696464696e67536f7572636550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330ac8160a30676f6f676c652f6164732f676f6f676c656164732f76332f7265736f75726365732f61645f67726f75702e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365731a44676f6f676c652f6164732f676f6f676c656164732f76332f636f6d6d6f6e2f6578706c6f7265725f6175746f5f6f7074696d697a65725f73657474696e672e70726f746f1a36676f6f676c652f6164732f676f6f676c656164732f76332f636f6d6d6f6e2f746172676574696e675f73657474696e672e70726f746f1a3d676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f61645f67726f75705f61645f726f746174696f6e5f6d6f64652e70726f746f1a33676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f61645f67726f75705f7374617475732e70726f746f1a31676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f61645f67726f75705f747970652e70726f746f1a32676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f62696464696e675f736f757263652e70726f746f1a37676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f746172676574696e675f64696d656e73696f6e2e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22e00f0a07416447726f7570123f0a0d7265736f757263655f6e616d651801200128094228e04105fa41220a20676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570122c0a02696418032001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e04103122a0a046e616d6518042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565124e0a0673746174757318052001280e323e2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e416447726f7570537461747573456e756d2e416447726f7570537461747573124d0a0474797065180c2001280e323a2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e416447726f757054797065456e756d2e416447726f7570547970654203e0410512680a1061645f726f746174696f6e5f6d6f646518162001280e324e2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e416447726f75704164526f746174696f6e4d6f6465456e756d2e416447726f75704164526f746174696f6e4d6f6465125d0a0d626173655f61645f67726f757018122001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654228e04103fa41220a20676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570123b0a15747261636b696e675f75726c5f74656d706c617465180d2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565124e0a1575726c5f637573746f6d5f706172616d657465727318062003280b322f2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e437573746f6d506172616d6574657212590a0863616d706169676e180a2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654229e04105fa41230a21676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e12330a0e6370635f6269645f6d6963726f73180e2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512330a0e63706d5f6269645f6d6963726f73180f2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512360a117461726765745f6370615f6d6963726f73181b2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512380a0e6370765f6269645f6d6963726f7318112001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410312360a117461726765745f63706d5f6d6963726f73181a2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512310a0b7461726765745f726f6173181e2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123b0a1670657263656e745f6370635f6269645f6d6963726f7318142001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512650a1f6578706c6f7265725f6175746f5f6f7074696d697a65725f73657474696e6718152001280b323c2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e4578706c6f7265724175746f4f7074696d697a657253657474696e67126e0a1c646973706c61795f637573746f6d5f6269645f64696d656e73696f6e18172001280e32482e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e546172676574696e6744696d656e73696f6e456e756d2e546172676574696e6744696d656e73696f6e12360a1066696e616c5f75726c5f73756666697818182001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565124b0a11746172676574696e675f73657474696e6718192001280b32302e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e546172676574696e6753657474696e6712450a1b6566666563746976655f7461726765745f6370615f6d6963726f73181c2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410312680a1b6566666563746976655f7461726765745f6370615f736f75726365181d2001280e323e2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e42696464696e67536f75726365456e756d2e42696464696e67536f757263654203e0410312400a156566666563746976655f7461726765745f726f6173181f2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c75654203e0410312690a1c6566666563746976655f7461726765745f726f61735f736f7572636518202001280e323e2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e42696464696e67536f75726365456e756d2e42696464696e67536f757263654203e04103125b0a066c6162656c7318212003280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565422de04103fa41270a25676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f75704c6162656c3a4fea414c0a20676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f75701228637573746f6d6572732f7b637573746f6d65727d2f616447726f7570732f7b61645f67726f75707d42f9010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f7572636573420c416447726f757050726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56332e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56335c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5265736f7572636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

