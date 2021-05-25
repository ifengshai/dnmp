<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1alpha/data.proto

namespace Google\Analytics\Data\V1alpha;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Explains a dimension.
 *
 * Generated from protobuf message <code>google.analytics.data.v1alpha.DimensionMetadata</code>
 */
class DimensionMetadata extends \Google\Protobuf\Internal\Message
{
    /**
     * This dimension's name. Useable in [Dimension](#Dimension)'s `name`. For
     * example, `eventName`.
     *
     * Generated from protobuf field <code>string api_name = 1;</code>
     */
    private $api_name = '';
    /**
     * This dimension's name within the Google Analytics user interface. For
     * example, `Event name`.
     *
     * Generated from protobuf field <code>string ui_name = 2;</code>
     */
    private $ui_name = '';
    /**
     * Description of how this dimension is used and calculated.
     *
     * Generated from protobuf field <code>string description = 3;</code>
     */
    private $description = '';
    /**
     * Still usable but deprecated names for this dimension. If populated, this
     * dimension is available by either `apiName` or one of `deprecatedApiNames`
     * for a period of time. After the deprecation period, the dimension will be
     * available only by `apiName`.
     *
     * Generated from protobuf field <code>repeated string deprecated_api_names = 4;</code>
     */
    private $deprecated_api_names;
    /**
     * True if the dimension is a custom dimension for this property.
     *
     * Generated from protobuf field <code>bool custom_definition = 5;</code>
     */
    private $custom_definition = false;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $api_name
     *           This dimension's name. Useable in [Dimension](#Dimension)'s `name`. For
     *           example, `eventName`.
     *     @type string $ui_name
     *           This dimension's name within the Google Analytics user interface. For
     *           example, `Event name`.
     *     @type string $description
     *           Description of how this dimension is used and calculated.
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $deprecated_api_names
     *           Still usable but deprecated names for this dimension. If populated, this
     *           dimension is available by either `apiName` or one of `deprecatedApiNames`
     *           for a period of time. After the deprecation period, the dimension will be
     *           available only by `apiName`.
     *     @type bool $custom_definition
     *           True if the dimension is a custom dimension for this property.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Analytics\Data\V1Alpha\Data::initOnce();
        parent::__construct($data);
    }

    /**
     * This dimension's name. Useable in [Dimension](#Dimension)'s `name`. For
     * example, `eventName`.
     *
     * Generated from protobuf field <code>string api_name = 1;</code>
     * @return string
     */
    public function getApiName()
    {
        return $this->api_name;
    }

    /**
     * This dimension's name. Useable in [Dimension](#Dimension)'s `name`. For
     * example, `eventName`.
     *
     * Generated from protobuf field <code>string api_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setApiName($var)
    {
        GPBUtil::checkString($var, True);
        $this->api_name = $var;

        return $this;
    }

    /**
     * This dimension's name within the Google Analytics user interface. For
     * example, `Event name`.
     *
     * Generated from protobuf field <code>string ui_name = 2;</code>
     * @return string
     */
    public function getUiName()
    {
        return $this->ui_name;
    }

    /**
     * This dimension's name within the Google Analytics user interface. For
     * example, `Event name`.
     *
     * Generated from protobuf field <code>string ui_name = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setUiName($var)
    {
        GPBUtil::checkString($var, True);
        $this->ui_name = $var;

        return $this;
    }

    /**
     * Description of how this dimension is used and calculated.
     *
     * Generated from protobuf field <code>string description = 3;</code>
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Description of how this dimension is used and calculated.
     *
     * Generated from protobuf field <code>string description = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setDescription($var)
    {
        GPBUtil::checkString($var, True);
        $this->description = $var;

        return $this;
    }

    /**
     * Still usable but deprecated names for this dimension. If populated, this
     * dimension is available by either `apiName` or one of `deprecatedApiNames`
     * for a period of time. After the deprecation period, the dimension will be
     * available only by `apiName`.
     *
     * Generated from protobuf field <code>repeated string deprecated_api_names = 4;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getDeprecatedApiNames()
    {
        return $this->deprecated_api_names;
    }

    /**
     * Still usable but deprecated names for this dimension. If populated, this
     * dimension is available by either `apiName` or one of `deprecatedApiNames`
     * for a period of time. After the deprecation period, the dimension will be
     * available only by `apiName`.
     *
     * Generated from protobuf field <code>repeated string deprecated_api_names = 4;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setDeprecatedApiNames($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->deprecated_api_names = $arr;

        return $this;
    }

    /**
     * True if the dimension is a custom dimension for this property.
     *
     * Generated from protobuf field <code>bool custom_definition = 5;</code>
     * @return bool
     */
    public function getCustomDefinition()
    {
        return $this->custom_definition;
    }

    /**
     * True if the dimension is a custom dimension for this property.
     *
     * Generated from protobuf field <code>bool custom_definition = 5;</code>
     * @param bool $var
     * @return $this
     */
    public function setCustomDefinition($var)
    {
        GPBUtil::checkBool($var);
        $this->custom_definition = $var;

        return $this;
    }

}

