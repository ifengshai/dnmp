<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/ads/googleads/v5/common/user_lists.proto

namespace Google\Ads\GoogleAds\V5\Common;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * A rule item composed of a date operation.
 *
 * Generated from protobuf message <code>google.ads.googleads.v5.common.UserListDateRuleItemInfo</code>
 */
class UserListDateRuleItemInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * Date comparison operator.
     * This field is required and must be populated when creating new date
     * rule item.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.UserListDateRuleItemOperatorEnum.UserListDateRuleItemOperator operator = 1;</code>
     */
    protected $operator = 0;
    /**
     * String representing date value to be compared with the rule variable.
     * Supported date format is YYYY-MM-DD.
     * Times are reported in the customer's time zone.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue value = 2;</code>
     */
    protected $value = null;
    /**
     * The relative date value of the right hand side denoted by number of days
     * offset from now. The value field will override this field when both are
     * present.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value offset_in_days = 3;</code>
     */
    protected $offset_in_days = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $operator
     *           Date comparison operator.
     *           This field is required and must be populated when creating new date
     *           rule item.
     *     @type \Google\Protobuf\StringValue $value
     *           String representing date value to be compared with the rule variable.
     *           Supported date format is YYYY-MM-DD.
     *           Times are reported in the customer's time zone.
     *     @type \Google\Protobuf\Int64Value $offset_in_days
     *           The relative date value of the right hand side denoted by number of days
     *           offset from now. The value field will override this field when both are
     *           present.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Ads\GoogleAds\V5\Common\UserLists::initOnce();
        parent::__construct($data);
    }

    /**
     * Date comparison operator.
     * This field is required and must be populated when creating new date
     * rule item.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.UserListDateRuleItemOperatorEnum.UserListDateRuleItemOperator operator = 1;</code>
     * @return int
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Date comparison operator.
     * This field is required and must be populated when creating new date
     * rule item.
     *
     * Generated from protobuf field <code>.google.ads.googleads.v5.enums.UserListDateRuleItemOperatorEnum.UserListDateRuleItemOperator operator = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setOperator($var)
    {
        GPBUtil::checkEnum($var, \Google\Ads\GoogleAds\V5\Enums\UserListDateRuleItemOperatorEnum\UserListDateRuleItemOperator::class);
        $this->operator = $var;

        return $this;
    }

    /**
     * String representing date value to be compared with the rule variable.
     * Supported date format is YYYY-MM-DD.
     * Times are reported in the customer's time zone.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue value = 2;</code>
     * @return \Google\Protobuf\StringValue
     */
    public function getValue()
    {
        return isset($this->value) ? $this->value : null;
    }

    public function hasValue()
    {
        return isset($this->value);
    }

    public function clearValue()
    {
        unset($this->value);
    }

    /**
     * Returns the unboxed value from <code>getValue()</code>

     * String representing date value to be compared with the rule variable.
     * Supported date format is YYYY-MM-DD.
     * Times are reported in the customer's time zone.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue value = 2;</code>
     * @return string|null
     */
    public function getValueUnwrapped()
    {
        return $this->readWrapperValue("value");
    }

    /**
     * String representing date value to be compared with the rule variable.
     * Supported date format is YYYY-MM-DD.
     * Times are reported in the customer's time zone.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue value = 2;</code>
     * @param \Google\Protobuf\StringValue $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\StringValue::class);
        $this->value = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\StringValue object.

     * String representing date value to be compared with the rule variable.
     * Supported date format is YYYY-MM-DD.
     * Times are reported in the customer's time zone.
     *
     * Generated from protobuf field <code>.google.protobuf.StringValue value = 2;</code>
     * @param string|null $var
     * @return $this
     */
    public function setValueUnwrapped($var)
    {
        $this->writeWrapperValue("value", $var);
        return $this;}

    /**
     * The relative date value of the right hand side denoted by number of days
     * offset from now. The value field will override this field when both are
     * present.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value offset_in_days = 3;</code>
     * @return \Google\Protobuf\Int64Value
     */
    public function getOffsetInDays()
    {
        return isset($this->offset_in_days) ? $this->offset_in_days : null;
    }

    public function hasOffsetInDays()
    {
        return isset($this->offset_in_days);
    }

    public function clearOffsetInDays()
    {
        unset($this->offset_in_days);
    }

    /**
     * Returns the unboxed value from <code>getOffsetInDays()</code>

     * The relative date value of the right hand side denoted by number of days
     * offset from now. The value field will override this field when both are
     * present.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value offset_in_days = 3;</code>
     * @return int|string|null
     */
    public function getOffsetInDaysUnwrapped()
    {
        return $this->readWrapperValue("offset_in_days");
    }

    /**
     * The relative date value of the right hand side denoted by number of days
     * offset from now. The value field will override this field when both are
     * present.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value offset_in_days = 3;</code>
     * @param \Google\Protobuf\Int64Value $var
     * @return $this
     */
    public function setOffsetInDays($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Int64Value::class);
        $this->offset_in_days = $var;

        return $this;
    }

    /**
     * Sets the field by wrapping a primitive type in a Google\Protobuf\Int64Value object.

     * The relative date value of the right hand side denoted by number of days
     * offset from now. The value field will override this field when both are
     * present.
     *
     * Generated from protobuf field <code>.google.protobuf.Int64Value offset_in_days = 3;</code>
     * @param int|string|null $var
     * @return $this
     */
    public function setOffsetInDaysUnwrapped($var)
    {
        $this->writeWrapperValue("offset_in_days", $var);
        return $this;}

}

