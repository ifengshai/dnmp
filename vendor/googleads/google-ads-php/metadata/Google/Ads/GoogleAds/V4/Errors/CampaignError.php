<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v4/errors/campaign_error.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V4\Errors;

class CampaignError
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
            "0afe0f0a33676f6f676c652f6164732f676f6f676c656164732f76342f6572726f72732f63616d706169676e5f6572726f722e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76342e6572726f727322ae0d0a1143616d706169676e4572726f72456e756d22980d0a0d43616d706169676e4572726f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112210a1d43414e4e4f545f5441524745545f434f4e54454e545f4e4554574f524b100312200a1c43414e4e4f545f5441524745545f5345415243485f4e4554574f524b100412360a3243414e4e4f545f5441524745545f5345415243485f4e4554574f524b5f574954484f55545f474f4f474c455f534541524348100512300a2c43414e4e4f545f5441524745545f474f4f474c455f5345415243485f464f525f43504d5f43414d504149474e1006122d0a2943414d504149474e5f4d5553545f5441524745545f41545f4c454153545f4f4e455f4e4554574f524b100712280a2443414e4e4f545f5441524745545f504152544e45525f5345415243485f4e4554574f524b1008124b0a4743414e4e4f545f5441524745545f434f4e54454e545f4e4554574f524b5f4f4e4c595f574954485f43524954455249415f4c4556454c5f42494444494e475f5354524154454759100912360a3243414d504149474e5f4455524154494f4e5f4d5553545f434f4e5441494e5f414c4c5f52554e4e41424c455f545249414c53100a12240a2043414e4e4f545f4d4f444946595f464f525f545249414c5f43414d504149474e100b121b0a174455504c49434154455f43414d504149474e5f4e414d45100c121f0a1b494e434f4d50415449424c455f43414d504149474e5f4649454c44100d12190a15494e56414c49445f43414d504149474e5f4e414d45100e122a0a26494e56414c49445f41445f53455256494e475f4f5054494d495a4154494f4e5f535441545553100f12180a14494e56414c49445f545241434b494e475f55524c1010123e0a3a43414e4e4f545f5345545f424f54485f545241434b494e475f55524c5f54454d504c4154455f414e445f545241434b494e475f53455454494e47101112200a1c4d41585f494d5052455353494f4e535f4e4f545f494e5f52414e47451012121b0a1754494d455f554e49545f4e4f545f535550504f52544544101312310a2d494e56414c49445f4f5045524154494f4e5f49465f53455256494e475f5354415455535f4841535f454e4445441014121b0a174255444745545f43414e4e4f545f42455f534841524544101512250a2143414d504149474e5f43414e4e4f545f5553455f5348415245445f425544474554101612300a2c43414e4e4f545f4348414e47455f4255444745545f4f4e5f43414d504149474e5f574954485f545249414c53101712210a1d43414d504149474e5f4c4142454c5f444f45535f4e4f545f4558495354101812210a1d43414d504149474e5f4c4142454c5f414c52454144595f4558495354531019121c0a184d495353494e475f53484f5050494e475f53455454494e47101a12220a1e494e56414c49445f53484f5050494e475f53414c45535f434f554e545259101b123b0a374144564552544953494e475f4348414e4e454c5f545950455f4e4f545f415641494c41424c455f464f525f4143434f554e545f54595045101f12280a24494e56414c49445f4144564552544953494e475f4348414e4e454c5f5355425f545950451020122c0a2841545f4c454153545f4f4e455f434f4e56455253494f4e5f4d5553545f42455f53454c45435445441021121f0a1b43414e4e4f545f5345545f41445f524f544154494f4e5f4d4f44451022122f0a2b43414e4e4f545f4d4f444946595f53544152545f444154455f49465f414c52454144595f535441525445441023121b0a1743414e4e4f545f5345545f444154455f544f5f504153541024121f0a1b4d495353494e475f484f54454c5f435553544f4d45525f4c494e4b1025121f0a1b494e56414c49445f484f54454c5f435553544f4d45525f4c494e4b102612190a154d495353494e475f484f54454c5f53455454494e47102712420a3e43414e4e4f545f5553455f5348415245445f43414d504149474e5f4255444745545f5748494c455f504152545f4f465f43414d504149474e5f47524f5550102812110a0d4150505f4e4f545f464f554e44102912390a3553484f5050494e475f454e41424c455f4c4f43414c5f4e4f545f535550504f525445445f464f525f43414d504149474e5f54595045102a12330a2f4d45524348414e545f4e4f545f414c4c4f5745445f464f525f434f4d50415249534f4e5f4c495354494e475f414453102b12230a1f494e53554646494349454e545f4150505f494e5354414c4c535f434f554e54102c121a0a1653454e5349544956455f43415445474f52595f415050102d42ed010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76342e6572726f7273421243616d706169676e4572726f7250726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76342f6572726f72733b6572726f7273a20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56342e4572726f7273ca021e476f6f676c655c4164735c476f6f676c654164735c56345c4572726f7273ea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56343a3a4572726f7273620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

