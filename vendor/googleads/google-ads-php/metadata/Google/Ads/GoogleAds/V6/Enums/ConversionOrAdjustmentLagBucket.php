<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/enums/conversion_or_adjustment_lag_bucket.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V6\Enums;

class ConversionOrAdjustmentLagBucket
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
            "0aba0f0a47676f6f676c652f6164732f676f6f676c656164732f76362f656e756d732f636f6e76657273696f6e5f6f725f61646a7573746d656e745f6c61675f6275636b65742e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76362e656e756d7322cb0c0a23436f6e76657273696f6e4f7241646a7573746d656e744c61674275636b6574456e756d22a30c0a1f436f6e76657273696f6e4f7241646a7573746d656e744c61674275636b6574120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112200a1c434f4e56455253494f4e5f4c4553535f5448414e5f4f4e455f4441591002121e0a1a434f4e56455253494f4e5f4f4e455f544f5f54574f5f44415953100312200a1c434f4e56455253494f4e5f54574f5f544f5f54485245455f44415953100412210a1d434f4e56455253494f4e5f54485245455f544f5f464f55525f44415953100512200a1c434f4e56455253494f4e5f464f55525f544f5f464956455f444159531006121f0a1b434f4e56455253494f4e5f464956455f544f5f5349585f44415953100712200a1c434f4e56455253494f4e5f5349585f544f5f534556454e5f44415953100812220a1e434f4e56455253494f4e5f534556454e5f544f5f45494748545f44415953100912210a1d434f4e56455253494f4e5f45494748545f544f5f4e494e455f44415953100a121f0a1b434f4e56455253494f4e5f4e494e455f544f5f54454e5f44415953100b12210a1d434f4e56455253494f4e5f54454e5f544f5f454c4556454e5f44415953100c12240a20434f4e56455253494f4e5f454c4556454e5f544f5f5457454c56455f44415953100d12260a22434f4e56455253494f4e5f5457454c56455f544f5f544849525445454e5f44415953100e12280a24434f4e56455253494f4e5f544849525445454e5f544f5f464f55525445454e5f44415953100f122a0a26434f4e56455253494f4e5f464f55525445454e5f544f5f5457454e54595f4f4e455f44415953101012280a24434f4e56455253494f4e5f5457454e54595f4f4e455f544f5f5448495254595f44415953101112280a24434f4e56455253494f4e5f5448495254595f544f5f464f5254595f464956455f44415953101212270a23434f4e56455253494f4e5f464f5254595f464956455f544f5f53495854595f44415953101312230a1f434f4e56455253494f4e5f53495854595f544f5f4e494e4554595f44415953101412200a1c41444a5553544d454e545f4c4553535f5448414e5f4f4e455f4441591015121e0a1a41444a5553544d454e545f4f4e455f544f5f54574f5f44415953101612200a1c41444a5553544d454e545f54574f5f544f5f54485245455f44415953101712210a1d41444a5553544d454e545f54485245455f544f5f464f55525f44415953101812200a1c41444a5553544d454e545f464f55525f544f5f464956455f444159531019121f0a1b41444a5553544d454e545f464956455f544f5f5349585f44415953101a12200a1c41444a5553544d454e545f5349585f544f5f534556454e5f44415953101b12220a1e41444a5553544d454e545f534556454e5f544f5f45494748545f44415953101c12210a1d41444a5553544d454e545f45494748545f544f5f4e494e455f44415953101d121f0a1b41444a5553544d454e545f4e494e455f544f5f54454e5f44415953101e12210a1d41444a5553544d454e545f54454e5f544f5f454c4556454e5f44415953101f12240a2041444a5553544d454e545f454c4556454e5f544f5f5457454c56455f44415953102012260a2241444a5553544d454e545f5457454c56455f544f5f544849525445454e5f44415953102112280a2441444a5553544d454e545f544849525445454e5f544f5f464f55525445454e5f444159531022122a0a2641444a5553544d454e545f464f55525445454e5f544f5f5457454e54595f4f4e455f44415953102312280a2441444a5553544d454e545f5457454e54595f4f4e455f544f5f5448495254595f44415953102412280a2441444a5553544d454e545f5448495254595f544f5f464f5254595f464956455f44415953102512270a2341444a5553544d454e545f464f5254595f464956455f544f5f53495854595f44415953102612230a1f41444a5553544d454e545f53495854595f544f5f4e494e4554595f44415953102712380a3441444a5553544d454e545f4e494e4554595f544f5f4f4e455f48554e445245445f414e445f464f5254595f464956455f44415953102812160a12434f4e56455253494f4e5f554e4b4e4f574e102912160a1241444a5553544d454e545f554e4b4e4f574e102a42f9010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e656e756d734224436f6e76657273696f6e4f7241646a7573746d656e744c61674275636b657450726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56362e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56365c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a456e756d73620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

