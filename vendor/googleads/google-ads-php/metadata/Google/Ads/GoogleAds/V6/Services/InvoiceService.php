<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/services/invoice_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V6\Services;

class InvoiceService
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
        \GPBMetadata\Google\Api\Client::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0a95030a2a676f6f676c652f6164732f676f6f676c656164732f76362f636f6d6d6f6e2f64617465732e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e22570a094461746552616e676512170a0a73746172745f64617465180320012809480088010112150a08656e645f646174651804200128094801880101420d0a0b5f73746172745f64617465420b0a095f656e645f6461746542e5010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e420a446174657350726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56362e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56365c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a436f6d6d6f6e620670726f746f330a96040a31676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f6d6f6e74685f6f665f796561722e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d7322d1010a0f4d6f6e74684f6659656172456e756d22bd010a0b4d6f6e74684f6659656172120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a074a414e554152591002120c0a084645425255415259100312090a054d41524348100412090a05415052494c100512070a034d4159100612080a044a554e45100712080a044a554c591008120a0a064155475553541009120d0a0953455054454d424552100a120b0a074f43544f424552100b120c0a084e4f56454d424552100c120c0a08444543454d424552100d42e5010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d7342104d6f6e74684f665965617250726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f330a9f030a30676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f696e766f6963655f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d73225c0a0f496e766f69636554797065456e756d22490a0b496e766f69636554797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120f0a0b4352454449545f4d454d4f1002120b0a07494e564f494345100342e5010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d734210496e766f6963655479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f330aa0130a2f676f6f676c652f6164732f676f6f676c656164732f76362f7265736f75726365732f696e766f6963652e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365731a30676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f696e766f6963655f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22b90f0a07496e766f696365123f0a0d7265736f757263655f6e616d651801200128094228e04103fa41220a20676f6f676c656164732e676f6f676c65617069732e636f6d2f496e766f69636512140a0269641819200128094203e041034800880101124d0a047479706518032001280e323a2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732e496e766f69636554797065456e756d2e496e766f696365547970654203e04103121f0a0d62696c6c696e675f7365747570181a200128094203e04103480188010112250a137061796d656e74735f6163636f756e745f6964181b200128094203e04103480288010112250a137061796d656e74735f70726f66696c655f6964181c200128094203e041034803880101121c0a0a69737375655f64617465181d200128094203e041034804880101121a0a086475655f64617465181e200128094203e041034805880101124a0a12736572766963655f646174655f72616e676518092001280b32292e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4461746552616e67654203e04103121f0a0d63757272656e63795f636f6465181f200128094203e041034806880101122f0a2261646a7573746d656e74735f737562746f74616c5f616d6f756e745f6d6963726f731813200128034203e04103122a0a1d61646a7573746d656e74735f7461785f616d6f756e745f6d6963726f731814200128034203e04103122c0a1f61646a7573746d656e74735f746f74616c5f616d6f756e745f6d6963726f731815200128034203e0410312340a27726567756c61746f72795f636f7374735f737562746f74616c5f616d6f756e745f6d6963726f731816200128034203e04103122f0a22726567756c61746f72795f636f7374735f7461785f616d6f756e745f6d6963726f731817200128034203e0410312310a24726567756c61746f72795f636f7374735f746f74616c5f616d6f756e745f6d6963726f731818200128034203e0410312280a16737562746f74616c5f616d6f756e745f6d6963726f731821200128034203e04103480788010112230a117461785f616d6f756e745f6d6963726f731822200128034203e04103480888010112250a13746f74616c5f616d6f756e745f6d6963726f731823200128034203e04103480988010112230a11636f727265637465645f696e766f6963651824200128094203e04103480a880101121e0a117265706c616365645f696e766f696365731825200328094203e0410312190a077064665f75726c1826200128094203e04103480b88010112660a186163636f756e745f6275646765745f73756d6d617269657318122003280b323f2e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365732e496e766f6963652e4163636f756e7442756467657453756d6d6172794203e041031ad6040a144163636f756e7442756467657453756d6d617279121a0a08637573746f6d6572180a200128094203e041034800880101122b0a19637573746f6d65725f64657363726970746976655f6e616d65180b200128094203e04103480188010112200a0e6163636f756e745f627564676574180c200128094203e04103480288010112250a136163636f756e745f6275646765745f6e616d65180d200128094203e04103480388010112270a1570757263686173655f6f726465725f6e756d626572180e200128094203e04103480488010112280a16737562746f74616c5f616d6f756e745f6d6963726f73180f200128034203e04103480588010112230a117461785f616d6f756e745f6d6963726f731810200128034203e04103480688010112250a13746f74616c5f616d6f756e745f6d6963726f731811200128034203e04103480788010112540a1c62696c6c61626c655f61637469766974795f646174655f72616e676518092001280b32292e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4461746552616e67654203e04103420b0a095f637573746f6d6572421c0a1a5f637573746f6d65725f64657363726970746976655f6e616d6542110a0f5f6163636f756e745f62756467657442160a145f6163636f756e745f6275646765745f6e616d6542180a165f70757263686173655f6f726465725f6e756d62657242190a175f737562746f74616c5f616d6f756e745f6d6963726f7342140a125f7461785f616d6f756e745f6d6963726f7342160a145f746f74616c5f616d6f756e745f6d6963726f733a54ea41510a20676f6f676c656164732e676f6f676c65617069732e636f6d2f496e766f696365122d637573746f6d6572732f7b637573746f6d65725f69647d2f696e766f696365732f7b696e766f6963655f69647d42050a035f696442100a0e5f62696c6c696e675f736574757042160a145f7061796d656e74735f6163636f756e745f696442160a145f7061796d656e74735f70726f66696c655f6964420d0a0b5f69737375655f64617465420b0a095f6475655f6461746542100a0e5f63757272656e63795f636f646542190a175f737562746f74616c5f616d6f756e745f6d6963726f7342140a125f7461785f616d6f756e745f6d6963726f7342160a145f746f74616c5f616d6f756e745f6d6963726f7342140a125f636f727265637465645f696e766f696365420a0a085f7064665f75726c42f9010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f7572636573420c496e766f69636550726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56362e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56365c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a5265736f7572636573620670726f746f330a8e080a36676f6f676c652f6164732f676f6f676c656164732f76362f73657276696365732f696e766f6963655f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365731a2f676f6f676c652f6164732f676f6f676c656164732f76362f7265736f75726365732f696e766f6963652e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f22ba010a134c697374496e766f696365735265717565737412180a0b637573746f6d65725f69641801200128094203e04102121a0a0d62696c6c696e675f73657475701802200128094203e0410212170a0a69737375655f796561721803200128094203e0410212540a0b69737375655f6d6f6e746818042001280e323a2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732e4d6f6e74684f6659656172456e756d2e4d6f6e74684f66596561724203e0410222540a144c697374496e766f69636573526573706f6e7365123c0a08696e766f6963657318012003280b322a2e676f6f676c652e6164732e676f6f676c656164732e76362e7265736f75726365732e496e766f6963653290020a0e496e766f6963655365727669636512e0010a0c4c697374496e766f6963657312352e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4c697374496e766f69636573526571756573741a362e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e4c697374496e766f69636573526573706f6e7365226182d3e493022812262f76362f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f696e766f69636573da4130637573746f6d65725f69642c62696c6c696e675f73657475702c69737375655f796561722c69737375655f6d6f6e74681a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d42fa010a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365734213496e766f6963655365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56362e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56365c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

