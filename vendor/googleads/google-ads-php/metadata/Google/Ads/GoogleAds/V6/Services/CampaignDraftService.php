<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/services/campaign_draft_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V6\Services;

class CampaignDraftService
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Http::initOnce();
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Api\FieldBehavior::initOnce();
        \GPBMetadata\Google\Api\Resource::initOnce();
        \GPBMetadata\Google\Protobuf\Any::initOnce();
        \GPBMetadata\Google\Protobuf\Duration::initOnce();
        \GPBMetadata\Google\Protobuf\FieldMask::initOnce();
        \GPBMetadata\Google\Api\Client::initOnce();
        \GPBMetadata\Google\Rpc\Status::initOnce();
        \GPBMetadata\Google\Protobuf\GPBEmpty::initOnce();
        \GPBMetadata\Google\Longrunning\Operations::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0aef030a39676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f63616d706169676e5f64726166745f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d73229a010a1743616d706169676e4472616674537461747573456e756d227f0a1343616d706169676e4472616674537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120c0a0850524f504f5345441002120b0a0752454d4f5645441003120d0a0950524f4d4f54494e471005120c0a0850524f4d4f544544100412120a0e50524f4d4f54455f4641494c4544100642ed010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d73421843616d706169676e447261667453746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f330ac3030a39676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f726573706f6e73655f636f6e74656e745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d73226f0a17526573706f6e7365436f6e74656e7454797065456e756d22540a13526573706f6e7365436f6e74656e7454797065120f0a0b554e535045434946494544100012160a125245534f555243455f4e414d455f4f4e4c59100112140a104d555441424c455f5245534f55524345100242ed010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d734218526573706f6e7365436f6e74656e745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f330aef080a36676f6f676c652f6164732f676f6f676c656164732f76362f7265736f75726365732f63616d706169676e5f64726166742e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365731a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22ad050a0d43616d706169676e447261667412450a0d7265736f757263655f6e616d65180120012809422ee04105fa41280a26676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4472616674121a0a0864726166745f69641809200128034203e04103480088010112450a0d626173655f63616d706169676e180a200128094229e04105fa41230a21676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e480188010112110a046e616d65180b20012809480288010112460a0e64726166745f63616d706169676e180c200128094229e04103fa41230a21676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4803880101125f0a0673746174757318062001280e324a2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732e43616d706169676e4472616674537461747573456e756d2e43616d706169676e44726166745374617475734203e0410312280a166861735f6578706572696d656e745f72756e6e696e67180d200128084203e04103480488010112280a166c6f6e675f72756e6e696e675f6f7065726174696f6e180e200128094203e0410348058801013a71ea416e0a26676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e44726166741244637573746f6d6572732f7b637573746f6d65725f69647d2f63616d706169676e4472616674732f7b626173655f63616d706169676e5f69647d7e7b64726166745f69647d420b0a095f64726166745f696442100a0e5f626173655f63616d706169676e42070a055f6e616d6542110a0f5f64726166745f63616d706169676e42190a175f6861735f6578706572696d656e745f72756e6e696e6742190a175f6c6f6e675f72756e6e696e675f6f7065726174696f6e42ff010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f7572636573421243616d706169676e447261667450726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56362e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56365c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a5265736f7572636573620670726f746f330ac8160a3d676f6f676c652f6164732f676f6f676c656164732f76362f73657276696365732f63616d706169676e5f64726166745f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365731a36676f6f676c652f6164732f676f6f676c656164732f76362f7265736f75726365732f63616d706169676e5f64726166742e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a23676f6f676c652f6c6f6e6772756e6e696e672f6f7065726174696f6e732e70726f746f1a20676f6f676c652f70726f746f6275662f6669656c645f6d61736b2e70726f746f1a17676f6f676c652f7270632f7374617475732e70726f746f22600a1747657443616d706169676e44726166745265717565737412450a0d7265736f757263655f6e616d65180120012809422ee04102fa41280a26676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e447261667422a5020a1b4d757461746543616d706169676e4472616674735265717565737412180a0b637573746f6d65725f69641801200128094203e0410212510a0a6f7065726174696f6e7318022003280b32382e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e43616d706169676e44726166744f7065726174696f6e4203e0410212170a0f7061727469616c5f6661696c75726518032001280812150a0d76616c69646174655f6f6e6c7918042001280812690a15726573706f6e73655f636f6e74656e745f7479706518052001280e324a2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732e526573706f6e7365436f6e74656e7454797065456e756d2e526573706f6e7365436f6e74656e7454797065223a0a1b50726f6d6f746543616d706169676e447261667452657175657374121b0a0e63616d706169676e5f64726166741801200128094203e0410222f0010a1643616d706169676e44726166744f7065726174696f6e122f0a0b7570646174655f6d61736b18042001280b321a2e676f6f676c652e70726f746f6275662e4669656c644d61736b12420a0663726561746518012001280b32302e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365732e43616d706169676e4472616674480012420a0675706461746518022001280b32302e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365732e43616d706169676e4472616674480012100a0672656d6f76651803200128094800420b0a096f7065726174696f6e229f010a1c4d757461746543616d706169676e447261667473526573706f6e736512310a157061727469616c5f6661696c7572655f6572726f7218032001280b32122e676f6f676c652e7270632e537461747573124c0a07726573756c747318022003280b323b2e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4d757461746543616d706169676e4472616674526573756c74227c0a194d757461746543616d706169676e4472616674526573756c7412150a0d7265736f757263655f6e616d6518012001280912480a0e63616d706169676e5f647261667418022001280b32302e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365732e43616d706169676e44726166742293010a234c69737443616d706169676e44726166744173796e634572726f72735265717565737412450a0d7265736f757263655f6e616d65180120012809422ee04102fa41280a26676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e447261667412120a0a706167655f746f6b656e18022001280912110a09706167655f73697a6518032001280522630a244c69737443616d706169676e44726166744173796e634572726f7273526573706f6e736512220a066572726f727318012003280b32122e676f6f676c652e7270632e53746174757312170a0f6e6578745f706167655f746f6b656e18022001280932fa070a1443616d706169676e44726166745365727669636512c9010a1047657443616d706169676e447261667412392e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e47657443616d706169676e4472616674526571756573741a302e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365732e43616d706169676e4472616674224882d3e493023212302f76362f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f63616d706169676e4472616674732f2a7dda410d7265736f757263655f6e616d6512ee010a144d757461746543616d706169676e447261667473123d2e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4d757461746543616d706169676e447261667473526571756573741a3e2e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4d757461746543616d706169676e447261667473526573706f6e7365225782d3e493023822332f76362f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f63616d706169676e4472616674733a6d75746174653a012ada4116637573746f6d65725f69642c6f7065726174696f6e7312fd010a1450726f6d6f746543616d706169676e4472616674123d2e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e50726f6d6f746543616d706169676e4472616674526571756573741a1d2e676f6f676c652e6c6f6e6772756e6e696e672e4f7065726174696f6e22860182d3e493023e22392f76362f7b63616d706169676e5f64726166743d637573746f6d6572732f2a2f63616d706169676e4472616674732f2a7d3a70726f6d6f74653a012ada410e63616d706169676e5f6472616674ca412e0a15676f6f676c652e70726f746f6275662e456d7074791215676f6f676c652e70726f746f6275662e456d7074791287020a1c4c69737443616d706169676e44726166744173796e634572726f727312452e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4c69737443616d706169676e44726166744173796e634572726f7273526571756573741a462e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4c69737443616d706169676e44726166744173796e634572726f7273526573706f6e7365225882d3e493024212402f76362f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f63616d706169676e4472616674732f2a7d3a6c6973744173796e634572726f7273da410d7265736f757263655f6e616d651a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d4280020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e7365727669636573421943616d706169676e44726166745365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56362e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56365c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

