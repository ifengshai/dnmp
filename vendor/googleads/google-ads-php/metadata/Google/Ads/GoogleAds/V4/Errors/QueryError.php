<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/errors/query_error.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V4\Errors;

class QueryError
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
            "0a9d100a30676f6f676c652f6164732f676f6f676c656164732f76342f6572726f72732f71756572795f6572726f722e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76342e6572726f727322d30d0a0e51756572794572726f72456e756d22c00d0a0a51756572794572726f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120f0a0b51554552595f4552524f52103212150a114241445f454e554d5f434f4e5354414e54101212170a134241445f4553434150455f53455155454e4345100712120a0e4241445f4649454c445f4e414d45100c12130a0f4241445f4c494d49545f56414c5545100f120e0a0a4241445f4e554d424552100512100a0c4241445f4f50455241544f52100312160a124241445f504152414d455445525f4e414d45103d12170a134241445f504152414d455445525f56414c5545103e12240a204241445f5245534f555243455f545950455f494e5f46524f4d5f434c41555345102d120e0a0a4241445f53594d424f4c1002120d0a094241445f56414c5545100412170a13444154455f52414e47455f544f4f5f57494445102412190a15444154455f52414e47455f544f4f5f4e4152524f57103c12100a0c45585045435445445f414e44101e120f0a0b45585045435445445f4259100e122d0a2945585045435445445f44494d454e53494f4e5f4649454c445f494e5f53454c4543545f434c41555345102512220a1e45585045435445445f46494c544552535f4f4e5f444154455f52414e4745103712110a0d45585045435445445f46524f4d102c12110a0d45585045435445445f4c4953541029122e0a2a45585045435445445f5245464552454e4345445f4649454c445f494e5f53454c4543545f434c41555345101012130a0f45585045435445445f53454c454354100d12190a1545585045435445445f53494e474c455f56414c5545102a12280a2445585045435445445f56414c55455f574954485f4245545745454e5f4f50455241544f52101d12170a13494e56414c49445f444154455f464f524d4154102612180a14494e56414c49445f535452494e475f56414c5545103912270a23494e56414c49445f56414c55455f574954485f4245545745454e5f4f50455241544f52101a12260a22494e56414c49445f56414c55455f574954485f445552494e475f4f50455241544f52101612240a20494e56414c49445f56414c55455f574954485f4c494b455f4f50455241544f521038121b0a174f50455241544f525f4649454c445f4d49534d41544348102312260a2250524f484942495445445f454d5054595f4c4953545f494e5f434f4e444954494f4e101c121c0a1850524f484942495445445f454e554d5f434f4e5354414e54103612310a2d50524f484942495445445f4649454c445f434f4d42494e4154494f4e5f494e5f53454c4543545f434c41555345101f12270a2350524f484942495445445f4649454c445f494e5f4f524445525f42595f434c41555345102812250a2150524f484942495445445f4649454c445f494e5f53454c4543545f434c41555345101712240a2050524f484942495445445f4649454c445f494e5f57484552455f434c415553451018122b0a2750524f484942495445445f5245534f555243455f545950455f494e5f46524f4d5f434c41555345102b122d0a2950524f484942495445445f5245534f555243455f545950455f494e5f53454c4543545f434c415553451030122c0a2850524f484942495445445f5245534f555243455f545950455f494e5f57484552455f434c41555345103a122f0a2b50524f484942495445445f4d45545249435f494e5f53454c4543545f4f525f57484552455f434c41555345103112300a2c50524f484942495445445f5345474d454e545f494e5f53454c4543545f4f525f57484552455f434c415553451033123c0a3850524f484942495445445f5345474d454e545f574954485f4d45545249435f494e5f53454c4543545f4f525f57484552455f434c41555345103512170a134c494d49545f56414c55455f544f4f5f4c4f57101912200a1c50524f484942495445445f4e45574c494e455f494e5f535452494e47100812280a2450524f484942495445445f56414c55455f434f4d42494e4154494f4e5f494e5f4c495354100a12360a3250524f484942495445445f56414c55455f434f4d42494e4154494f4e5f574954485f4245545745454e5f4f50455241544f52101512190a15535452494e475f4e4f545f5445524d494e41544544100612150a11544f4f5f4d414e595f5345474d454e54531022121b0a17554e45585045435445445f454e445f4f465f51554552591009121a0a16554e45585045435445445f46524f4d5f434c41555345102f12160a12554e5245434f474e495a45445f4649454c44102012140a10554e45585045435445445f494e505554100b12210a1d5245515545535445445f4d4554524943535f464f525f4d414e41474552103b42ea010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e6572726f7273420f51756572794572726f7250726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f6572726f72733b6572726f7273a20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56342e4572726f7273ca021e476f6f676c655c4164735c476f6f676c654164735c56345c4572726f7273ea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a4572726f7273620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

