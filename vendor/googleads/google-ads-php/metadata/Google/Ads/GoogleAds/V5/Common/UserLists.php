<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/common/user_lists.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V5\Common;

class UserLists
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
        $pool->internalAddGeneratedFile(hex2bin(
            "0afa030a42676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f637573746f6d65725f6d617463685f75706c6f61645f6b65795f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732295010a1e437573746f6d65724d6174636855706c6f61644b657954797065456e756d22730a1a437573746f6d65724d6174636855706c6f61644b657954797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112100a0c434f4e544143545f494e464f1002120a0a0643524d5f4944100312190a154d4f42494c455f4144564552544953494e475f4944100442f4010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421f437573746f6d65724d6174636855706c6f61644b65795479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ade030a44676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f636f6d62696e65645f72756c655f6f70657261746f722e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322760a20557365724c697374436f6d62696e656452756c654f70657261746f72456e756d22520a1c557365724c697374436f6d62696e656452756c654f70657261746f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112070a03414e441002120b0a07414e445f4e4f54100342f6010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734221557365724c697374436f6d62696e656452756c654f70657261746f7250726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330a8b040a42676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f63726d5f646174615f736f757263655f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322a7010a1d557365724c69737443726d44617461536f7572636554797065456e756d2285010a19557365724c69737443726d44617461536f7572636554797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120f0a0b46495253545f50415254591002121d0a1954484952445f50415254595f4352454449545f4255524541551003121a0a1654484952445f50415254595f564f5445525f46494c45100442f3010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421e557365724c69737443726d44617461536f757263655479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330afd030a45676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f646174655f72756c655f6974656d5f6f70657261746f722e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732293010a20557365724c6973744461746552756c654974656d4f70657261746f72456e756d226f0a1c557365724c6973744461746552756c654974656d4f70657261746f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120a0a06455155414c531002120e0a0a4e4f545f455155414c531003120a0a064245464f5245100412090a054146544552100542f6010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734221557365724c6973744461746552756c654974656d4f70657261746f7250726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ae0030a43676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f6c6f676963616c5f72756c655f6f70657261746f722e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73227a0a1f557365724c6973744c6f676963616c52756c654f70657261746f72456e756d22570a1b557365724c6973744c6f676963616c52756c654f70657261746f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112070a03414c4c100212070a03414e59100312080a044e4f4e45100442f5010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734220557365724c6973744c6f676963616c52756c654f70657261746f7250726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ac3040a47676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f6e756d6265725f72756c655f6974656d5f6f70657261746f722e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322d5010a22557365724c6973744e756d62657252756c654974656d4f70657261746f72456e756d22ae010a1e557365724c6973744e756d62657252756c654974656d4f70657261746f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112100a0c475245415445525f5448414e100212190a15475245415445525f5448414e5f4f525f455155414c1003120a0a06455155414c531004120e0a0a4e4f545f455155414c531005120d0a094c4553535f5448414e100612160a124c4553535f5448414e5f4f525f455155414c100742f8010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734223557365724c6973744e756d62657252756c654974656d4f70657261746f7250726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330aed030a42676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f707265706f70756c6174696f6e5f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732287010a1f557365724c697374507265706f70756c6174696f6e537461747573456e756d22640a1b557365724c697374507265706f70756c6174696f6e537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120d0a095245515545535445441002120c0a0846494e49534845441003120a0a064641494c4544100442f5010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734220557365724c697374507265706f70756c6174696f6e53746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ab7030a37676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f72756c655f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322680a14557365724c69737452756c6554797065456e756d22500a10557365724c69737452756c6554797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120e0a0a414e445f4f465f4f52531002120e0a0a4f525f4f465f414e4453100342ea010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734215557365724c69737452756c655479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330ad7040a47676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f737472696e675f72756c655f6974656d5f6f70657261746f722e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322e9010a22557365724c697374537472696e6752756c654974656d4f70657261746f72456e756d22c2010a1e557365724c697374537472696e6752756c654974656d4f70657261746f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120c0a08434f4e5441494e531002120a0a06455155414c531003120f0a0b5354415254535f574954481004120d0a09454e44535f574954481005120e0a0a4e4f545f455155414c53100612100a0c4e4f545f434f4e5441494e53100712130a0f4e4f545f5354415254535f57495448100812110a0d4e4f545f454e44535f57495448100942f8010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d734223557365724c697374537472696e6752756c654974656d4f70657261746f7250726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330aab210a2f676f6f676c652f6164732f676f6f676c656164732f76352f636f6d6d6f6e2f757365725f6c697374732e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e1a44676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f636f6d62696e65645f72756c655f6f70657261746f722e70726f746f1a42676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f63726d5f646174615f736f757263655f747970652e70726f746f1a45676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f646174655f72756c655f6974656d5f6f70657261746f722e70726f746f1a43676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f6c6f676963616c5f72756c655f6f70657261746f722e70726f746f1a47676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f6e756d6265725f72756c655f6974656d5f6f70657261746f722e70726f746f1a42676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f707265706f70756c6174696f6e5f7374617475732e70726f746f1a37676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f72756c655f747970652e70726f746f1a47676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f757365725f6c6973745f737472696e675f72756c655f6974656d5f6f70657261746f722e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f224b0a1353696d696c6172557365724c697374496e666f12340a0e736565645f757365725f6c69737418012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756522a9020a1443726d4261736564557365724c697374496e666f122c0a066170705f696418012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512710a0f75706c6f61645f6b65795f7479706518022001280e32582e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e437573746f6d65724d6174636855706c6f61644b657954797065456e756d2e437573746f6d65724d6174636855706c6f61644b65795479706512700a10646174615f736f757263655f7479706518032001280e32562e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e557365724c69737443726d44617461536f7572636554797065456e756d2e557365724c69737443726d44617461536f757263655479706522c0010a10557365724c69737452756c65496e666f12570a0972756c655f7479706518012001280e32442e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e557365724c69737452756c6554797065456e756d2e557365724c69737452756c655479706512530a1072756c655f6974656d5f67726f75707318022003280b32392e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c69737452756c654974656d47726f7570496e666f22650a19557365724c69737452756c654974656d47726f7570496e666f12480a0a72756c655f6974656d7318012003280b32342e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c69737452756c654974656d496e666f22d3020a14557365724c69737452756c654974656d496e666f122a0a046e616d6518012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512560a106e756d6265725f72756c655f6974656d18022001280b323a2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c6973744e756d62657252756c654974656d496e666f480012560a10737472696e675f72756c655f6974656d18032001280b323a2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c697374537472696e6752756c654974656d496e666f480012520a0e646174655f72756c655f6974656d18042001280b32382e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c6973744461746552756c654974656d496e666f4800420b0a0972756c655f6974656d22ec010a18557365724c6973744461746552756c654974656d496e666f126e0a086f70657261746f7218012001280e325c2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e557365724c6973744461746552756c654974656d4f70657261746f72456e756d2e557365724c6973744461746552756c654974656d4f70657261746f72122b0a0576616c756518022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512330a0e6f66667365745f696e5f6461797318032001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756522bd010a1a557365724c6973744e756d62657252756c654974656d496e666f12720a086f70657261746f7218012001280e32602e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e557365724c6973744e756d62657252756c654974656d4f70657261746f72456e756d2e557365724c6973744e756d62657252756c654974656d4f70657261746f72122b0a0576616c756518022001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756522bd010a1a557365724c697374537472696e6752756c654974656d496e666f12720a086f70657261746f7218012001280e32602e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e557365724c697374537472696e6752756c654974656d4f70657261746f72456e756d2e557365724c697374537472696e6752756c654974656d4f70657261746f72122b0a0576616c756518022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756522a0020a18436f6d62696e656452756c65557365724c697374496e666f12460a0c6c6566745f6f706572616e6418012001280b32302e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c69737452756c65496e666f12470a0d72696768745f6f706572616e6418022001280b32302e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c69737452756c65496e666f12730a0d72756c655f6f70657261746f7218032001280e325c2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e557365724c697374436f6d62696e656452756c654f70657261746f72456e756d2e557365724c697374436f6d62696e656452756c654f70657261746f7222c0010a1c44617465537065636966696352756c65557365724c697374496e666f123e0a0472756c6518012001280b32302e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c69737452756c65496e666f12300a0a73746172745f6461746518022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565122e0a08656e645f6461746518032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565225c0a1a45787072657373696f6e52756c65557365724c697374496e666f123e0a0472756c6518012001280b32302e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c69737452756c65496e666f22cd030a1552756c654261736564557365724c697374496e666f12780a14707265706f70756c6174696f6e5f73746174757318012001280e325a2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e557365724c697374507265706f70756c6174696f6e537461747573456e756d2e557365724c697374507265706f70756c6174696f6e537461747573125b0a17636f6d62696e65645f72756c655f757365725f6c69737418022001280b32382e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e436f6d62696e656452756c65557365724c697374496e666f480012640a1c646174655f73706563696669635f72756c655f757365725f6c69737418032001280b323c2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e44617465537065636966696352756c65557365724c697374496e666f4800125f0a1965787072657373696f6e5f72756c655f757365725f6c69737418042001280b323a2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e45787072657373696f6e52756c65557365724c697374496e666f480042160a1472756c655f62617365645f757365725f6c697374225d0a134c6f676963616c557365724c697374496e666f12460a0572756c657318012003280b32372e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c6973744c6f676963616c52756c65496e666f22da010a17557365724c6973744c6f676963616c52756c65496e666f126c0a086f70657261746f7218012001280e325a2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e557365724c6973744c6f676963616c52756c654f70657261746f72456e756d2e557365724c6973744c6f676963616c52756c654f70657261746f7212510a0d72756c655f6f706572616e647318022003280b323a2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e4c6f676963616c557365724c6973744f706572616e64496e666f224d0a1a4c6f676963616c557365724c6973744f706572616e64496e666f122f0a09757365725f6c69737418012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756522580a114261736963557365724c697374496e666f12430a07616374696f6e7318012003280b32322e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e2e557365724c697374416374696f6e496e666f229f010a12557365724c697374416374696f6e496e666f12390a11636f6e76657273696f6e5f616374696f6e18012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654800123a0a1272656d61726b6574696e675f616374696f6e18022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565480042120a10757365725f6c6973745f616374696f6e42e9010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e636f6d6d6f6e420e557365724c6973747350726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56352e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56355c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a436f6d6d6f6e620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

