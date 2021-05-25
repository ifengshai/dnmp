<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/services/offline_user_data_job_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Services;

class OfflineUserDataJobService
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
        \GPBMetadata\Google\Protobuf\Any::initOnce();
        \GPBMetadata\Google\Rpc\Status::initOnce();
        \GPBMetadata\Google\Protobuf\Duration::initOnce();
        \GPBMetadata\Google\Protobuf\GPBEmpty::initOnce();
        \GPBMetadata\Google\Longrunning\Operations::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ab5130a36676f6f676c652f6164732f676f6f676c656164732f76332f636f6d6d6f6e2f6f66666c696e655f757365725f646174612e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22c9020a164f66666c696e655573657241646472657373496e666f12370a116861736865645f66697273745f6e616d6518012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512360a106861736865645f6c6173745f6e616d6518022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565122a0a046369747918032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565122b0a05737461746518042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512320a0c636f756e7472795f636f646518052001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512310a0b706f7374616c5f636f646518062001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756522d1020a0e557365724964656e74696669657212340a0c6861736865645f656d61696c18012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654800123b0a136861736865645f70686f6e655f6e756d62657218022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565480012310a096d6f62696c655f696418032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654800123b0a1374686972645f70617274795f757365725f696418042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654800124e0a0c616464726573735f696e666f18052001280b32362e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e4f66666c696e655573657241646472657373496e666f4800420c0a0a6964656e74696669657222af030a145472616e73616374696f6e417474726962757465123b0a157472616e73616374696f6e5f646174655f74696d6518012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565123f0a197472616e73616374696f6e5f616d6f756e745f6d6963726f7318022001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512330a0d63757272656e63795f636f646518032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512370a11636f6e76657273696f6e5f616374696f6e18042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565122e0a086f726465725f696418052001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512470a0f73746f72655f61747472696275746518062001280b322e2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e53746f726541747472696275746512320a0c637573746f6d5f76616c756518072001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756522420a0e53746f726541747472696275746512300a0a73746f72655f636f646518012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756522a9010a08557365724461746112480a10757365725f6964656e7469666965727318012003280b322e2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e557365724964656e74696669657212530a157472616e73616374696f6e5f61747472696275746518022001280b32342e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e5472616e73616374696f6e41747472696275746522500a1d437573746f6d65724d61746368557365724c6973744d65746164617461122f0a09757365725f6c69737418012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565229d020a1253746f726553616c65734d6574616461746112360a106c6f79616c74795f6672616374696f6e18012001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512410a1b7472616e73616374696f6e5f75706c6f61645f6672616374696f6e18022001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512300a0a637573746f6d5f6b657918042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565125a0a1474686972645f70617274795f6d6574616461746118032001280b323c2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e53746f726553616c6573546869726450617274794d65746164617461228e030a1c53746f726553616c6573546869726450617274794d6574616461746112410a1b616476657274697365725f75706c6f61645f646174655f74696d6518012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512400a1a76616c69645f7472616e73616374696f6e5f6672616374696f6e18022001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123c0a16706172746e65725f6d617463685f6672616374696f6e18032001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123d0a17706172746e65725f75706c6f61645f6672616374696f6e18042001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123b0a156272696467655f6d61705f76657273696f6e5f696418052001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565122f0a0a706172746e65725f696418062001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756542ef010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e42144f66666c696e65557365724461746150726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56332e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56335c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a436f6d6d6f6e620670726f746f330a9d040a48676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f6f66666c696e655f757365725f646174615f6a6f625f6661696c7572655f726561736f6e2e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322ad010a234f66666c696e6555736572446174614a6f624661696c757265526561736f6e456e756d2285010a1f4f66666c696e6555736572446174614a6f624661696c757265526561736f6e120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112250a21494e53554646494349454e545f4d4154434845445f5452414e53414354494f4e531002121d0a19494e53554646494349454e545f5452414e53414354494f4e53100342f9010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7342244f66666c696e6555736572446174614a6f624661696c757265526561736f6e50726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330aec030a40676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f6f66666c696e655f757365725f646174615f6a6f625f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73228b010a1c4f66666c696e6555736572446174614a6f62537461747573456e756d226b0a184f66666c696e6555736572446174614a6f62537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a0750454e44494e471002120b0a0752554e4e494e471003120b0a07535543434553531004120a0a064641494c4544100542f2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73421d4f66666c696e6555736572446174614a6f6253746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330a98040a3e676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f6f66666c696e655f757365725f646174615f6a6f625f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322bb010a1a4f66666c696e6555736572446174614a6f6254797065456e756d229c010a164f66666c696e6555736572446174614a6f6254797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112220a1e53544f52455f53414c45535f55504c4f41445f46495253545f5041525459100212220a1e53544f52455f53414c45535f55504c4f41445f54484952445f50415254591003121c0a18435553544f4d45525f4d415443485f555345525f4c495354100442f0010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73421b4f66666c696e6555736572446174614a6f625479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330a9a0c0a3d676f6f676c652f6164732f676f6f676c656164732f76332f7265736f75726365732f6f66666c696e655f757365725f646174615f6a6f622e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365731a48676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f6f66666c696e655f757365725f646174615f6a6f625f6661696c7572655f726561736f6e2e70726f746f1a40676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f6f66666c696e655f757365725f646174615f6a6f625f7374617475732e70726f746f1a3e676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f6f66666c696e655f757365725f646174615f6a6f625f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22e0060a124f66666c696e6555736572446174614a6f62124a0a0d7265736f757263655f6e616d651801200128094233e04105fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f4f66666c696e6555736572446174614a6f62122c0a02696418022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410312350a0b65787465726e616c5f696418032001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410312630a047479706518042001280e32502e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e4f66666c696e6555736572446174614a6f6254797065456e756d2e4f66666c696e6555736572446174614a6f62547970654203e0410312690a0673746174757318052001280e32542e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e4f66666c696e6555736572446174614a6f62537461747573456e756d2e4f66666c696e6555736572446174614a6f625374617475734203e04103127f0a0e6661696c7572655f726561736f6e18062001280e32622e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e4f66666c696e6555736572446174614a6f624661696c757265526561736f6e456e756d2e4f66666c696e6555736572446174614a6f624661696c757265526561736f6e4203e04103126f0a21637573746f6d65725f6d617463685f757365725f6c6973745f6d6574616461746118072001280b323d2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e437573746f6d65724d61746368557365724c6973744d657461646174614203e04103480012570a1473746f72655f73616c65735f6d6574616461746118082001280b32322e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e53746f726553616c65734d657461646174614203e0410348003a72ea416f0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f4f66666c696e6555736572446174614a6f621240637573746f6d6572732f7b637573746f6d65727d2f6f66666c696e6555736572446174614a6f62732f7b6f66666c696e655f757365725f646174615f6a6f627d420a0a086d657461646174614284020a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f757263657342174f66666c696e6555736572446174614a6f6250726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56332e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56335c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5265736f7572636573620670726f746f330a81150a44676f6f676c652f6164732f676f6f676c656164732f76332f73657276696365732f6f66666c696e655f757365725f646174615f6a6f625f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365731a3d676f6f676c652f6164732f676f6f676c656164732f76332f7265736f75726365732f6f66666c696e655f757365725f646174615f6a6f622e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a23676f6f676c652f6c6f6e6772756e6e696e672f6f7065726174696f6e732e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a17676f6f676c652f7270632f7374617475732e70726f746f2284010a1f4372656174654f66666c696e6555736572446174614a6f625265717565737412180a0b637573746f6d65725f69641801200128094203e0410212470a036a6f6218022001280b32352e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e4f66666c696e6555736572446174614a6f624203e0410222390a204372656174654f66666c696e6555736572446174614a6f62526573706f6e736512150a0d7265736f757263655f6e616d65180120012809226a0a1c4765744f66666c696e6555736572446174614a6f6252657175657374124a0a0d7265736f757263655f6e616d651801200128094233e04102fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f4f66666c696e6555736572446174614a6f62226a0a1c52756e4f66666c696e6555736572446174614a6f6252657175657374124a0a0d7265736f757263655f6e616d651801200128094233e04102fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f4f66666c696e6555736572446174614a6f622288020a264164644f66666c696e6555736572446174614a6f624f7065726174696f6e7352657175657374124a0a0d7265736f757263655f6e616d651801200128094233e04102fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f4f66666c696e6555736572446174614a6f62123a0a16656e61626c655f7061727469616c5f6661696c75726518022001280b321a2e676f6f676c652e70726f746f6275662e426f6f6c56616c756512560a0a6f7065726174696f6e7318032003280b323d2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4f66666c696e6555736572446174614a6f624f7065726174696f6e4203e0410222b8010a1b4f66666c696e6555736572446174614a6f624f7065726174696f6e123a0a0663726561746518012001280b32282e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e55736572446174614800123a0a0672656d6f766518022001280b32282e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e2e5573657244617461480012140a0a72656d6f76655f616c6c1803200128084800420b0a096f7065726174696f6e225c0a274164644f66666c696e6555736572446174614a6f624f7065726174696f6e73526573706f6e736512310a157061727469616c5f6661696c7572655f6572726f7218012001280b32122e676f6f676c652e7270632e53746174757332b8080a194f66666c696e6555736572446174614a6f625365727669636512f8010a184372656174654f66666c696e6555736572446174614a6f6212412e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4372656174654f66666c696e6555736572446174614a6f62526571756573741a422e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4372656174654f66666c696e6555736572446174614a6f62526573706f6e7365225582d3e493023d22382f76332f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f6f66666c696e6555736572446174614a6f62733a6372656174653a012ada410f637573746f6d65725f69642c6a6f6212dd010a154765744f66666c696e6555736572446174614a6f62123e2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4765744f66666c696e6555736572446174614a6f62526571756573741a352e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e4f66666c696e6555736572446174614a6f62224d82d3e493023712352f76332f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f6f66666c696e6555736572446174614a6f62732f2a7dda410d7265736f757263655f6e616d6512a1020a1f4164644f66666c696e6555736572446174614a6f624f7065726174696f6e7312482e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4164644f66666c696e6555736572446174614a6f624f7065726174696f6e73526571756573741a492e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e4164644f66666c696e6555736572446174614a6f624f7065726174696f6e73526573706f6e7365226982d3e493024822432f76332f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f6f66666c696e6555736572446174614a6f62732f2a7d3a6164644f7065726174696f6e733a012ada41187265736f757263655f6e616d652c6f7065726174696f6e7312fe010a1552756e4f66666c696e6555736572446174614a6f62123e2e676f6f676c652e6164732e676f6f676c656164732e76332e73657276696365732e52756e4f66666c696e6555736572446174614a6f62526571756573741a1d2e676f6f676c652e6c6f6e6772756e6e696e672e4f7065726174696f6e22850182d3e493023e22392f76332f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f6f66666c696e6555736572446174614a6f62732f2a7d3a72756e3a012ada410d7265736f757263655f6e616d65ca412e0a15676f6f676c652e70726f746f6275662e456d7074791215676f6f676c652e70726f746f6275662e456d7074791a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d4285020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7365727669636573421e4f66666c696e6555736572446174614a6f625365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56332e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56335c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

