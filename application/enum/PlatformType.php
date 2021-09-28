<?php
/**
 * Class PlatformType.php
 * @package app\enum
 * @author  jhh
 * @date    2021/4/22 15:06
 */

namespace app\enum;


/**
 * 站点烈性
 * Class PlatformType
 * @package app\enum
 * @author  jhh
 * @date    2021/4/22 15:18
 */
class PlatformType
{
    //zeelool
    const ZEELOOL_PLAT = 1;
    //voogueme
    const VOOGUEME_PLAT = 2;
    //nihao
    const NIHAO_PLAT = 3;
    //meeloog
    const MEELOOG_PLAT = 4;
    //wesee
    const WESEE_PLAT = 5;
    //amazon
    const AMAZON_PLAT = 8;
    //zeelool_es
    const ZEELOOL_ES_PLAT = 9;
    //zeelool_de
    const ZEELOOL_DE_PLAT = 10;
    //zeelool_jp
    const ZEELOOL_JP_PLAT = 11;
    //voogmechic
    const VOOGMECHIC_PLAT = 12;
    //zeelool_cn
    const ZEELOOL_CN_PLAT = 13;
    //alibaba
    const ALIBABA_PLAT = 14;
    //zeelool_fr
    const ZEELOOL_FR_PLAT = 15;

    //zeelool
    const ZEELOOL = 'zeelool';
    //voogueme
    const VOOGUEME = 'voogueme';
    //nihao
    const NIHAO = 'nihao';
    //meeloog
    const MEELOOG = 'meeloog';
    //wesee
    const WESEE = 'wesee';
    //amazon
    const AMAZON = 'amazon';
    //zeelool_es
    const ZEELOOL_ES = 'zeelool_es';
    //zeelool_de
    const ZEELOOL_DE = 'zeelool_de';
    //zeelool_jp
    const ZEELOOL_JP = 'zeelool_jp';
    //voogmechic
    const VOOGMECHIC = 'voogmechic';
    //zeelool_cn
    const ZEELOOL_CN = 'zeelool_cn';
    //alibaba
    const ALIBABA = 'alibaba';
    //zeelool_fr
    const ZEELOOL_FR = 'zeelool_fr';

    public static function getNameById($id)
    {
        switch ($id) {
            case self::ZEELOOL_PLAT:
                return self::ZEELOOL;
            case self::VOOGUEME_PLAT:
                return self::VOOGUEME;
            case self::NIHAO_PLAT:
                return self::NIHAO;
            case self::MEELOOG_PLAT:
                return self::MEELOOG;
            case self::WESEE_PLAT:
                return self::WESEE;
            case self::AMAZON_PLAT:
                return self::AMAZON;
            case self::ZEELOOL_ES_PLAT:
                return self::ZEELOOL_ES;
            case self::ZEELOOL_DE_PLAT:
                return self::ZEELOOL_DE;
            case self::ZEELOOL_JP_PLAT:
                return self::ZEELOOL_JP;
            case self::VOOGMECHIC_PLAT:
                return self::VOOGMECHIC;
            case self::ZEELOOL_CN_PLAT:
                return self::ZEELOOL_CN;
            case self::ALIBABA_PLAT:
                return self::ALIBABA;
            case self::ZEELOOL_FR_PLAT:
                return self::ZEELOOL_FR;
            default:
                return 'Unknown';
        }
    }

}
