<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/enums/criterion_category_locale_availability_mode.proto

namespace Google\Ads\GoogleAds\V5\Enums\CriterionCategoryLocaleAvailabilityModeEnum;

use UnexpectedValueException;

/**
 * Enum containing the possible CriterionCategoryLocaleAvailabilityMode.
 *
 * Protobuf type <code>google.ads.googleads.v5.enums.CriterionCategoryLocaleAvailabilityModeEnum.CriterionCategoryLocaleAvailabilityMode</code>
 */
class CriterionCategoryLocaleAvailabilityMode
{
    /**
     * Not specified.
     *
     * Generated from protobuf enum <code>UNSPECIFIED = 0;</code>
     */
    const UNSPECIFIED = 0;
    /**
     * Used for return value only. Represents value unknown in this version.
     *
     * Generated from protobuf enum <code>UNKNOWN = 1;</code>
     */
    const UNKNOWN = 1;
    /**
     * The category is available to campaigns of all locales.
     *
     * Generated from protobuf enum <code>ALL_LOCALES = 2;</code>
     */
    const ALL_LOCALES = 2;
    /**
     * The category is available to campaigns within a list of countries,
     * regardless of language.
     *
     * Generated from protobuf enum <code>COUNTRY_AND_ALL_LANGUAGES = 3;</code>
     */
    const COUNTRY_AND_ALL_LANGUAGES = 3;
    /**
     * The category is available to campaigns within a list of languages,
     * regardless of country.
     *
     * Generated from protobuf enum <code>LANGUAGE_AND_ALL_COUNTRIES = 4;</code>
     */
    const LANGUAGE_AND_ALL_COUNTRIES = 4;
    /**
     * The category is available to campaigns within a list of country, language
     * pairs.
     *
     * Generated from protobuf enum <code>COUNTRY_AND_LANGUAGE = 5;</code>
     */
    const COUNTRY_AND_LANGUAGE = 5;

    private static $valueToName = [
        self::UNSPECIFIED => 'UNSPECIFIED',
        self::UNKNOWN => 'UNKNOWN',
        self::ALL_LOCALES => 'ALL_LOCALES',
        self::COUNTRY_AND_ALL_LANGUAGES => 'COUNTRY_AND_ALL_LANGUAGES',
        self::LANGUAGE_AND_ALL_COUNTRIES => 'LANGUAGE_AND_ALL_COUNTRIES',
        self::COUNTRY_AND_LANGUAGE => 'COUNTRY_AND_LANGUAGE',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CriterionCategoryLocaleAvailabilityMode::class, \Google\Ads\GoogleAds\V5\Enums\CriterionCategoryLocaleAvailabilityModeEnum_CriterionCategoryLocaleAvailabilityMode::class);

