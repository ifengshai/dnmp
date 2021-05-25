<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/analytics/data/v1beta/data.proto

namespace Google\Analytics\Data\V1beta;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Describes the visible dimension columns and rows in the report response.
 *
 * Generated from protobuf message <code>google.analytics.data.v1beta.Pivot</code>
 */
class Pivot extends \Google\Protobuf\Internal\Message
{
    /**
     * Dimension names for visible columns in the report response. Including
     * "dateRange" produces a date range column; for each row in the response,
     * dimension values in the date range column will indicate the corresponding
     * date range from the request.
     *
     * Generated from protobuf field <code>repeated string field_names = 1;</code>
     */
    private $field_names;
    /**
     * Specifies how dimensions are ordered in the pivot. In the first Pivot, the
     * OrderBys determine Row and PivotDimensionHeader ordering; in subsequent
     * Pivots, the OrderBys determine only PivotDimensionHeader ordering.
     * Dimensions specified in these OrderBys must be a subset of
     * Pivot.field_names.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1beta.OrderBy order_bys = 2;</code>
     */
    private $order_bys;
    /**
     * The row count of the start row. The first row is counted as row 0.
     *
     * Generated from protobuf field <code>int64 offset = 3;</code>
     */
    private $offset = 0;
    /**
     * The number of unique combinations of dimension values to return in this
     * pivot. If unspecified, up to 10,000 unique combinations of dimension values
     * are returned. `limit` must be positive.
     * The product of the `limit` for each `pivot` in a `RunPivotReportRequest`
     * must not exceed 100,000. For example, a two pivot request with `limit:
     * 1000` in each pivot will fail because the product is `1,000,000`.
     *
     * Generated from protobuf field <code>int64 limit = 4;</code>
     */
    private $limit = 0;
    /**
     * Aggregate the metrics by dimensions in this pivot using the specified
     * metric_aggregations.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1beta.MetricAggregation metric_aggregations = 5;</code>
     */
    private $metric_aggregations;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $field_names
     *           Dimension names for visible columns in the report response. Including
     *           "dateRange" produces a date range column; for each row in the response,
     *           dimension values in the date range column will indicate the corresponding
     *           date range from the request.
     *     @type \Google\Analytics\Data\V1beta\OrderBy[]|\Google\Protobuf\Internal\RepeatedField $order_bys
     *           Specifies how dimensions are ordered in the pivot. In the first Pivot, the
     *           OrderBys determine Row and PivotDimensionHeader ordering; in subsequent
     *           Pivots, the OrderBys determine only PivotDimensionHeader ordering.
     *           Dimensions specified in these OrderBys must be a subset of
     *           Pivot.field_names.
     *     @type int|string $offset
     *           The row count of the start row. The first row is counted as row 0.
     *     @type int|string $limit
     *           The number of unique combinations of dimension values to return in this
     *           pivot. If unspecified, up to 10,000 unique combinations of dimension values
     *           are returned. `limit` must be positive.
     *           The product of the `limit` for each `pivot` in a `RunPivotReportRequest`
     *           must not exceed 100,000. For example, a two pivot request with `limit:
     *           1000` in each pivot will fail because the product is `1,000,000`.
     *     @type int[]|\Google\Protobuf\Internal\RepeatedField $metric_aggregations
     *           Aggregate the metrics by dimensions in this pivot using the specified
     *           metric_aggregations.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Google\Analytics\Data\V1Beta\Data::initOnce();
        parent::__construct($data);
    }

    /**
     * Dimension names for visible columns in the report response. Including
     * "dateRange" produces a date range column; for each row in the response,
     * dimension values in the date range column will indicate the corresponding
     * date range from the request.
     *
     * Generated from protobuf field <code>repeated string field_names = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getFieldNames()
    {
        return $this->field_names;
    }

    /**
     * Dimension names for visible columns in the report response. Including
     * "dateRange" produces a date range column; for each row in the response,
     * dimension values in the date range column will indicate the corresponding
     * date range from the request.
     *
     * Generated from protobuf field <code>repeated string field_names = 1;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setFieldNames($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->field_names = $arr;

        return $this;
    }

    /**
     * Specifies how dimensions are ordered in the pivot. In the first Pivot, the
     * OrderBys determine Row and PivotDimensionHeader ordering; in subsequent
     * Pivots, the OrderBys determine only PivotDimensionHeader ordering.
     * Dimensions specified in these OrderBys must be a subset of
     * Pivot.field_names.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1beta.OrderBy order_bys = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getOrderBys()
    {
        return $this->order_bys;
    }

    /**
     * Specifies how dimensions are ordered in the pivot. In the first Pivot, the
     * OrderBys determine Row and PivotDimensionHeader ordering; in subsequent
     * Pivots, the OrderBys determine only PivotDimensionHeader ordering.
     * Dimensions specified in these OrderBys must be a subset of
     * Pivot.field_names.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1beta.OrderBy order_bys = 2;</code>
     * @param \Google\Analytics\Data\V1beta\OrderBy[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setOrderBys($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Google\Analytics\Data\V1beta\OrderBy::class);
        $this->order_bys = $arr;

        return $this;
    }

    /**
     * The row count of the start row. The first row is counted as row 0.
     *
     * Generated from protobuf field <code>int64 offset = 3;</code>
     * @return int|string
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * The row count of the start row. The first row is counted as row 0.
     *
     * Generated from protobuf field <code>int64 offset = 3;</code>
     * @param int|string $var
     * @return $this
     */
    public function setOffset($var)
    {
        GPBUtil::checkInt64($var);
        $this->offset = $var;

        return $this;
    }

    /**
     * The number of unique combinations of dimension values to return in this
     * pivot. If unspecified, up to 10,000 unique combinations of dimension values
     * are returned. `limit` must be positive.
     * The product of the `limit` for each `pivot` in a `RunPivotReportRequest`
     * must not exceed 100,000. For example, a two pivot request with `limit:
     * 1000` in each pivot will fail because the product is `1,000,000`.
     *
     * Generated from protobuf field <code>int64 limit = 4;</code>
     * @return int|string
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * The number of unique combinations of dimension values to return in this
     * pivot. If unspecified, up to 10,000 unique combinations of dimension values
     * are returned. `limit` must be positive.
     * The product of the `limit` for each `pivot` in a `RunPivotReportRequest`
     * must not exceed 100,000. For example, a two pivot request with `limit:
     * 1000` in each pivot will fail because the product is `1,000,000`.
     *
     * Generated from protobuf field <code>int64 limit = 4;</code>
     * @param int|string $var
     * @return $this
     */
    public function setLimit($var)
    {
        GPBUtil::checkInt64($var);
        $this->limit = $var;

        return $this;
    }

    /**
     * Aggregate the metrics by dimensions in this pivot using the specified
     * metric_aggregations.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1beta.MetricAggregation metric_aggregations = 5;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMetricAggregations()
    {
        return $this->metric_aggregations;
    }

    /**
     * Aggregate the metrics by dimensions in this pivot using the specified
     * metric_aggregations.
     *
     * Generated from protobuf field <code>repeated .google.analytics.data.v1beta.MetricAggregation metric_aggregations = 5;</code>
     * @param int[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMetricAggregations($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::ENUM, \Google\Analytics\Data\V1beta\MetricAggregation::class);
        $this->metric_aggregations = $arr;

        return $this;
    }

}

