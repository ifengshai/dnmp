<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v3/common/metrics.proto

namespace GPBMetadata\Google\Ads\GoogleAds\V3\Common;

class Metrics
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
        $pool->internalAddGeneratedFile(hex2bin(
            "0adc030a3a676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f696e746572616374696f6e5f6576656e745f747970652e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732285010a18496e746572616374696f6e4576656e7454797065456e756d22690a14496e746572616374696f6e4576656e7454797065120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112090a05434c49434b1002120e0a0a454e474147454d454e541003120e0a0a564944454f5f56494557100412080a044e4f4e45100542ee010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d734219496e746572616374696f6e4576656e745479706550726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330ad1030a38676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f7175616c6974795f73636f72655f6275636b65742e70726f746f121d676f6f676c652e6164732e676f6f676c656164732e76332e656e756d73227f0a165175616c69747953636f72654275636b6574456e756d22650a125175616c69747953636f72654275636b6574120f0a0b554e5350454349464945441000120b0a07554e4b4e4f574e100112110a0d42454c4f575f415645524147451002120b0a0741564552414745100312110a0d41424f56455f41564552414745100442ec010a21636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d7342175175616c69747953636f72654275636b657450726f746f50015a42676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f656e756d733b656e756d73a20203474141aa021d476f6f676c652e4164732e476f6f676c654164732e56332e456e756d73ca021d476f6f676c655c4164735c476f6f676c654164735c56335c456e756d73ea0221476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a456e756d73620670726f746f330ab83d0a2c676f6f676c652f6164732f676f6f676c656164732f76332f636f6d6d6f6e2f6d6574726963732e70726f746f121e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e1a38676f6f676c652f6164732f676f6f676c656164732f76332f656e756d732f7175616c6974795f73636f72655f6275636b65742e70726f746f1a1e676f6f676c652f70726f746f6275662f77726170706572732e70726f746f1a1c676f6f676c652f6170692f616e6e6f746174696f6e732e70726f746f22fd390a074d65747269637312480a226162736f6c7574655f746f705f696d7072657373696f6e5f70657263656e74616765185f2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512350a0f6163746976655f766965775f63706d18012001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512350a0f6163746976655f766965775f637472184f2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123c0a176163746976655f766965775f696d7072657373696f6e7318022001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565123f0a196163746976655f766965775f6d65617375726162696c69747918602001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512470a226163746976655f766965775f6d656173757261626c655f636f73745f6d6963726f7318032001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512470a226163746976655f766965775f6d656173757261626c655f696d7072657373696f6e7318042001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565123d0a176163746976655f766965775f766965776162696c69747918612001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565124c0a26616c6c5f636f6e76657273696f6e735f66726f6d5f696e746572616374696f6e735f7261746518412001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123b0a15616c6c5f636f6e76657273696f6e735f76616c756518422001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512350a0f616c6c5f636f6e76657273696f6e7318072001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512440a1e616c6c5f636f6e76657273696f6e735f76616c75655f7065725f636f7374183e2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512480a22616c6c5f636f6e76657273696f6e735f66726f6d5f636c69636b5f746f5f63616c6c18762001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512450a1f616c6c5f636f6e76657273696f6e735f66726f6d5f646972656374696f6e7318772001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565125d0a37616c6c5f636f6e76657273696f6e735f66726f6d5f696e746572616374696f6e735f76616c75655f7065725f696e746572616374696f6e18432001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123f0a19616c6c5f636f6e76657273696f6e735f66726f6d5f6d656e7518782001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512400a1a616c6c5f636f6e76657273696f6e735f66726f6d5f6f7264657218792001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565124b0a25616c6c5f636f6e76657273696f6e735f66726f6d5f6f746865725f656e676167656d656e74187a2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512460a20616c6c5f636f6e76657273696f6e735f66726f6d5f73746f72655f7669736974187b2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512480a22616c6c5f636f6e76657273696f6e735f66726f6d5f73746f72655f77656273697465187c2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512320a0c617665726167655f636f737418082001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512310a0b617665726167655f63706318092001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512310a0b617665726167655f63706518622001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512310a0b617665726167655f63706d180a2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512310a0b617665726167655f637076180b2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512380a12617665726167655f706167655f766965777318632001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123a0a14617665726167655f74696d655f6f6e5f7369746518542001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123f0a1962656e63686d61726b5f617665726167655f6d61785f637063180e2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512330a0d62656e63686d61726b5f637472184d2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512310a0b626f756e63655f72617465180f2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565122b0a06636c69636b7318132001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512340a0f636f6d62696e65645f636c69636b7318732001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565123f0a19636f6d62696e65645f636c69636b735f7065725f717565727918742001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512350a10636f6d62696e65645f7175657269657318752001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565124a0a24636f6e74656e745f6275646765745f6c6f73745f696d7072657373696f6e5f736861726518142001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123e0a18636f6e74656e745f696d7072657373696f6e5f736861726518152001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512500a2a636f6e76657273696f6e5f6c6173745f72656365697665645f726571756573745f646174655f74696d6518492001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512450a1f636f6e76657273696f6e5f6c6173745f636f6e76657273696f6e5f64617465184a2001280b321c2e676f6f676c652e70726f746f6275662e537472696e6756616c756512480a22636f6e74656e745f72616e6b5f6c6f73745f696d7072657373696f6e5f736861726518162001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512480a22636f6e76657273696f6e735f66726f6d5f696e746572616374696f6e735f7261746518452001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512370a11636f6e76657273696f6e735f76616c756518462001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512400a1a636f6e76657273696f6e735f76616c75655f7065725f636f737418472001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512590a33636f6e76657273696f6e735f66726f6d5f696e746572616374696f6e735f76616c75655f7065725f696e746572616374696f6e18482001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512310a0b636f6e76657273696f6e7318192001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512300a0b636f73745f6d6963726f73181a2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565123e0a18636f73745f7065725f616c6c5f636f6e76657273696f6e7318442001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512390a13636f73745f7065725f636f6e76657273696f6e181c2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512520a2c636f73745f7065725f63757272656e745f6d6f64656c5f617474726962757465645f636f6e76657273696f6e186a2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123e0a1863726f73735f6465766963655f636f6e76657273696f6e73181d2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512290a03637472181e2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565124a0a2463757272656e745f6d6f64656c5f617474726962757465645f636f6e76657273696f6e7318652001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512610a3b63757272656e745f6d6f64656c5f617474726962757465645f636f6e76657273696f6e735f66726f6d5f696e746572616374696f6e735f7261746518662001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512720a4c63757272656e745f6d6f64656c5f617474726962757465645f636f6e76657273696f6e735f66726f6d5f696e746572616374696f6e735f76616c75655f7065725f696e746572616374696f6e18672001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512500a2a63757272656e745f6d6f64656c5f617474726962757465645f636f6e76657273696f6e735f76616c756518682001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512590a3363757272656e745f6d6f64656c5f617474726962757465645f636f6e76657273696f6e735f76616c75655f7065725f636f737418692001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512350a0f656e676167656d656e745f72617465181f2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512300a0b656e676167656d656e747318202001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512450a1f686f74656c5f617665726167655f6c6561645f76616c75655f6d6963726f73184b2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512480a21686f74656c5f70726963655f646966666572656e63655f70657263656e746167651881012001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512730a21686973746f726963616c5f63726561746976655f7175616c6974795f73636f726518502001280e32482e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e5175616c69747953636f72654275636b6574456e756d2e5175616c69747953636f72654275636b657412770a25686973746f726963616c5f6c616e64696e675f706167655f7175616c6974795f73636f726518512001280e32482e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e5175616c69747953636f72654275636b6574456e756d2e5175616c69747953636f72654275636b6574123d0a18686973746f726963616c5f7175616c6974795f73636f726518522001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512710a1f686973746f726963616c5f7365617263685f7072656469637465645f63747218532001280e32482e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e5175616c69747953636f72654275636b6574456e756d2e5175616c69747953636f72654275636b657412330a0e676d61696c5f666f72776172647318552001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512300a0b676d61696c5f736176657318562001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565123b0a16676d61696c5f7365636f6e646172795f636c69636b7318572001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512410a1c696d7072657373696f6e735f66726f6d5f73746f72655f7265616368187d2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512300a0b696d7072657373696f6e7318252001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512360a10696e746572616374696f6e5f7261746518262001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512310a0c696e746572616374696f6e7318272001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565126d0a17696e746572616374696f6e5f6576656e745f747970657318642003280e324c2e676f6f676c652e6164732e676f6f676c656164732e76332e656e756d732e496e746572616374696f6e4576656e7454797065456e756d2e496e746572616374696f6e4576656e745479706512380a12696e76616c69645f636c69636b5f7261746518282001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512330a0e696e76616c69645f636c69636b7318292001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512320a0d6d6573736167655f6368617473187e2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512380a136d6573736167655f696d7072657373696f6e73187f2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512380a116d6573736167655f636861745f726174651880012001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512470a216d6f62696c655f667269656e646c795f636c69636b735f70657263656e74616765186d2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512330a0e6f7267616e69635f636c69636b73186e2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565123e0a186f7267616e69635f636c69636b735f7065725f7175657279186f2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512380a136f7267616e69635f696d7072657373696f6e7318702001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512430a1d6f7267616e69635f696d7072657373696f6e735f7065725f717565727918712001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512340a0f6f7267616e69635f7175657269657318722001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565123a0a1470657263656e745f6e65775f76697369746f7273182a2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512300a0b70686f6e655f63616c6c73182b2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512360a1170686f6e655f696d7072657373696f6e73182c2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756512380a1270686f6e655f7468726f7567685f72617465182d2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512320a0c72656c61746976655f637472182e2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565124a0a247365617263685f6162736f6c7574655f746f705f696d7072657373696f6e5f7368617265184e2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512560a307365617263685f6275646765745f6c6f73745f6162736f6c7574655f746f705f696d7072657373696f6e5f736861726518582001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512490a237365617263685f6275646765745f6c6f73745f696d7072657373696f6e5f7368617265182f2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565124d0a277365617263685f6275646765745f6c6f73745f746f705f696d7072657373696f6e5f736861726518592001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512380a127365617263685f636c69636b5f736861726518302001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512490a237365617263685f65786163745f6d617463685f696d7072657373696f6e5f736861726518312001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123d0a177365617263685f696d7072657373696f6e5f736861726518322001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512540a2e7365617263685f72616e6b5f6c6f73745f6162736f6c7574655f746f705f696d7072657373696f6e5f7368617265185a2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512470a217365617263685f72616e6b5f6c6f73745f696d7072657373696f6e5f736861726518332001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565124b0a257365617263685f72616e6b5f6c6f73745f746f705f696d7072657373696f6e5f7368617265185b2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512410a1b7365617263685f746f705f696d7072657373696f6e5f7368617265185c2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512300a0b73706565645f73636f7265186b2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565123f0a19746f705f696d7072657373696f6e5f70657263656e74616765185d2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512560a3076616c69645f616363656c6572617465645f6d6f62696c655f70616765735f636c69636b735f70657263656e74616765186c2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123f0a1976616c75655f7065725f616c6c5f636f6e76657273696f6e7318342001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123a0a1476616c75655f7065725f636f6e76657273696f6e18352001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512530a2d76616c75655f7065725f63757272656e745f6d6f64656c5f617474726962757465645f636f6e76657273696f6e185e2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123d0a17766964656f5f7175617274696c655f3130305f7261746518362001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123c0a16766964656f5f7175617274696c655f32355f7261746518372001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123c0a16766964656f5f7175617274696c655f35305f7261746518382001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c7565123c0a16766964656f5f7175617274696c655f37355f7261746518392001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512350a0f766964656f5f766965775f72617465183a2001280b321c2e676f6f676c652e70726f746f6275662e446f75626c6556616c756512300a0b766964656f5f7669657773183b2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c7565123d0a18766965775f7468726f7567685f636f6e76657273696f6e73183c2001280b321b2e676f6f676c652e70726f746f6275662e496e74363456616c756542e7010a22636f6d2e676f6f676c652e6164732e676f6f676c656164732e76332e636f6d6d6f6e420c4d65747269637350726f746f50015a44676f6f676c652e676f6c616e672e6f72672f67656e70726f746f2f676f6f676c65617069732f6164732f676f6f676c656164732f76332f636f6d6d6f6e3b636f6d6d6f6ea20203474141aa021e476f6f676c652e4164732e476f6f676c654164732e56332e436f6d6d6f6eca021e476f6f676c655c4164735c476f6f676c654164735c56335c436f6d6d6f6eea0222476f6f676c653a3a4164733a3a476f6f676c654164733a3a56333a3a436f6d6d6f6e620670726f746f33"
        ), true);
        static::$is_initialized = true;
    }
}

