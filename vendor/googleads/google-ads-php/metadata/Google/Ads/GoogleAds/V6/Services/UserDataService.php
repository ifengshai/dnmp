<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/services/user_data_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V6\Services;

class UserDataService
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
        \GPBMetadata\Google\Api\Client::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ac8030a3a676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f757365725f6964656e7469666965725f736f757263652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d7322720a18557365724964656e746966696572536f75726365456e756d22560a14557365724964656e746966696572536f75726365120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120f0a0b46495253545f50415254591002120f0a0b54484952445f5041525459100342ee010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d734219557365724964656e746966696572536f7572636550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f330a9f140a36676f6f676c652f6164732f676f6f676c656164732f76362f636f6d6d6f6e2f6f66666c696e655f757365725f646174612e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f2292020a164f66666c696e655573657241646472657373496e666f121e0a116861736865645f66697273745f6e616d651807200128094800880101121d0a106861736865645f6c6173745f6e616d65180820012809480188010112110a0463697479180920012809480288010112120a057374617465180a20012809480388010112190a0c636f756e7472795f636f6465180b20012809480488010112180a0b706f7374616c5f636f6465180c20012809480588010142140a125f6861736865645f66697273745f6e616d6542130a115f6861736865645f6c6173745f6e616d6542070a055f6369747942080a065f7374617465420f0a0d5f636f756e7472795f636f6465420e0a0c5f706f7374616c5f636f646522c7020a0e557365724964656e746966696572126c0a16757365725f6964656e7469666965725f736f7572636518062001280e324c2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732e557365724964656e746966696572536f75726365456e756d2e557365724964656e746966696572536f7572636512160a0c6861736865645f656d61696c1807200128094800121d0a136861736865645f70686f6e655f6e756d626572180820012809480012130a096d6f62696c655f69641809200128094800121d0a1374686972645f70617274795f757365725f6964180a200128094800124e0a0c616464726573735f696e666f18052001280b32362e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4f66666c696e655573657241646472657373496e666f4800420c0a0a6964656e7469666965722297030a145472616e73616374696f6e41747472696275746512220a157472616e73616374696f6e5f646174655f74696d65180820012809480088010112260a197472616e73616374696f6e5f616d6f756e745f6d6963726f731809200128014801880101121a0a0d63757272656e63795f636f6465180a200128094802880101121e0a11636f6e76657273696f6e5f616374696f6e180b20012809480388010112150a086f726465725f6964180c20012809480488010112470a0f73746f72655f61747472696275746518062001280b322e2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e53746f726541747472696275746512190a0c637573746f6d5f76616c7565180d20012809480588010142180a165f7472616e73616374696f6e5f646174655f74696d65421c0a1a5f7472616e73616374696f6e5f616d6f756e745f6d6963726f7342100a0e5f63757272656e63795f636f646542140a125f636f6e76657273696f6e5f616374696f6e420b0a095f6f726465725f6964420f0a0d5f637573746f6d5f76616c756522380a0e53746f726541747472696275746512170a0a73746f72655f636f64651802200128094800880101420d0a0b5f73746f72655f636f646522f0010a08557365724461746112480a10757365725f6964656e7469666965727318012003280b322e2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e557365724964656e74696669657212530a157472616e73616374696f6e5f61747472696275746518022001280b32342e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e5472616e73616374696f6e41747472696275746512450a0e757365725f61747472696275746518032001280b322d2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e55736572417474726962757465228b010a0d5573657241747472696275746512220a156c69666574696d655f76616c75655f6d6963726f73180120012803480088010112220a156c69666574696d655f76616c75655f6275636b6574180220012805480188010142180a165f6c69666574696d655f76616c75655f6d6963726f7342180a165f6c69666574696d655f76616c75655f6275636b657422450a1d437573746f6d65724d61746368557365724c6973744d6574616461746112160a09757365725f6c6973741802200128094800880101420c0a0a5f757365725f6c6973742296020a1253746f726553616c65734d65746164617461121d0a106c6f79616c74795f6672616374696f6e180520012801480088010112280a1b7472616e73616374696f6e5f75706c6f61645f6672616374696f6e180620012801480188010112170a0a637573746f6d5f6b65791807200128094802880101125a0a1474686972645f70617274795f6d6574616461746118032001280b323c2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e53746f726553616c6573546869726450617274794d6574616461746142130a115f6c6f79616c74795f6672616374696f6e421e0a1c5f7472616e73616374696f6e5f75706c6f61645f6672616374696f6e420d0a0b5f637573746f6d5f6b65792298030a1c53746f726553616c6573546869726450617274794d6574616461746112280a1b616476657274697365725f75706c6f61645f646174655f74696d65180720012809480088010112270a1a76616c69645f7472616e73616374696f6e5f6672616374696f6e180820012801480188010112230a16706172746e65725f6d617463685f6672616374696f6e180920012801480288010112240a17706172746e65725f75706c6f61645f6672616374696f6e180a20012801480388010112220a156272696467655f6d61705f76657273696f6e5f6964180b20012809480488010112170a0a706172746e65725f6964180c200128034805880101421e0a1c5f616476657274697365725f75706c6f61645f646174655f74696d65421d0a1b5f76616c69645f7472616e73616374696f6e5f6672616374696f6e42190a175f706172746e65725f6d617463685f6672616374696f6e421a0a185f706172746e65725f75706c6f61645f6672616374696f6e42180a165f6272696467655f6d61705f76657273696f6e5f6964420d0a0b5f706172746e65725f696442ef010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e42144f66666c696e65557365724461746150726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56362e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56365c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a436f6d6d6f6e620670726f746f330ad4090a38676f6f676c652f6164732f676f6f676c656164732f76362f73657276696365732f757365725f646174615f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f22f7010a1555706c6f616455736572446174615265717565737412180a0b637573746f6d65725f69641801200128094203e04102124c0a0a6f7065726174696f6e7318032003280b32332e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e55736572446174614f7065726174696f6e4203e04102126a0a21637573746f6d65725f6d617463685f757365725f6c6973745f6d6574616461746118022001280b323d2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e437573746f6d65724d61746368557365724c6973744d657461646174614800420a0a086d657461646174612298010a1155736572446174614f7065726174696f6e123a0a0663726561746518012001280b32282e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e55736572446174614800123a0a0672656d6f766518022001280b32282e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e55736572446174614800420b0a096f7065726174696f6e2292010a1655706c6f61645573657244617461526573706f6e7365121d0a1075706c6f61645f646174655f74696d65180320012809480088010112260a1972656365697665645f6f7065726174696f6e735f636f756e74180420012805480188010142130a115f75706c6f61645f646174655f74696d65421c0a1a5f72656365697665645f6f7065726174696f6e735f636f756e7432ed010a0f55736572446174615365727669636512bc010a0e55706c6f6164557365724461746112372e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e55706c6f61645573657244617461526571756573741a382e676f6f676c652e6164732e676f6f676c656164732e76362e73657276696365732e55706c6f61645573657244617461526573706f6e7365223782d3e4930231222c2f76362f637573746f6d6572732f7b637573746f6d65725f69643d2a7d3a75706c6f616455736572446174613a012a1a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d42fb010a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e7365727669636573421455736572446174615365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56362e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56365c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

