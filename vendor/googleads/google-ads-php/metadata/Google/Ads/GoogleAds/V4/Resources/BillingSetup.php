<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/resources/billing_setup.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V4\Resources;

class BillingSetup
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
        $pool->internalAddGeneratedFile(hex2bin(
            "0adc030a38676f6f676c652f6164732f676f6f676c656164732f76342f656e756d732f62696c6c696e675f73657475705f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76342e656e756d732289010a1642696c6c696e675365747570537461747573456e756d226f0a1242696c6c696e675365747570537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a0750454e44494e47100212110a0d415050524f5645445f48454c441003120c0a08415050524f5645441004120d0a0943414e43454c4c4544100542ec010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d73421742696c6c696e67536574757053746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56342e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56345c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a456e756d73620670726f746f330a8b030a2d676f6f676c652f6164732f676f6f676c656164732f76342f656e756d732f74696d655f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76342e656e756d73224e0a0c54696d6554797065456e756d223e0a0854696d6554797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112070a034e4f571002120b0a07464f5245564552100342e2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d73420d54696d655479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56342e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56345c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a456e756d73620670726f746f330ac70d0a35676f6f676c652f6164732f676f6f676c656164732f76342f7265736f75726365732f62696c6c696e675f73657475702e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365731a2d676f6f676c652f6164732f676f6f676c656164732f76342f656e756d732f74696d655f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22b8090a0c42696c6c696e67536574757012440a0d7265736f757263655f6e616d65180120012809422de04105fa41270a25676f6f676c656164732e676f6f676c65617069732e636f6d2f42696c6c696e675365747570122c0a02696418022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e04103125d0a0673746174757318032001280e32482e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d732e42696c6c696e675365747570537461747573456e756d2e42696c6c696e6753657475705374617475734203e0410312680a107061796d656e74735f6163636f756e74180b2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654230e04105fa412a0a28676f6f676c656164732e676f6f676c65617069732e636f6d2f5061796d656e74734163636f756e7412670a157061796d656e74735f6163636f756e745f696e666f180c2001280b32432e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f75726365732e42696c6c696e6753657475702e5061796d656e74734163636f756e74496e666f4203e04105123c0a0f73746172745f646174655f74696d6518092001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e04105480012540a0f73746172745f74696d655f74797065180a2001280e32342e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d732e54696d6554797065456e756d2e54696d65547970654203e041054800123a0a0d656e645f646174655f74696d65180d2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e04103480112520a0d656e645f74696d655f74797065180e2001280e32342e676f6f676c652e6164732e676f6f676c656164732e76342e656e756d732e54696d6554797065456e756d2e54696d65547970654203e0410348011ae3020a135061796d656e74734163636f756e74496e666f123e0a137061796d656e74735f6163636f756e745f696418012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312400a157061796d656e74735f6163636f756e745f6e616d6518022001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e04105123e0a137061796d656e74735f70726f66696c655f696418032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410512400a157061796d656e74735f70726f66696c655f6e616d6518042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312480a1d7365636f6e646172795f7061796d656e74735f70726f66696c655f696418052001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e041033a5eea415b0a25676f6f676c656164732e676f6f676c65617069732e636f6d2f42696c6c696e6753657475701232637573746f6d6572732f7b637573746f6d65727d2f62696c6c696e675365747570732f7b62696c6c696e675f73657475707d420c0a0a73746172745f74696d65420a0a08656e645f74696d6542fe010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e7265736f7572636573421142696c6c696e67536574757050726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56342e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56345c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a5265736f7572636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

