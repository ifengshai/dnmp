<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/errors/ad_error.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Errors;

class AdError
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
            "0acf250a2d676f6f676c652f6164732f676f6f676c656164732f76332f6572726f72732f61645f6572726f722e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76332e6572726f7273228b230a0b41644572726f72456e756d22fb220a0741644572726f72120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e1001122c0a2841445f435553544f4d495a4552535f4e4f545f535550504f525445445f464f525f41445f545950451002121a0a16415050524f58494d4154454c595f544f4f5f4c4f4e471003121b0a17415050524f58494d4154454c595f544f4f5f53484f52541004120f0a0b4241445f534e4950504554100512140a1043414e4e4f545f4d4f444946595f4144100612270a2343414e4e4f545f5345545f425553494e4553535f4e414d455f49465f55524c5f534554100712140a1043414e4e4f545f5345545f4649454c441008122a0a2643414e4e4f545f5345545f4649454c445f574954485f4f524947494e5f41445f49445f5345541009122f0a2b43414e4e4f545f5345545f4649454c445f574954485f41445f49445f5345545f464f525f53484152494e47100a12290a2543414e4e4f545f5345545f414c4c4f575f464c455849424c455f434f4c4f525f46414c5345100b12370a3343414e4e4f545f5345545f434f4c4f525f434f4e54524f4c5f5748454e5f4e41544956455f464f524d41545f53455454494e47100c12120a0e43414e4e4f545f5345545f55524c100d12210a1d43414e4e4f545f5345545f574954484f55545f46494e414c5f55524c53100e121e0a1a43414e4e4f545f5345545f574954485f46494e414c5f55524c53100f121c0a1843414e4e4f545f5345545f574954485f55524c5f44415441101112270a2343414e4e4f545f5553455f41445f535542434c4153535f464f525f4f50455241544f52101212230a1f435553544f4d45525f4e4f545f415050524f5645445f4d4f42494c45414453101312280a24435553544f4d45525f4e4f545f415050524f5645445f544849524450415254595f414453101412310a2d435553544f4d45525f4e4f545f415050524f5645445f544849524450415254595f52454449524543545f414453101512190a15435553544f4d45525f4e4f545f454c494749424c45101612310a2d435553544f4d45525f4e4f545f454c494749424c455f464f525f5550444154494e475f424541434f4e5f55524c1017121e0a1a44494d454e53494f4e5f414c52454144595f494e5f554e494f4e101812190a1544494d454e53494f4e5f4d5553545f42455f5345541019121a0a1644494d454e53494f4e5f4e4f545f494e5f554e494f4e101a12230a1f444953504c41595f55524c5f43414e4e4f545f42455f535045434946494544101b12200a1c444f4d45535449435f50484f4e455f4e554d4245525f464f524d4154101c121a0a16454d455247454e43595f50484f4e455f4e554d424552101d120f0a0b454d5054595f4649454c44101e12300a2c464545445f4154545249425554455f4d5553545f484156455f4d415050494e475f464f525f545950455f4944101f12280a24464545445f4154545249425554455f4d415050494e475f545950455f4d49534d41544348102012210a1d494c4c4547414c5f41445f435553544f4d495a45525f5441475f555345102112130a0f494c4c4547414c5f5441475f5553451022121b0a17494e434f4e53495354454e545f44494d454e53494f4e53102312290a25494e434f4e53495354454e545f5354415455535f494e5f54454d504c4154455f554e494f4e102412140a10494e434f52524543545f4c454e4754481025121a0a16494e454c494749424c455f464f525f55504752414445102612260a22494e56414c49445f41445f414444524553535f43414d504149474e5f544152474554102712130a0f494e56414c49445f41445f54595045102812270a23494e56414c49445f415454524942555445535f464f525f4d4f42494c455f494d414745102912260a22494e56414c49445f415454524942555445535f464f525f4d4f42494c455f54455854102a121f0a1b494e56414c49445f43414c4c5f544f5f414354494f4e5f54455854102b121d0a19494e56414c49445f4348415241435445525f464f525f55524c102c12180a14494e56414c49445f434f554e5452595f434f4445102d122a0a26494e56414c49445f455850414e4445445f44594e414d49435f5345415243485f41445f544147102f12110a0d494e56414c49445f494e5055541030121b0a17494e56414c49445f4d41524b55505f4c414e47554147451031121a0a16494e56414c49445f4d4f42494c455f43415252494552103212210a1d494e56414c49445f4d4f42494c455f434152524945525f5441524745541033121e0a1a494e56414c49445f4e554d4245525f4f465f454c454d454e54531034121f0a1b494e56414c49445f50484f4e455f4e554d4245525f464f524d4154103512310a2d494e56414c49445f524943485f4d454449415f4345525449464945445f56454e444f525f464f524d41545f4944103612190a15494e56414c49445f54454d504c4154455f44415441103712270a23494e56414c49445f54454d504c4154455f454c454d454e545f4649454c445f54595045103812170a13494e56414c49445f54454d504c4154455f4944103912110a0d4c494e455f544f4f5f57494445103a12210a1d4d495353494e475f41445f435553544f4d495a45525f4d415050494e47103b121d0a194d495353494e475f414444524553535f434f4d504f4e454e54103c121e0a1a4d495353494e475f4144564552544953454d454e545f4e414d45103d12190a154d495353494e475f425553494e4553535f4e414d45103e12180a144d495353494e475f4445534352495054494f4e31103f12180a144d495353494e475f4445534352495054494f4e321040121f0a1b4d495353494e475f44455354494e4154494f4e5f55524c5f544147104112200a1c4d495353494e475f4c414e44494e475f504147455f55524c5f544147104212150a114d495353494e475f44494d454e53494f4e104312170a134d495353494e475f444953504c41595f55524c104412140a104d495353494e475f484541444c494e45104512120a0e4d495353494e475f484549474854104612110a0d4d495353494e475f494d4147451047122d0a294d495353494e475f4d41524b4554494e475f494d4147455f4f525f50524f445543545f564944454f531048121c0a184d495353494e475f4d41524b55505f4c414e4755414745531049121a0a164d495353494e475f4d4f42494c455f43415252494552104a12110a0d4d495353494e475f50484f4e45104b12240a204d495353494e475f52455155495245445f54454d504c4154455f4649454c4453104c12200a1c4d495353494e475f54454d504c4154455f4649454c445f56414c5545104d12100a0c4d495353494e475f54455854104e12170a134d495353494e475f56495349424c455f55524c104f12110a0d4d495353494e475f5749445448105012270a234d554c5449504c455f44495354494e43545f46454544535f554e535550504f52544544105112240a204d5553545f5553455f54454d505f41445f554e494f4e5f49445f4f4e5f4144441052120c0a08544f4f5f4c4f4e471053120d0a09544f4f5f53484f5254105412220a1e554e494f4e5f44494d454e53494f4e535f43414e4e4f545f4348414e47451055121d0a19554e4b4e4f574e5f414444524553535f434f4d504f4e454e54105612160a12554e4b4e4f574e5f4649454c445f4e414d45105712170a13554e4b4e4f574e5f554e495155455f4e414d451058121a0a16554e535550504f525445445f44494d454e53494f4e53105912160a1255524c5f494e56414c49445f534348454d45105a12200a1c55524c5f494e56414c49445f544f505f4c4556454c5f444f4d41494e105b12110a0d55524c5f4d414c464f524d4544105c120f0a0b55524c5f4e4f5f484f5354105d12160a1255524c5f4e4f545f4551554956414c454e54105e121a0a1655524c5f484f53545f4e414d455f544f4f5f4c4f4e47105f12110a0d55524c5f4e4f5f534348454d451060121b0a1755524c5f4e4f5f544f505f4c4556454c5f444f4d41494e106112180a1455524c5f504154485f4e4f545f414c4c4f574544106212180a1455524c5f504f52545f4e4f545f414c4c4f574544106312190a1555524c5f51554552595f4e4f545f414c4c4f574544106412340a3055524c5f534348454d455f4245464f52455f455850414e4445445f44594e414d49435f5345415243485f41445f544147106612290a25555345525f444f45535f4e4f545f484156455f4143434553535f544f5f54454d504c415445106712240a20494e434f4e53495354454e545f455850414e4441424c455f53455454494e4753106812120a0e494e56414c49445f464f524d4154106912160a12494e56414c49445f4649454c445f54455854106a12170a13454c454d454e545f4e4f545f50524553454e54106b120f0a0b494d4147455f4552524f52106c12160a1256414c55455f4e4f545f494e5f52414e4745106d12150a114649454c445f4e4f545f50524553454e54106e12180a14414444524553535f4e4f545f434f4d504c455445106f12130a0f414444524553535f494e56414c4944107012190a15564944454f5f52455452494556414c5f4552524f521071120f0a0b415544494f5f4552524f521072121f0a1b494e56414c49445f594f55545542455f444953504c41595f55524c1073121b0a17544f4f5f4d414e595f50524f445543545f494d414745531074121b0a17544f4f5f4d414e595f50524f445543545f564944454f531075122e0a2a494e434f4d50415449424c455f41445f545950455f414e445f4445564943455f505245464552454e43451076122a0a2643414c4c545241434b494e475f4e4f545f535550504f525445445f464f525f434f554e5452591077122d0a29434152524945525f53504543494649435f53484f52545f4e554d4245525f4e4f545f414c4c4f5745441078121a0a16444953414c4c4f5745445f4e554d4245525f545950451079122a0a2650484f4e455f4e554d4245525f4e4f545f535550504f525445445f464f525f434f554e545259107a123c0a3850484f4e455f4e554d4245525f4e4f545f535550504f525445445f574954485f43414c4c545241434b494e475f464f525f434f554e545259107b12230a1f5052454d49554d5f524154455f4e554d4245525f4e4f545f414c4c4f574544107c12230a1f56414e4954595f50484f4e455f4e554d4245525f4e4f545f414c4c4f574544107d12230a1f494e56414c49445f43414c4c5f434f4e56455253494f4e5f545950455f4944107e123d0a3943414e4e4f545f44495341424c455f43414c4c5f434f4e56455253494f4e5f414e445f5345545f434f4e56455253494f4e5f545950455f4944107f12230a1e43414e4e4f545f5345545f50415448325f574954484f55545f504154483110800112330a2e4d495353494e475f44594e414d49435f5345415243485f4144535f53455454494e475f444f4d41494e5f4e414d4510810112270a22494e434f4d50415449424c455f574954485f5245535452494354494f4e5f5459504510820112310a2c435553544f4d45525f434f4e53454e545f464f525f43414c4c5f5245434f5244494e475f524551554952454410830112220a1d4d495353494e475f494d4147455f4f525f4d454449415f42554e444c4510840112300a2b50524f445543545f545950455f4e4f545f535550504f525445445f494e5f544849535f43414d504149474e10850112300a2b504c414345484f4c4445525f43414e4e4f545f484156455f454d5054595f44454641554c545f56414c5545108601123d0a38504c414345484f4c4445525f434f554e54444f574e5f46554e4354494f4e5f43414e4e4f545f484156455f44454641554c545f56414c554510870112260a21504c414345484f4c4445525f44454641554c545f56414c55455f4d495353494e4710880112290a24554e45585045435445445f504c414345484f4c4445525f44454641554c545f56414c554510890112270a2241445f435553544f4d495a4552535f4d41595f4e4f545f42455f41444a4143454e54108a01122c0a275550444154494e475f41445f574954485f4e4f5f454e41424c45445f4153534f43494154494f4e108b0142e7010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e6572726f7273420c41644572726f7250726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f6572726f72733b6572726f7273a20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56332e4572726f7273ca021e476f6f676c655c4164735c476f6f676c654164735c56335c4572726f7273ea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a4572726f7273620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

