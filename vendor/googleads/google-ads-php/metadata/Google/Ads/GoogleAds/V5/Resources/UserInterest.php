<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/resources/user_interest.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V5\Resources;

class UserInterest
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
        $pool->internalAddGeneratedFile(hex2bin(
            "0ad5040a50676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f637269746572696f6e5f63617465676f72795f6368616e6e656c5f617661696c6162696c6974795f6d6f64652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322d4010a2c437269746572696f6e43617465676f72794368616e6e656c417661696c6162696c6974794d6f6465456e756d22a3010a28437269746572696f6e43617465676f72794368616e6e656c417661696c6162696c6974794d6f6465120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112100a0c414c4c5f4348414e4e454c53100212210a1d4348414e4e454c5f545950455f414e445f414c4c5f5355425459504553100312240a204348414e4e454c5f545950455f414e445f5355425345545f535542545950455310044282020a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73422d437269746572696f6e43617465676f72794368616e6e656c417661696c6162696c6974794d6f646550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330a96040a3c676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6164766572746973696e675f6368616e6e656c5f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322bb010a1a4164766572746973696e674368616e6e656c54797065456e756d229c010a164164766572746973696e674368616e6e656c54797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120a0a065345415243481002120b0a07444953504c41591003120c0a0853484f5050494e47100412090a05484f54454c100512090a05564944454f100612110a0d4d554c54495f4348414e4e454c100712090a054c4f43414c100812090a05534d415254100942f0010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421b4164766572746973696e674368616e6e656c5479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ae0040a4f676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f637269746572696f6e5f63617465676f72795f6c6f63616c655f617661696c6162696c6974795f6d6f64652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322e1010a2b437269746572696f6e43617465676f72794c6f63616c65417661696c6162696c6974794d6f6465456e756d22b1010a27437269746572696f6e43617465676f72794c6f63616c65417661696c6162696c6974794d6f6465120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120f0a0b414c4c5f4c4f43414c45531002121d0a19434f554e5452595f414e445f414c4c5f4c414e4755414745531003121e0a1a4c414e47554147455f414e445f414c4c5f434f554e5452494553100412180a14434f554e5452595f414e445f4c414e475541474510054281020a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73422c437269746572696f6e43617465676f72794c6f63616c65417661696c6162696c6974794d6f646550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ab3060a40676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6164766572746973696e675f6368616e6e656c5f7375625f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322d1030a1d4164766572746973696e674368616e6e656c53756254797065456e756d22af030a194164766572746973696e674368616e6e656c53756254797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112150a115345415243485f4d4f42494c455f415050100212160a12444953504c41595f4d4f42494c455f415050100312120a0e5345415243485f45585052455353100412130a0f444953504c41595f45585052455353100512160a1253484f5050494e475f534d4152545f414453100612140a10444953504c41595f474d41494c5f41441007121a0a16444953504c41595f534d4152545f43414d504149474e100812130a0f564944454f5f4f555453545245414d100912100a0c564944454f5f414354494f4e100a12170a13564944454f5f4e4f4e5f534b49505041424c45100b12100a0c4150505f43414d504149474e100c121f0a1b4150505f43414d504149474e5f464f525f454e474147454d454e54100d12120a0e4c4f43414c5f43414d504149474e100e12230a1f53484f5050494e475f434f4d50415249534f4e5f4c495354494e475f414453100f12120a0e534d4152545f43414d504149474e101012120a0e564944454f5f53455155454e4345101142f3010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421e4164766572746973696e674368616e6e656c5375625479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330aef0c0a44676f6f676c652f6164732f676f6f676c656164732f76352f636f6d6d6f6e2f637269746572696f6e5f63617465676f72795f617661696c6162696c6974792e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e1a3c676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6164766572746973696e675f6368616e6e656c5f747970652e70726f746f1a50676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f637269746572696f6e5f63617465676f72795f6368616e6e656c5f617661696c6162696c6974795f6d6f64652e70726f746f1a4f676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f637269746572696f6e5f63617465676f72795f6c6f63616c655f617661696c6162696c6974795f6d6f64652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22cb010a1d437269746572696f6e43617465676f7279417661696c6162696c69747912550a076368616e6e656c18012001280b32442e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e437269746572696f6e43617465676f72794368616e6e656c417661696c6162696c69747912530a066c6f63616c6518022003280b32432e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e437269746572696f6e43617465676f72794c6f63616c65417661696c6162696c69747922f0030a24437269746572696f6e43617465676f72794368616e6e656c417661696c6162696c697479128f010a11617661696c6162696c6974795f6d6f646518012001280e32742e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e437269746572696f6e43617465676f72794368616e6e656c417661696c6162696c6974794d6f6465456e756d2e437269746572696f6e43617465676f72794368616e6e656c417661696c6162696c6974794d6f646512720a186164766572746973696e675f6368616e6e656c5f7479706518022001280e32502e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e4164766572746973696e674368616e6e656c54797065456e756d2e4164766572746973696e674368616e6e656c54797065127c0a1c6164766572746973696e675f6368616e6e656c5f7375625f7479706518032003280e32562e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e4164766572746973696e674368616e6e656c53756254797065456e756d2e4164766572746973696e674368616e6e656c5375625479706512440a20696e636c7564655f64656661756c745f6368616e6e656c5f7375625f7479706518042001280b321a2e676f6f676c652e70726f746f6275662e426f6f6c56616c7565229e020a23437269746572696f6e43617465676f72794c6f63616c65417661696c6162696c697479128d010a11617661696c6162696c6974795f6d6f646518012001280e32722e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e437269746572696f6e43617465676f72794c6f63616c65417661696c6162696c6974794d6f6465456e756d2e437269746572696f6e43617465676f72794c6f63616c65417661696c6162696c6974794d6f646512320a0c636f756e7472795f636f646518022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512330a0d6c616e67756167655f636f646518032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756542fd010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e4222437269746572696f6e43617465676f7279417661696c6162696c69747950726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56352e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56355c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a436f6d6d6f6e620670726f746f330a9f040a3f676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f696e7465726573745f7461786f6e6f6d795f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322bf010a1c55736572496e7465726573745461786f6e6f6d7954797065456e756d229e010a1855736572496e7465726573745461786f6e6f6d7954797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120c0a08414646494e4954591002120d0a09494e5f4d41524b45541003121b0a174d4f42494c455f4150505f494e5354414c4c5f55534552100412100a0c564552544943414c5f47454f100512180a144e45575f534d4152545f50484f4e455f55534552100642f2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421d55736572496e7465726573745461786f6e6f6d795479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ab5090a35676f6f676c652f6164732f676f6f676c656164732f76352f7265736f75726365732f757365725f696e7465726573742e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365731a3f676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f696e7465726573745f7461786f6e6f6d795f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f2294050a0c55736572496e74657265737412440a0d7265736f757263655f6e616d65180120012809422de04103fa41270a25676f6f676c656164732e676f6f676c65617069732e636f6d2f55736572496e74657265737412700a0d7461786f6e6f6d795f7479706518022001280e32542e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e55736572496e7465726573745461786f6e6f6d7954797065456e756d2e55736572496e7465726573745461786f6e6f6d79547970654203e04103123a0a10757365725f696e7465726573745f696418032001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e04103122f0a046e616d6518042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312690a14757365725f696e7465726573745f706172656e7418052001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565422de04103fa41270a25676f6f676c656164732e676f6f676c65617069732e636f6d2f55736572496e74657265737412380a0f6c61756e636865645f746f5f616c6c18062001280b321a2e676f6f676c652e70726f746f6275662e426f6f6c56616c75654203e04103125a0a0e617661696c6162696c697469657318072003280b323d2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e437269746572696f6e43617465676f7279417661696c6162696c6974794203e041033a5eea415b0a25676f6f676c656164732e676f6f676c65617069732e636f6d2f55736572496e7465726573741232637573746f6d6572732f7b637573746f6d65727d2f75736572496e746572657374732f7b757365725f696e7465726573747d42fe010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f7572636573421155736572496e74657265737450726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56352e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56355c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a5265736f7572636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

