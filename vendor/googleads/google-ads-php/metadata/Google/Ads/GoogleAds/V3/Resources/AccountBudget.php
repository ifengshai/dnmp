<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/resources/account_budget.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Resources;

class AccountBudget
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
            "0ae9030a40676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f6163636f756e745f6275646765745f70726f706f73616c5f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732287010a1d4163636f756e7442756467657450726f706f73616c54797065456e756d22660a194163636f756e7442756467657450726f706f73616c54797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120a0a064352454154451002120a0a06555044415445100312070a03454e441004120a0a0652454d4f5645100542f3010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73421e4163636f756e7442756467657450726f706f73616c5479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330acc030a39676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f6163636f756e745f6275646765745f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322780a174163636f756e74427564676574537461747573456e756d225d0a134163636f756e74427564676574537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a0750454e44494e471002120c0a08415050524f5645441003120d0a0943414e43454c4c4544100442ed010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7342184163636f756e7442756467657453746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330aa8030a37676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f7370656e64696e675f6c696d69745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7322580a155370656e64696e674c696d697454797065456e756d223f0a115370656e64696e674c696d697454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120c0a08494e46494e495445100242eb010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7342165370656e64696e674c696d69745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330a8b030a2d676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f74696d655f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73224e0a0c54696d6554797065456e756d223e0a0854696d6554797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112070a034e4f571002120b0a07464f5245564552100342e2010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73420d54696d655479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330add1b0a36676f6f676c652f6164732f676f6f676c656164732f76332f7265736f75726365732f6163636f756e745f6275646765742e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365731a39676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f6163636f756e745f6275646765745f7374617475732e70726f746f1a37676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f7370656e64696e675f6c696d69745f747970652e70726f746f1a2d676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f74696d655f747970652e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22d8160a0d4163636f756e7442756467657412450a0d7265736f757263655f6e616d65180120012809422ee04103fa41280a26676f6f676c656164732e676f6f676c65617069732e636f6d2f4163636f756e74427564676574122c0a02696418022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410312620a0d62696c6c696e675f736574757018032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c7565422de04103fa41270a25676f6f676c656164732e676f6f676c65617069732e636f6d2f42696c6c696e675365747570125f0a0673746174757318042001280e324a2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e4163636f756e74427564676574537461747573456e756d2e4163636f756e744275646765745374617475734203e04103122f0a046e616d6518052001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312430a1870726f706f7365645f73746172745f646174655f74696d6518062001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312430a18617070726f7665645f73746172745f646174655f74696d6518072001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312420a18746f74616c5f61646a7573746d656e74735f6d6963726f7318122001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e04103123e0a14616d6f756e745f7365727665645f6d6963726f7318132001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e0410312400a1570757263686173655f6f726465725f6e756d62657218142001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312300a056e6f74657318152001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e04103126c0a1070656e64696e675f70726f706f73616c18162001280b324d2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f75726365732e4163636f756e744275646765742e50656e64696e674163636f756e7442756467657450726f706f73616c4203e0410312430a1670726f706f7365645f656e645f646174655f74696d6518082001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e041034800125b0a1670726f706f7365645f656e645f74696d655f7479706518092001280e32342e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e54696d6554797065456e756d2e54696d65547970654203e04103480012430a16617070726f7665645f656e645f646174655f74696d65180a2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e041034801125b0a16617070726f7665645f656e645f74696d655f74797065180b2001280e32342e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e54696d6554797065456e756d2e54696d65547970654203e041034801124a0a1e70726f706f7365645f7370656e64696e675f6c696d69745f6d6963726f73180c2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e04103480212730a1c70726f706f7365645f7370656e64696e675f6c696d69745f74797065180d2001280e32462e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e5370656e64696e674c696d697454797065456e756d2e5370656e64696e674c696d6974547970654203e041034802124a0a1e617070726f7665645f7370656e64696e675f6c696d69745f6d6963726f73180e2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e04103480312730a1c617070726f7665645f7370656e64696e675f6c696d69745f74797065180f2001280e32462e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e5370656e64696e674c696d697454797065456e756d2e5370656e64696e674c696d6974547970654203e041034803124a0a1e61646a75737465645f7370656e64696e675f6c696d69745f6d6963726f7318102001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e04103480412730a1c61646a75737465645f7370656e64696e675f6c696d69745f7479706518112001280e32462e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e5370656e64696e674c696d697454797065456e756d2e5370656e64696e674c696d6974547970654203e0410348041a86070a1c50656e64696e674163636f756e7442756467657450726f706f73616c12750a176163636f756e745f6275646765745f70726f706f73616c18012001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654236e04103fa41300a2e676f6f676c656164732e676f6f676c65617069732e636f6d2f4163636f756e7442756467657450726f706f73616c12720a0d70726f706f73616c5f7479706518022001280e32562e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e4163636f756e7442756467657450726f706f73616c54797065456e756d2e4163636f756e7442756467657450726f706f73616c547970654203e04103122f0a046e616d6518032001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e04103123a0a0f73746172745f646174655f74696d6518042001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312400a1570757263686173655f6f726465725f6e756d62657218092001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e0410312300a056e6f746573180a2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e04103123d0a126372656174696f6e5f646174655f74696d65180b2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e04103123a0a0d656e645f646174655f74696d6518052001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c75654203e04103480012520a0d656e645f74696d655f7479706518062001280e32342e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e54696d6554797065456e756d2e54696d65547970654203e04103480012410a157370656e64696e675f6c696d69745f6d6963726f7318072001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c75654203e041034801126a0a137370656e64696e675f6c696d69745f7479706518082001280e32462e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e5370656e64696e674c696d697454797065456e756d2e5370656e64696e674c696d6974547970654203e041034801420a0a08656e645f74696d6542100a0e7370656e64696e675f6c696d69743a61ea415e0a26676f6f676c656164732e676f6f676c65617069732e636f6d2f4163636f756e744275646765741234637573746f6d6572732f7b637573746f6d65727d2f6163636f756e74427564676574732f7b6163636f756e745f6275646765747d42130a1170726f706f7365645f656e645f74696d6542130a11617070726f7665645f656e645f74696d6542190a1770726f706f7365645f7370656e64696e675f6c696d697442190a17617070726f7665645f7370656e64696e675f6c696d697442190a1761646a75737465645f7370656e64696e675f6c696d697442ff010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e7265736f757263657342124163636f756e7442756467657450726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56332e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56335c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a5265736f7572636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

