<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v6/common/simulation.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V6\Common;

class Simulation
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
            "0aeb1b0a2f676f6f676c652f6164732f676f6f676c656164732f76362f636f6d6d6f6e2f73696d756c6174696f6e2e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e226c0a1e4269644d6f64696669657253696d756c6174696f6e506f696e744c697374124a0a06706f696e747318012003280b323a2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e4269644d6f64696669657253696d756c6174696f6e506f696e7422620a1943706342696453696d756c6174696f6e506f696e744c69737412450a06706f696e747318012003280b32352e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e43706342696453696d756c6174696f6e506f696e7422620a1943707642696453696d756c6174696f6e506f696e744c69737412450a06706f696e747318012003280b32352e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e43707642696453696d756c6174696f6e506f696e7422680a1c54617267657443706153696d756c6174696f6e506f696e744c69737412480a06706f696e747318012003280b32382e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e54617267657443706153696d756c6174696f6e506f696e74226a0a1d546172676574526f617353696d756c6174696f6e506f696e744c69737412490a06706f696e747318012003280b32392e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e546172676574526f617353696d756c6174696f6e506f696e7422700a2050657263656e7443706342696453696d756c6174696f6e506f696e744c697374124c0a06706f696e747318012003280b323c2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e2e50657263656e7443706342696453696d756c6174696f6e506f696e7422d2060a1a4269644d6f64696669657253696d756c6174696f6e506f696e7412190a0c6269645f6d6f646966696572180f20012801480088010112210a146269646461626c655f636f6e76657273696f6e73181020012801480188010112270a1a6269646461626c655f636f6e76657273696f6e735f76616c7565181120012801480288010112130a06636c69636b73181220012803480388010112180a0b636f73745f6d6963726f73181320012803480488010112180a0b696d7072657373696f6e73181420012803480588010112210a14746f705f736c6f745f696d7072657373696f6e73181520012803480688010112280a1b706172656e745f6269646461626c655f636f6e76657273696f6e731816200128014807880101122e0a21706172656e745f6269646461626c655f636f6e76657273696f6e735f76616c75651817200128014808880101121a0a0d706172656e745f636c69636b731818200128034809880101121f0a12706172656e745f636f73745f6d6963726f73181920012803480a880101121f0a12706172656e745f696d7072657373696f6e73181a20012803480b88010112280a1b706172656e745f746f705f736c6f745f696d7072657373696f6e73181b20012803480c880101122a0a1d706172656e745f72657175697265645f6275646765745f6d6963726f73181c20012803480d880101420f0a0d5f6269645f6d6f64696669657242170a155f6269646461626c655f636f6e76657273696f6e73421d0a1b5f6269646461626c655f636f6e76657273696f6e735f76616c756542090a075f636c69636b73420e0a0c5f636f73745f6d6963726f73420e0a0c5f696d7072657373696f6e7342170a155f746f705f736c6f745f696d7072657373696f6e73421e0a1c5f706172656e745f6269646461626c655f636f6e76657273696f6e7342240a225f706172656e745f6269646461626c655f636f6e76657273696f6e735f76616c756542100a0e5f706172656e745f636c69636b7342150a135f706172656e745f636f73745f6d6963726f7342150a135f706172656e745f696d7072657373696f6e73421e0a1c5f706172656e745f746f705f736c6f745f696d7072657373696f6e7342200a1e5f706172656e745f72657175697265645f6275646765745f6d6963726f7322fb020a1543706342696453696d756c6174696f6e506f696e74121b0a0e6370635f6269645f6d6963726f73180820012803480088010112210a146269646461626c655f636f6e76657273696f6e73180920012801480188010112270a1a6269646461626c655f636f6e76657273696f6e735f76616c7565180a20012801480288010112130a06636c69636b73180b20012803480388010112180a0b636f73745f6d6963726f73180c20012803480488010112180a0b696d7072657373696f6e73180d20012803480588010112210a14746f705f736c6f745f696d7072657373696f6e73180e20012803480688010142110a0f5f6370635f6269645f6d6963726f7342170a155f6269646461626c655f636f6e76657273696f6e73421d0a1b5f6269646461626c655f636f6e76657273696f6e735f76616c756542090a075f636c69636b73420e0a0c5f636f73745f6d6963726f73420e0a0c5f696d7072657373696f6e7342170a155f746f705f736c6f745f696d7072657373696f6e7322b9010a1543707642696453696d756c6174696f6e506f696e74121b0a0e6370765f6269645f6d6963726f73180520012803480088010112180a0b636f73745f6d6963726f73180620012803480188010112180a0b696d7072657373696f6e73180720012803480288010112120a057669657773180820012803480388010142110a0f5f6370765f6269645f6d6963726f73420e0a0c5f636f73745f6d6963726f73420e0a0c5f696d7072657373696f6e7342080a065f76696577732284030a1854617267657443706153696d756c6174696f6e506f696e74121e0a117461726765745f6370615f6d6963726f73180820012803480088010112210a146269646461626c655f636f6e76657273696f6e73180920012801480188010112270a1a6269646461626c655f636f6e76657273696f6e735f76616c7565180a20012801480288010112130a06636c69636b73180b20012803480388010112180a0b636f73745f6d6963726f73180c20012803480488010112180a0b696d7072657373696f6e73180d20012803480588010112210a14746f705f736c6f745f696d7072657373696f6e73180e20012803480688010142140a125f7461726765745f6370615f6d6963726f7342170a155f6269646461626c655f636f6e76657273696f6e73421d0a1b5f6269646461626c655f636f6e76657273696f6e735f76616c756542090a075f636c69636b73420e0a0c5f636f73745f6d6963726f73420e0a0c5f696d7072657373696f6e7342170a155f746f705f736c6f745f696d7072657373696f6e7322f9020a19546172676574526f617353696d756c6174696f6e506f696e7412180a0b7461726765745f726f6173180820012801480088010112210a146269646461626c655f636f6e76657273696f6e73180920012801480188010112270a1a6269646461626c655f636f6e76657273696f6e735f76616c7565180a20012801480288010112130a06636c69636b73180b20012803480388010112180a0b636f73745f6d6963726f73180c20012803480488010112180a0b696d7072657373696f6e73180d20012803480588010112210a14746f705f736c6f745f696d7072657373696f6e73180e200128034806880101420e0a0c5f7461726765745f726f617342170a155f6269646461626c655f636f6e76657273696f6e73421d0a1b5f6269646461626c655f636f6e76657273696f6e735f76616c756542090a075f636c69636b73420e0a0c5f636f73745f6d6963726f73420e0a0c5f696d7072657373696f6e7342170a155f746f705f736c6f745f696d7072657373696f6e732292030a1c50657263656e7443706342696453696d756c6174696f6e506f696e7412230a1670657263656e745f6370635f6269645f6d6963726f73180120012803480088010112210a146269646461626c655f636f6e76657273696f6e73180220012801480188010112270a1a6269646461626c655f636f6e76657273696f6e735f76616c7565180320012801480288010112130a06636c69636b73180420012803480388010112180a0b636f73745f6d6963726f73180520012803480488010112180a0b696d7072657373696f6e73180620012803480588010112210a14746f705f736c6f745f696d7072657373696f6e73180720012803480688010142190a175f70657263656e745f6370635f6269645f6d6963726f7342170a155f6269646461626c655f636f6e76657273696f6e73421d0a1b5f6269646461626c655f636f6e76657273696f6e735f76616c756542090a075f636c69636b73420e0a0c5f636f73745f6d6963726f73420e0a0c5f696d7072657373696f6e7342170a155f746f705f736c6f745f696d7072657373696f6e7342ea010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76362e636f6d6d6f6e420f53696d756c6174696f6e50726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76362f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56362e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56365c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56363a3a436f6d6d6f6e620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

