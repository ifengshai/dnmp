<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/campaign_budget_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V5\Services;

class CampaignBudgetService
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Http::initOnce();
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Api\Resource::initOnce();
        \GPBMetadata\Google\Api\FieldBehavior::initOnce();
        \GPBMetadata\Google\Api\Client::initOnce();
        \GPBMetadata\Google\Protobuf\FieldMask::initOnce();
        \GPBMetadata\Google\Protobuf\Any::initOnce();
        \GPBMetadata\Google\Rpc\Status::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ac5030a3a676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6275646765745f64656c69766572795f6d6574686f642e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73226f0a1842756467657444656c69766572794d6574686f64456e756d22530a1442756467657444656c69766572794d6574686f64120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120c0a085354414e444152441002120f0a0b414343454c455241544544100342ee010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421942756467657444656c69766572794d6574686f6450726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330aa3030a31676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6275646765745f706572696f642e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73225e0a10427564676574506572696f64456e756d224a0a0c427564676574506572696f64120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112090a054441494c59100212110a0d435553544f4d5f504552494f44100542e6010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734211427564676574506572696f6450726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330a9f030a31676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6275646765745f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73225a0a10427564676574537461747573456e756d22460a0c427564676574537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a07454e41424c45441002120b0a0752454d4f564544100342e6010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421142756467657453746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ab4030a2f676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6275646765745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322730a0e42756467657454797065456e756d22610a0a42756467657454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120c0a085354414e44415244100212180a14484f54454c5f4144535f434f4d4d495353494f4e1003120d0a0946495845445f435041100442e4010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73420f4275646765745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ac3030a39676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f726573706f6e73655f636f6e74656e745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73226f0a17526573706f6e7365436f6e74656e7454797065456e756d22540a13526573706f6e7365436f6e74656e7454797065120f0a0b554e535045434946494544100012160a125245534f555243455f4e414d455f4f4e4c59100112140a104d555441424c455f5245534f55524345100242ed010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734218526573706f6e7365436f6e74656e745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330a9f100a37676f6f676c652f6164732f676f6f676c656164732f76352f7265736f75726365732f63616d706169676e5f6275646765742e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365731a31676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6275646765745f706572696f642e70726f746f1a31676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6275646765745f7374617475732e70726f746f1a2f676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6275646765745f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22c40b0a0e43616d706169676e42756467657412460a0d7265736f757263655f6e616d65180120012809422fe04105fa41290a27676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e42756467657412140a0269641813200128034203e04103480088010112110a046e616d651814200128094801880101121a0a0d616d6f756e745f6d6963726f73181520012803480288010112200a13746f74616c5f616d6f756e745f6d6963726f73181620012803480388010112510a0673746174757318062001280e323c2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e427564676574537461747573456e756d2e4275646765745374617475734203e0410312650a0f64656c69766572795f6d6574686f6418072001280e324c2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e42756467657444656c69766572794d6574686f64456e756d2e42756467657444656c69766572794d6574686f64121e0a116578706c696369746c795f736861726564181720012808480488010112210a0f7265666572656e63655f636f756e741818200128034203e04103480588010112280a166861735f7265636f6d6d656e6465645f6275646765741819200128084203e04103480688010112320a207265636f6d6d656e6465645f6275646765745f616d6f756e745f6d6963726f73181a200128034203e04103480788010112510a06706572696f64180d2001280e323c2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e427564676574506572696f64456e756d2e427564676574506572696f644203e0410512430a317265636f6d6d656e6465645f6275646765745f657374696d617465645f6368616e67655f7765656b6c795f636c69636b73181b200128034203e04103480888010112480a367265636f6d6d656e6465645f6275646765745f657374696d617465645f6368616e67655f7765656b6c795f636f73745f6d6963726f73181c200128034203e04103480988010112490a377265636f6d6d656e6465645f6275646765745f657374696d617465645f6368616e67655f7765656b6c795f696e746572616374696f6e73181d200128034203e04103480a88010112420a307265636f6d6d656e6465645f6275646765745f657374696d617465645f6368616e67655f7765656b6c795f7669657773181e200128034203e04103480b880101124b0a047479706518122001280e32382e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e42756467657454797065456e756d2e427564676574547970654203e041053a64ea41610a27676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4275646765741236637573746f6d6572732f7b637573746f6d65727d2f63616d706169676e427564676574732f7b63616d706169676e5f6275646765747d42050a035f696442070a055f6e616d6542100a0e5f616d6f756e745f6d6963726f7342160a145f746f74616c5f616d6f756e745f6d6963726f7342140a125f6578706c696369746c795f73686172656442120a105f7265666572656e63655f636f756e7442190a175f6861735f7265636f6d6d656e6465645f62756467657442230a215f7265636f6d6d656e6465645f6275646765745f616d6f756e745f6d6963726f7342340a325f7265636f6d6d656e6465645f6275646765745f657374696d617465645f6368616e67655f7765656b6c795f636c69636b7342390a375f7265636f6d6d656e6465645f6275646765745f657374696d617465645f6368616e67655f7765656b6c795f636f73745f6d6963726f73423a0a385f7265636f6d6d656e6465645f6275646765745f657374696d617465645f6368616e67655f7765656b6c795f696e746572616374696f6e7342330a315f7265636f6d6d656e6465645f6275646765745f657374696d617465645f6368616e67655f7765656b6c795f76696577734280020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f7572636573421343616d706169676e42756467657450726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56352e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56355c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a5265736f7572636573620670726f746f330afa0f0a3e676f6f676c652f6164732f676f6f676c656164732f76352f73657276696365732f63616d706169676e5f6275646765745f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365731a37676f6f676c652f6164732f676f6f676c656164732f76352f7265736f75726365732f63616d706169676e5f6275646765742e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a20676f6f676c652f70726f746f6275662f6669656c645f6d61736b2e70726f746f1a17676f6f676c652f7270632f7374617475732e70726f746f22620a1847657443616d706169676e4275646765745265717565737412460a0d7265736f757263655f6e616d65180120012809422fe04102fa41290a27676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e42756467657422a7020a1c4d757461746543616d706169676e427564676574735265717565737412180a0b637573746f6d65725f69641801200128094203e0410212520a0a6f7065726174696f6e7318022003280b32392e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e43616d706169676e4275646765744f7065726174696f6e4203e0410212170a0f7061727469616c5f6661696c75726518032001280812150a0d76616c69646174655f6f6e6c7918042001280812690a15726573706f6e73655f636f6e74656e745f7479706518052001280e324a2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e526573706f6e7365436f6e74656e7454797065456e756d2e526573706f6e7365436f6e74656e745479706522f3010a1743616d706169676e4275646765744f7065726174696f6e122f0a0b7570646174655f6d61736b18042001280b321a2e676f6f676c652e70726f746f6275662e4669656c644d61736b12430a0663726561746518012001280b32312e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e43616d706169676e427564676574480012430a0675706461746518022001280b32312e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e43616d706169676e427564676574480012100a0672656d6f76651803200128094800420b0a096f7065726174696f6e22a1010a1d4d757461746543616d706169676e42756467657473526573706f6e736512310a157061727469616c5f6661696c7572655f6572726f7218032001280b32122e676f6f676c652e7270632e537461747573124d0a07726573756c747318022003280b323c2e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e4d757461746543616d706169676e427564676574526573756c74227f0a1a4d757461746543616d706169676e427564676574526573756c7412150a0d7265736f757263655f6e616d65180120012809124a0a0f63616d706169676e5f62756467657418022001280b32312e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e43616d706169676e42756467657432f9030a1543616d706169676e4275646765745365727669636512cd010a1147657443616d706169676e427564676574123a2e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e47657443616d706169676e427564676574526571756573741a312e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e43616d706169676e427564676574224982d3e493023312312f76352f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f63616d706169676e427564676574732f2a7dda410d7265736f757263655f6e616d6512f2010a154d757461746543616d706169676e42756467657473123e2e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e4d757461746543616d706169676e42756467657473526571756573741a3f2e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e4d757461746543616d706169676e42756467657473526573706f6e7365225882d3e493023922342f76352f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f63616d706169676e427564676574733a6d75746174653a012ada4116637573746f6d65725f69642c6f7065726174696f6e731a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d4281020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e7365727669636573421a43616d706169676e4275646765745365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56352e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56355c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

