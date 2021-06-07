<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/campaign_experiment_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Services;

class CampaignExperimentService
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
        \GPBMetadata\Google\Protobuf\Any::initOnce();
        \GPBMetadata\Google\Rpc\Status::initOnce();
        \GPBMetadata\Google\Protobuf\Duration::initOnce();
        \GPBMetadata\Google\Protobuf\GPBEmpty::initOnce();
        \GPBMetadata\Google\Longrunning\Operations::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ad5040a3e676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f63616d706169676e5f6578706572696d656e745f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322f6010a1c43616d706169676e4578706572696d656e74537461747573456e756d22d5010a1843616d706169676e4578706572696d656e74537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112100a0c494e495449414c495a494e47100212190a15494e495449414c495a4154494f4e5f4641494c45441008120b0a07454e41424c45441003120d0a094752414455415445441004120b0a0752454d4f5645441005120d0a0950524f4d4f54494e47100612140a1050524f4d4f54494f4e5f4641494c45441009120c0a0850524f4d4f544544100712120a0e454e4445445f4d414e55414c4c59100a42f2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73421d43616d706169676e4578706572696d656e7453746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330aff030a4a676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f63616d706169676e5f6578706572696d656e745f747261666669635f73706c69745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73228a010a2643616d706169676e4578706572696d656e745472616666696353706c697454797065456e756d22600a2243616d706169676e4578706572696d656e745472616666696353706c697454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112100a0c52414e444f4d5f51554552591002120a0a06434f4f4b4945100342fc010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73422743616d706169676e4578706572696d656e745472616666696353706c69745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330ac00c0a3b676f6f676c652f6164732f676f6f676c656164732f76332f7265736f75726365732f63616d706169676e5f6578706572696d656e742e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365731a4a676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f63616d706169676e5f6578706572696d656e745f747261666669635f73706c69745f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f2288080a1243616d706169676e4578706572696d656e74124a0a0d7265736f757263655f6e616d651801200128094233e04105fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4578706572696d656e74122c0a02696418022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410312640a0e63616d706169676e5f647261667418032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565422ee04105fa41280a26676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4472616674122a0a046e616d6518042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512310a0b6465736372697074696f6e18052001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565123f0a15747261666669635f73706c69745f70657263656e7418062001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e041051289010a12747261666669635f73706c69745f7479706518072001280e32682e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e43616d706169676e4578706572696d656e745472616666696353706c697454797065456e756d2e43616d706169676e4578706572696d656e745472616666696353706c6974547970654203e0410512640a136578706572696d656e745f63616d706169676e18082001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654229e04103fa41230a21676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e12690a0673746174757318092001280e32542e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e43616d706169676e4578706572696d656e74537461747573456e756d2e43616d706169676e4578706572696d656e745374617475734203e0410312410a166c6f6e675f72756e6e696e675f6f7065726174696f6e180a2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312300a0a73746172745f64617465180b2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565122e0a08656e645f64617465180c2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75653a70ea416d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4578706572696d656e74123e637573746f6d6572732f7b637573746f6d65727d2f63616d706169676e4578706572696d656e74732f7b63616d706169676e5f6578706572696d656e747d4284020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f7572636573421743616d706169676e4578706572696d656e7450726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56332e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56335c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5265736f7572636573620670726f746f330aef1f0a42676f6f676c652f6164732f676f6f676c656164732f76332f73657276696365732f63616d706169676e5f6578706572696d656e745f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a23676f6f676c652f6c6f6e6772756e6e696e672f6f7065726174696f6e732e70726f746f1a1b676f6f676c652f70726f746f6275662f656d7074792e70726f746f1a20676f6f676c652f70726f746f6275662f6669656c645f6d61736b2e70726f746f1a17676f6f676c652f7270632f7374617475732e70726f746f226a0a1c47657443616d706169676e4578706572696d656e7452657175657374124a0a0d7265736f757263655f6e616d651801200128094233e04102fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4578706572696d656e7422c4010a204d757461746543616d706169676e4578706572696d656e74735265717565737412180a0b637573746f6d65725f69641801200128094203e0410212560a0a6f7065726174696f6e7318022003280b323d2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e43616d706169676e4578706572696d656e744f7065726174696f6e4203e0410212170a0f7061727469616c5f6661696c75726518032001280812150a0d76616c69646174655f6f6e6c7918042001280822b6010a1b43616d706169676e4578706572696d656e744f7065726174696f6e122f0a0b7570646174655f6d61736b18032001280b321a2e676f6f676c652e70726f746f6275662e4669656c644d61736b12470a0675706461746518012001280b32352e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e43616d706169676e4578706572696d656e74480012100a0672656d6f76651802200128094800420b0a096f7065726174696f6e22a9010a214d757461746543616d706169676e4578706572696d656e7473526573706f6e736512310a157061727469616c5f6661696c7572655f6572726f7218032001280b32122e676f6f676c652e7270632e53746174757312510a07726573756c747318022003280b32402e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d757461746543616d706169676e4578706572696d656e74526573756c7422370a1e4d757461746543616d706169676e4578706572696d656e74526573756c7412150a0d7265736f757263655f6e616d6518012001280922ab010a1f43726561746543616d706169676e4578706572696d656e745265717565737412180a0b637573746f6d65725f69641801200128094203e0410212570a1363616d706169676e5f6578706572696d656e7418022001280b32352e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e43616d706169676e4578706572696d656e744203e0410212150a0d76616c69646174655f6f6e6c79180320012808223f0a2043726561746543616d706169676e4578706572696d656e744d65746164617461121b0a1363616d706169676e5f6578706572696d656e7418012001280922630a21477261647561746543616d706169676e4578706572696d656e745265717565737412200a1363616d706169676e5f6578706572696d656e741801200128094203e04102121c0a0f63616d706169676e5f6275646765741802200128094203e0410222400a22477261647561746543616d706169676e4578706572696d656e74526573706f6e7365121a0a126772616475617465645f63616d706169676e18012001280922440a2050726f6d6f746543616d706169676e4578706572696d656e745265717565737412200a1363616d706169676e5f6578706572696d656e741801200128094203e0410222400a1c456e6443616d706169676e4578706572696d656e745265717565737412200a1363616d706169676e5f6578706572696d656e741801200128094203e04102229d010a284c69737443616d706169676e4578706572696d656e744173796e634572726f727352657175657374124a0a0d7265736f757263655f6e616d651801200128094233e04102fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4578706572696d656e7412120a0a706167655f746f6b656e18022001280912110a09706167655f73697a6518032001280522680a294c69737443616d706169676e4578706572696d656e744173796e634572726f7273526573706f6e736512220a066572726f727318012003280b32122e676f6f676c652e7270632e53746174757312170a0f6e6578745f706167655f746f6b656e180220012809328d0f0a1943616d706169676e4578706572696d656e745365727669636512dd010a1547657443616d706169676e4578706572696d656e74123e2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e47657443616d706169676e4578706572696d656e74526571756573741a352e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e43616d706169676e4578706572696d656e74224d82d3e493023712352f76332f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f63616d706169676e4578706572696d656e74732f2a7dda410d7265736f757263655f6e616d6512c1020a1843726561746543616d706169676e4578706572696d656e7412412e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e43726561746543616d706169676e4578706572696d656e74526571756573741a1d2e676f6f676c652e6c6f6e6772756e6e696e672e4f7065726174696f6e22c20182d3e493023d22382f76332f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f63616d706169676e4578706572696d656e74733a6372656174653a012ada411f637573746f6d65725f69642c63616d706169676e5f6578706572696d656e74ca415a0a15676f6f676c652e70726f746f6275662e456d7074791241676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e43726561746543616d706169676e4578706572696d656e744d657461646174611282020a194d757461746543616d706169676e4578706572696d656e747312422e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d757461746543616d706169676e4578706572696d656e7473526571756573741a432e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4d757461746543616d706169676e4578706572696d656e7473526573706f6e7365225c82d3e493023d22382f76332f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f63616d706169676e4578706572696d656e74733a6d75746174653a012ada4116637573746f6d65725f69642c6f7065726174696f6e73129e020a1a477261647561746543616d706169676e4578706572696d656e7412432e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e477261647561746543616d706169676e4578706572696d656e74526571756573741a442e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e477261647561746543616d706169676e4578706572696d656e74526573706f6e7365227582d3e493024922442f76332f7b63616d706169676e5f6578706572696d656e743d637573746f6d6572732f2a2f63616d706169676e4578706572696d656e74732f2a7d3a67726164756174653a012ada412363616d706169676e5f6578706572696d656e742c63616d706169676e5f6275646765741296020a1950726f6d6f746543616d706169676e4578706572696d656e7412422e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e50726f6d6f746543616d706169676e4578706572696d656e74526571756573741a1d2e676f6f676c652e6c6f6e6772756e6e696e672e4f7065726174696f6e22950182d3e493024822432f76332f7b63616d706169676e5f6578706572696d656e743d637573746f6d6572732f2a2f63616d706169676e4578706572696d656e74732f2a7d3a70726f6d6f74653a012ada411363616d706169676e5f6578706572696d656e74ca412e0a15676f6f676c652e70726f746f6275662e456d7074791215676f6f676c652e70726f746f6275662e456d70747912d1010a15456e6443616d706169676e4578706572696d656e74123e2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e456e6443616d706169676e4578706572696d656e74526571756573741a162e676f6f676c652e70726f746f6275662e456d707479226082d3e4930244223f2f76332f7b63616d706169676e5f6578706572696d656e743d637573746f6d6572732f2a2f63616d706169676e4578706572696d656e74732f2a7d3a656e643a012ada411363616d706169676e5f6578706572696d656e74129b020a214c69737443616d706169676e4578706572696d656e744173796e634572726f7273124a2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4c69737443616d706169676e4578706572696d656e744173796e634572726f7273526571756573741a4b2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4c69737443616d706169676e4578706572696d656e744173796e634572726f7273526573706f6e7365225d82d3e493024712452f76332f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f63616d706169676e4578706572696d656e74732f2a7d3a6c6973744173796e634572726f7273da410d7265736f757263655f6e616d651a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d4285020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7365727669636573421e43616d706169676e4578706572696d656e745365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56332e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56335c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

