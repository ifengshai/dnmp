<?php

namespace app\service\google;

use app\enum\GoogleId;
use app\enum\Site;
use app\enum\Store;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;


class AnalyticsData
{
    protected $_credential_file = '../keys/GA-Zeelool-app-46cb261d35b2.json';
    protected $_property_id;
    protected $_client;

    protected const GA4_CONFIG = [
        Site::ZEELOOL => [
            Store::IOS => GoogleId::ZEELOOL_IOS_GOOGLE_ANALYTICS_PROPERTY_ID,
            Store::ANDROID => GoogleId::ZEELOOL_ANDROID_GOOGLE_ANALYTICS_PROPERTY_ID,
        ],
        Site::VOOGUEME => [
            Store::IOS => GoogleId::VOOGUEME_IOS_GOOGLE_ANALYTICS_PROPERTY_ID,
            Store::ANDROID => GoogleId::VOOGUEME_ANDROID_GOOGLE_ANALYTICS_PROPERTY_ID,
        ]
    ];

    public function __construct($site, $platform = 'web')
    {
        $this->getConfig($site, $platform);
    }

    /**
     * 获取 google 的 property_id
     */
    public function getConfig($site, $platform = 'web')
    {
        $this->_property_id = self::GA4_CONFIG[$site][$platform] ?: null;
        if (!$this->_property_id) {
            throw new \Exception('unknown config');
        }
    }

    public function getClient()
    {
        if (!$this->_client instanceof BetaAnalyticsDataClient) {
            $this->_client = new BetaAnalyticsDataClient([
                'credentials' => $this->_credential_file,
                'credentialsConfig' => [
                    'scopes' => BetaAnalyticsDataClient::$serviceScopes,
                ],
            ]);
        }
        return $this->_client;
    }

    /**
     * 获取谷歌统计报告
     *
     * @param $start_time
     * @param $end_time
     * @param $metric_expressions
     * @param $dimension_names
     * @return array
     */
    public function getReport($start_time, $end_time, $metric_expressions, $dimension_names)
    {
        $client = $this->getClient();

        $date_range = new DateRange([
            'start_date' => $start_time,
            'end_date' => $end_time,
        ]);

        // Create the Metrics objects.
        // https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema#metrics
        $metrics = [];
        foreach ($metric_expressions as $metric_expression) {
            $metrics[] = new Metric([
                'name' => $metric_expression,
            ]);
        }

        // Create the Dimensions objects.
        // https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema#dimensions
        $dimensions = [];
        foreach ($dimension_names as $dimension_name) {
            $dimensions[] = new Dimension([
                'name' => $dimension_name,
            ]);
        }

        $response = $client->runReport([
            'property' => 'properties/' . $this->_property_id,
            'dateRanges' => [$date_range],
            'dimensions' => $dimensions,
            'metrics' => $metrics,
        ]);

        return $this->printResults($dimensions, $metrics, $response);
    }

    /**
     * 格式化数据
     *
     * @param $dimensions
     * @param $metrics
     * @param $response
     * @return array
     */
    protected function printResults($dimensions, $metrics, $response)
    {
        $result = [];
        foreach ($response->getRows() as $row) {
            $columns = [];
            foreach ($row->getMetricValues() as $index => $metricValue) {
                $columns[$metrics[$index]->getName()] = $metricValue->getValue();
            }
            $row = array_reduce(array_reverse(iterator_to_array($row->getDimensionValues())), function ($carry, $item) {
                return [$item->getValue() => $carry];
            }, $columns);
            $result = static::mergeArray($result, $row);
        }
        return $result;
    }

    /**
     * 递归地合并数组并保留键名
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    protected static function mergeArray($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::mergeArray($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }
        return $res;
    }
}
