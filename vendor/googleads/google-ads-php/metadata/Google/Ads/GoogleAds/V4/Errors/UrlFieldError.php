<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/errors/url_field_error.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V4\Errors;

class UrlFieldError
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
            "0a87110a34676f6f676c652f6164732f676f6f676c656164732f76342f6572726f72732f75726c5f6669656c645f6572726f722e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76342e6572726f727322b60e0a1155726c4669656c644572726f72456e756d22a00e0a0d55726c4669656c644572726f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112210a1d494e56414c49445f545241434b494e475f55524c5f54454d504c415445100212280a24494e56414c49445f5441475f494e5f545241434b494e475f55524c5f54454d504c415445100312250a214d495353494e475f545241434b494e475f55524c5f54454d504c4154455f5441471004122d0a294d495353494e475f50524f544f434f4c5f494e5f545241434b494e475f55524c5f54454d504c4154451005122d0a29494e56414c49445f50524f544f434f4c5f494e5f545241434b494e475f55524c5f54454d504c415445100612230a1f4d414c464f524d45445f545241434b494e475f55524c5f54454d504c415445100712290a254d495353494e475f484f53545f494e5f545241434b494e475f55524c5f54454d504c415445100812280a24494e56414c49445f544c445f494e5f545241434b494e475f55524c5f54454d504c4154451009122e0a2a524544554e44414e545f4e45535445445f545241434b494e475f55524c5f54454d504c4154455f544147100a12150a11494e56414c49445f46494e414c5f55524c100b121c0a18494e56414c49445f5441475f494e5f46494e414c5f55524c100c12220a1e524544554e44414e545f4e45535445445f46494e414c5f55524c5f544147100d12210a1d4d495353494e475f50524f544f434f4c5f494e5f46494e414c5f55524c100e12210a1d494e56414c49445f50524f544f434f4c5f494e5f46494e414c5f55524c100f12170a134d414c464f524d45445f46494e414c5f55524c1010121d0a194d495353494e475f484f53545f494e5f46494e414c5f55524c1011121c0a18494e56414c49445f544c445f494e5f46494e414c5f55524c1012121c0a18494e56414c49445f46494e414c5f4d4f42494c455f55524c101312230a1f494e56414c49445f5441475f494e5f46494e414c5f4d4f42494c455f55524c101412290a25524544554e44414e545f4e45535445445f46494e414c5f4d4f42494c455f55524c5f544147101512280a244d495353494e475f50524f544f434f4c5f494e5f46494e414c5f4d4f42494c455f55524c101612280a24494e56414c49445f50524f544f434f4c5f494e5f46494e414c5f4d4f42494c455f55524c1017121e0a1a4d414c464f524d45445f46494e414c5f4d4f42494c455f55524c101812240a204d495353494e475f484f53545f494e5f46494e414c5f4d4f42494c455f55524c101912230a1f494e56414c49445f544c445f494e5f46494e414c5f4d4f42494c455f55524c101a12190a15494e56414c49445f46494e414c5f4150505f55524c101b12200a1c494e56414c49445f5441475f494e5f46494e414c5f4150505f55524c101c12260a22524544554e44414e545f4e45535445445f46494e414c5f4150505f55524c5f544147101d12200a1c4d554c5449504c455f4150505f55524c535f464f525f4f5354595045101e12120a0e494e56414c49445f4f5354595045101f12200a1c494e56414c49445f50524f544f434f4c5f464f525f4150505f55524c102012220a1e494e56414c49445f5041434b4147455f49445f464f525f4150505f55524c1021122d0a2955524c5f435553544f4d5f504152414d45544552535f434f554e545f455843454544535f4c494d4954102212320a2e494e56414c49445f434841524143544552535f494e5f55524c5f435553544f4d5f504152414d455445525f4b4559102712340a30494e56414c49445f434841524143544552535f494e5f55524c5f435553544f4d5f504152414d455445525f56414c55451028122d0a29494e56414c49445f5441475f494e5f55524c5f435553544f4d5f504152414d455445525f56414c55451029122d0a29524544554e44414e545f4e45535445445f55524c5f435553544f4d5f504152414d455445525f544147102a12140a104d495353494e475f50524f544f434f4c102b12140a10494e56414c49445f50524f544f434f4c1034120f0a0b494e56414c49445f55524c102c121e0a1a44455354494e4154494f4e5f55524c5f44455052454341544544102d12160a12494e56414c49445f5441475f494e5f55524c102e12130a0f4d495353494e475f55524c5f544147102f12140a104455504c49434154455f55524c5f4944103012120a0e494e56414c49445f55524c5f49441031121e0a1a46494e414c5f55524c5f5355464649585f4d414c464f524d4544103212230a1f494e56414c49445f5441475f494e5f46494e414c5f55524c5f5355464649581033121c0a18494e56414c49445f544f505f4c4556454c5f444f4d41494e1035121e0a1a4d414c464f524d45445f544f505f4c4556454c5f444f4d41494e103612110a0d4d414c464f524d45445f55524c103712100a0c4d495353494e475f484f53541038121f0a1b4e554c4c5f435553544f4d5f504152414d455445525f56414c5545103942ed010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e6572726f7273421255726c4669656c644572726f7250726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f6572726f72733b6572726f7273a20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56342e4572726f7273ca021e476f6f676c655c4164735c476f6f676c654164735c56345c4572726f7273ea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a4572726f7273620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

