<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/change_status_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V5\Services;

class ChangeStatusService
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
        $pool->internalAddGeneratedFile(hex2bin(
            "0a96040a3c676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6164766572746973696e675f6368616e6e656c5f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322bb010a1a4164766572746973696e674368616e6e656c54797065456e756d229c010a164164766572746973696e674368616e6e656c54797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120a0a065345415243481002120b0a07444953504c41591003120c0a0853484f5050494e47100412090a05484f54454c100512090a05564944454f100612110a0d4d554c54495f4348414e4e454c100712090a054c4f43414c100812090a05534d415254100942f0010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421b4164766572746973696e674368616e6e656c5479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ab3060a40676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6164766572746973696e675f6368616e6e656c5f7375625f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322d1030a1d4164766572746973696e674368616e6e656c53756254797065456e756d22af030a194164766572746973696e674368616e6e656c53756254797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112150a115345415243485f4d4f42494c455f415050100212160a12444953504c41595f4d4f42494c455f415050100312120a0e5345415243485f45585052455353100412130a0f444953504c41595f45585052455353100512160a1253484f5050494e475f534d4152545f414453100612140a10444953504c41595f474d41494c5f41441007121a0a16444953504c41595f534d4152545f43414d504149474e100812130a0f564944454f5f4f555453545245414d100912100a0c564944454f5f414354494f4e100a12170a13564944454f5f4e4f4e5f534b49505041424c45100b12100a0c4150505f43414d504149474e100c121f0a1b4150505f43414d504149474e5f464f525f454e474147454d454e54100d12120a0e4c4f43414c5f43414d504149474e100e12230a1f53484f5050494e475f434f4d50415249534f4e5f4c495354494e475f414453100f12120a0e534d4152545f43414d504149474e101012120a0e564944454f5f53455155454e4345101142f3010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421e4164766572746973696e674368616e6e656c5375625479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330acc070a2b676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f61645f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732292050a0a416454797065456e756d2283050a06416454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a07544558545f4144100212140a10455850414e4445445f544558545f4144100312100a0c43414c4c5f4f4e4c595f41441006121e0a1a455850414e4445445f44594e414d49435f5345415243485f41441007120c0a08484f54454c5f4144100812150a1153484f5050494e475f534d4152545f4144100912170a1353484f5050494e475f50524f445543545f4144100a120c0a08564944454f5f4144100c120c0a08474d41494c5f4144100d120c0a08494d4147455f4144100e12180a14524553504f4e534956455f5345415243485f4144100f12200a1c4c45474143595f524553504f4e534956455f444953504c41595f41441010120a0a064150505f4144101112190a154c45474143595f4150505f494e5354414c4c5f4144101212190a15524553504f4e534956455f444953504c41595f41441013120c0a084c4f43414c5f4144101412130a0f48544d4c355f55504c4f41445f4144101512140a1044594e414d49435f48544d4c355f4144101612150a114150505f454e474147454d454e545f4144101712220a1e53484f5050494e475f434f4d50415249534f4e5f4c495354494e475f4144101812130a0f564944454f5f42554d5045525f4144101912240a20564944454f5f4e4f4e5f534b49505041424c455f494e5f53545245414d5f4144101a12160a12564944454f5f4f555453545245414d5f4144101b121f0a1b564944454f5f54525545564945575f444953434f564552595f4144101c121f0a1b564944454f5f54525545564945575f494e5f53545245414d5f4144101d12170a13564944454f5f524553504f4e534956455f4144101e42e0010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73420b41645479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330acf030a3b676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6368616e67655f7374617475735f6f7065726174696f6e2e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322770a194368616e67655374617475734f7065726174696f6e456e756d225a0a154368616e67655374617475734f7065726174696f6e120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112090a0541444445441002120b0a074348414e4745441003120b0a0752454d4f564544100442ef010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421a4368616e67655374617475734f7065726174696f6e50726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330af0040a3f676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6368616e67655f7374617475735f7265736f757263655f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732290020a1c4368616e67655374617475735265736f7572636554797065456e756d22ef010a184368616e67655374617475735265736f7572636554797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120c0a0841445f47524f55501003120f0a0b41445f47524f55505f4144100412160a1241445f47524f55505f435249544552494f4e1005120c0a0843414d504149474e100612160a1243414d504149474e5f435249544552494f4e100712080a04464545441009120d0a09464545445f4954454d100a12110a0d41445f47524f55505f46454544100b12110a0d43414d504149474e5f46454544100c12190a1541445f47524f55505f4249445f4d4f444946494552100d42f2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421d4368616e67655374617475735265736f757263655479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330a9c070a32676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f637269746572696f6e5f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322d4040a11437269746572696f6e54797065456e756d22be040a0d437269746572696f6e54797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a074b4559574f52441002120d0a09504c4143454d454e54100312170a134d4f42494c455f4150505f43415445474f5259100412160a124d4f42494c455f4150504c49434154494f4e1005120a0a064445564943451006120c0a084c4f434154494f4e100712110a0d4c495354494e475f47524f55501008120f0a0b41445f5343484544554c451009120d0a094147455f52414e4745100a120a0a0647454e444552100b12100a0c494e434f4d455f52414e4745100c12130a0f504152454e54414c5f535441545553100d12110a0d594f55545542455f564944454f100e12130a0f594f55545542455f4348414e4e454c100f120d0a09555345525f4c4953541010120d0a0950524f58494d495459101112090a05544f504943101212110a0d4c495354494e475f53434f50451013120c0a084c414e47554147451014120c0a0849505f424c4f434b101512110a0d434f4e54454e545f4c4142454c1016120b0a0743415252494552101712110a0d555345525f494e5445524553541018120b0a07574542504147451019121c0a184f5045524154494e475f53595354454d5f56455253494f4e101a12150a114150505f5041594d454e545f4d4f44454c101b12110a0d4d4f42494c455f444556494345101c12130a0f435553544f4d5f414646494e495459101d12110a0d435553544f4d5f494e54454e54101e12120a0e4c4f434154494f4e5f47524f5550101f42e7010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734212437269746572696f6e5479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330a93030a2f676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f666565645f6f726967696e2e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322520a0e466565644f726967696e456e756d22400a0a466565644f726967696e120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112080a04555345521002120a0a06474f4f474c45100342e4010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73420f466565644f726967696e50726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ad7110a35676f6f676c652f6164732f676f6f676c656164732f76352f7265736f75726365732f6368616e67655f7374617475732e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365731a40676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6164766572746973696e675f6368616e6e656c5f7375625f747970652e70726f746f1a3c676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6164766572746973696e675f6368616e6e656c5f747970652e70726f746f1a3b676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6368616e67655f7374617475735f6f7065726174696f6e2e70726f746f1a3f676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f6368616e67655f7374617475735f7265736f757263655f747970652e70726f746f1a32676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f637269746572696f6e5f747970652e70726f746f1a2f676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f666565645f6f726967696e2e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22940b0a0c4368616e676553746174757312440a0d7265736f757263655f6e616d65180120012809422de04103fa41270a25676f6f676c656164732e676f6f676c65617069732e636f6d2f4368616e676553746174757312400a156c6173745f6368616e67655f646174655f74696d6518032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312700a0d7265736f757263655f7479706518042001280e32542e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e4368616e67655374617475735265736f7572636554797065456e756d2e4368616e67655374617475735265736f75726365547970654203e0410312400a0863616d706169676e1811200128094229e04103fa41230a21676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4800880101123f0a0861645f67726f75701812200128094228e04103fa41220a20676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f75704801880101126c0a0f7265736f757263655f73746174757318082001280e324e2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e4368616e67655374617475734f7065726174696f6e456e756d2e4368616e67655374617475734f7065726174696f6e4203e04103125d0a0b61645f67726f75705f616418092001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565422ae04103fa41240a22676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f75704164126b0a1261645f67726f75705f637269746572696f6e180a2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654231e04103fa412b0a29676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f7570437269746572696f6e126c0a1263616d706169676e5f637269746572696f6e180b2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654232e04103fa412c0a2a676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e437269746572696f6e12510a0466656564180c2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654225e04103fa411f0a1d676f6f676c656164732e676f6f676c65617069732e636f6d2f46656564125a0a09666565645f6974656d180d2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654229e04103fa41230a21676f6f676c656164732e676f6f676c65617069732e636f6d2f466565644974656d12610a0d61645f67726f75705f66656564180e2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565422ce04103fa41260a24676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f75704665656412620a0d63616d706169676e5f66656564180f2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565422de04103fa41270a25676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4665656412700a1561645f67726f75705f6269645f6d6f64696669657218102001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654233e04103fa412d0a2b676f6f676c656164732e676f6f676c65617069732e636f6d2f416447726f75704269644d6f6469666965723a5dea415a0a25676f6f676c656164732e676f6f676c65617069732e636f6d2f4368616e67655374617475731231637573746f6d6572732f7b637573746f6d65727d2f6368616e67655374617475732f7b6368616e67655f7374617475737d420b0a095f63616d706169676e420b0a095f61645f67726f757042fe010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f757263657342114368616e676553746174757350726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56352e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56355c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a5265736f7572636573620670726f746f330ab9060a3c676f6f676c652f6164732f676f6f676c656164732f76352f73657276696365732f6368616e67655f7374617475735f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f225e0a164765744368616e67655374617475735265717565737412440a0d7265736f757263655f6e616d65180120012809422de04102fa41270a25676f6f676c656164732e676f6f676c65617069732e636f6d2f4368616e676553746174757332f9010a134368616e67655374617475735365727669636512c4010a0f4765744368616e676553746174757312382e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e4765744368616e6765537461747573526571756573741a2f2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e4368616e6765537461747573224682d3e4930230122e2f76352f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f6368616e67655374617475732f2a7dda410d7265736f757263655f6e616d651a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d42ff010a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e736572766963657342184368616e67655374617475735365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56352e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56355c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

