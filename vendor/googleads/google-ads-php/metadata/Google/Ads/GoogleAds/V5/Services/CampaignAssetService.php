<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/services/campaign_asset_service.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V5\Services;

class CampaignAssetService
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Api\Http::initOnce();
        \GPBMetadata\Google\Api\Annotations::initOnce();
        \GPBMetadata\Google\Api\Resource::initOnce();
        \GPBMetadata\Google\Api\FieldBehavior::initOnce();
        \GPBMetadata\Google\Api\Client::initOnce();
        \GPBMetadata\Google\Protobuf\Any::initOnce();
        \GPBMetadata\Google\Rpc\Status::initOnce();
        $pool->internalAddGeneratedFile(hex2bin(
            "0a94040a34676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f61737365745f6669656c645f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322c9010a1241737365744669656c6454797065456e756d22b2010a0e41737365744669656c6454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120c0a08484541444c494e451002120f0a0b4445534352495054494f4e100312150a114d414e4441544f52595f41445f54455854100412130a0f4d41524b4554494e475f494d414745100512100a0c4d454449415f42554e444c45100612110a0d594f55545542455f564944454f100712120a0e424f4f4b5f4f4e5f474f4f474c45100842e8010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421341737365744669656c645479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330aac030a35676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f61737365745f6c696e6b5f7374617475732e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76352e656e756d7322600a1341737365744c696e6b537461747573456e756d22490a0f41737365744c696e6b537461747573120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001120b0a07454e41424c45441002120b0a0752454d4f564544100342e9010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d73421441737365744c696e6b53746174757350726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56352e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56355c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a456e756d73620670726f746f330afb070a36676f6f676c652f6164732f676f6f676c656164732f76352f7265736f75726365732f63616d706169676e5f61737365742e70726f746f1221676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365731a35676f6f676c652f6164732f676f6f676c656164732f76352f656e756d732f61737365745f6c696e6b5f7374617475732e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f2282040a0d43616d706169676e417373657412450a0d7265736f757263655f6e616d65180120012809422ee04105fa41280a26676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e417373657412400a0863616d706169676e1806200128094229e04105fa41230a21676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e4800880101123a0a0561737365741807200128094226e04105fa41200a1e676f6f676c656164732e676f6f676c65617069732e636f6d2f4173736574480188010112590a0a6669656c645f7479706518042001280e32402e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e41737365744669656c6454797065456e756d2e41737365744669656c64547970654203e0410512570a0673746174757318052001280e32422e676f6f676c652e6164732e676f6f676c656164732e76352e656e756d732e41737365744c696e6b537461747573456e756d2e41737365744c696e6b5374617475734203e041033a61ea415e0a26676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e41737365741234637573746f6d6572732f7b637573746f6d65727d2f63616d706169676e4173736574732f7b63616d706169676e5f61737365747d420b0a095f63616d706169676e42080a065f617373657442ff010a25636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f7572636573421243616d706169676e417373657450726f746f50015a4a676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f7265736f75726365733b7265736f7572636573a20203474141aa0221476f6f676c652e4164732e476f6f676c654164732e56352e5265736f7572636573ca0221476f6f676c655c4164735c476f6f676c654164735c56355c5265736f7572636573ea0225476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a5265736f7572636573620670726f746f330add0c0a3d676f6f676c652f6164732f676f6f676c656164732f76352f73657276696365732f63616d706169676e5f61737365745f736572766963652e70726f746f1220676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365731a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f1a17676f6f676c652f6170692f636c69656e742e70726f746f1a1f676f6f676c652f6170692f6669656c645f6265686176696f722e70726f746f1a19676f6f676c652f6170692f7265736f757263652e70726f746f1a17676f6f676c652f7270632f7374617475732e70726f746f22600a1747657443616d706169676e41737365745265717565737412450a0d7265736f757263655f6e616d65180120012809422ee04102fa41280a26676f6f676c656164732e676f6f676c65617069732e636f6d2f43616d706169676e417373657422ba010a1b4d757461746543616d706169676e4173736574735265717565737412180a0b637573746f6d65725f69641801200128094203e0410212510a0a6f7065726174696f6e7318022003280b32382e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e43616d706169676e41737365744f7065726174696f6e4203e0410212170a0f7061727469616c5f6661696c75726518032001280812150a0d76616c69646174655f6f6e6c79180420012808227b0a1643616d706169676e41737365744f7065726174696f6e12420a0663726561746518012001280b32302e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e43616d706169676e4173736574480012100a0672656d6f76651802200128094800420b0a096f7065726174696f6e229f010a1c4d757461746543616d706169676e417373657473526573706f6e736512310a157061727469616c5f6661696c7572655f6572726f7218012001280b32122e676f6f676c652e7270632e537461747573124c0a07726573756c747318022003280b323b2e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e4d757461746543616d706169676e4173736574526573756c7422320a194d757461746543616d706169676e4173736574526573756c7412150a0d7265736f757263655f6e616d6518012001280932f0030a1443616d706169676e41737365745365727669636512c9010a1047657443616d706169676e417373657412392e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e47657443616d706169676e4173736574526571756573741a302e676f6f676c652e6164732e676f6f676c656164732e76352e7265736f75726365732e43616d706169676e4173736574224882d3e493023212302f76352f7b7265736f757263655f6e616d653d637573746f6d6572732f2a2f63616d706169676e4173736574732f2a7dda410d7265736f757263655f6e616d6512ee010a144d757461746543616d706169676e417373657473123d2e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e4d757461746543616d706169676e417373657473526571756573741a3e2e676f6f676c652e6164732e676f6f676c656164732e76352e73657276696365732e4d757461746543616d706169676e417373657473526573706f6e7365225782d3e493023822332f76352f637573746f6d6572732f7b637573746f6d65725f69643d2a7d2f63616d706169676e4173736574733a6d75746174653a012ada4116637573746f6d65725f69642c6f7065726174696f6e731a1bca4118676f6f676c656164732e676f6f676c65617069732e636f6d4280020a24636f6d2e676f6f676c652e6164732e676f6f676c656164732e76352e7365727669636573421943616d706169676e41737365745365727669636550726f746f50015a48676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76352f73657276696365733b7365727669636573a20203474141aa0220476f6f676c652e4164732e476f6f676c654164732e56352e5365727669636573ca0220476f6f676c655c4164735c476f6f676c654164735c56355c5365727669636573ea0224476f6f676c653a3a4164733a3a476f6f676c654164733a3a56353a3a5365727669636573620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

