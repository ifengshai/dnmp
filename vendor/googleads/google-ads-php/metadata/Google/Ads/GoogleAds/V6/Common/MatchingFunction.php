<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/common/matching_function.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V6\Common;

class MatchingFunction
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Http::initOnce();
        \GPBMetadata\Google\Api\Annotations::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0ae7030a42676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f6d61746368696e675f66756e6374696f6e5f636f6e746578745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732281010a1f4d61746368696e6746756e6374696f6e436f6e7465787454797065456e756d225e0a1b4d61746368696e6746756e6374696f6e436f6e7465787454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112100a0c464545445f4954454d5f49441002120f0a0b4445564943455f4e414d45100342f5010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d7342204d61746368696e6746756e6374696f6e436f6e746578745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f330af4030a3e676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f6d61746368696e675f66756e6374696f6e5f6f70657261746f722e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732295010a1c4d61746368696e6746756e6374696f6e4f70657261746f72456e756d22750a184d61746368696e6746756e6374696f6e4f70657261746f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112060a02494e1002120c0a084944454e544954591003120a0a06455155414c53100412070a03414e44100512100a0c434f4e5441494e535f414e59100642f2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d73421d4d61746368696e6746756e6374696f6e4f70657261746f7250726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f330adf0c0a36676f6f676c652f6164732f676f6f676c656164732f76362f636f6d6d6f6e2f6d61746368696e675f66756e6374696f6e2e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e1a3e676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f6d61746368696e675f66756e6374696f6e5f6f70657261746f722e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22ad020a104d61746368696e6746756e6374696f6e121c0a0f66756e6374696f6e5f737472696e67180520012809480088010112660a086f70657261746f7218042001280e32542e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732e4d61746368696e6746756e6374696f6e4f70657261746f72456e756d2e4d61746368696e6746756e6374696f6e4f70657261746f72123e0a0d6c6566745f6f706572616e647318022003280b32272e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4f706572616e64123f0a0e72696768745f6f706572616e647318032003280b32272e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4f706572616e6442120a105f66756e6374696f6e5f737472696e6722fb060a074f706572616e6412530a10636f6e7374616e745f6f706572616e6418012001280b32372e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4f706572616e642e436f6e7374616e744f706572616e644800125e0a16666565645f6174747269627574655f6f706572616e6418022001280b323c2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4f706572616e642e466565644174747269627574654f706572616e64480012530a1066756e6374696f6e5f6f706572616e6418032001280b32372e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4f706572616e642e46756e6374696f6e4f706572616e64480012600a17726571756573745f636f6e746578745f6f706572616e6418042001280b323d2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4f706572616e642e52657175657374436f6e746578744f706572616e6448001a8a010a0f436f6e7374616e744f706572616e6412160a0c737472696e675f76616c7565180520012809480012140a0a6c6f6e675f76616c7565180620012803480012170a0d626f6f6c65616e5f76616c7565180720012808480012160a0c646f75626c655f76616c7565180820012801480042180a16636f6e7374616e745f6f706572616e645f76616c75651a6e0a14466565644174747269627574654f706572616e6412140a07666565645f69641803200128034800880101121e0a11666565645f6174747269627574655f69641804200128034801880101420a0a085f666565645f696442140a125f666565645f6174747269627574655f69641a5e0a0f46756e6374696f6e4f706572616e64124b0a116d61746368696e675f66756e6374696f6e18012001280b32302e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4d61746368696e6746756e6374696f6e1a89010a1552657175657374436f6e746578744f706572616e6412700a0c636f6e746578745f7479706518012001280e325a2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d732e4d61746368696e6746756e6374696f6e436f6e7465787454797065456e756d2e4d61746368696e6746756e6374696f6e436f6e7465787454797065421b0a1966756e6374696f6e5f617267756d656e745f6f706572616e6442f0010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e42154d61746368696e6746756e6374696f6e50726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56362e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56365c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a436f6d6d6f6e620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

