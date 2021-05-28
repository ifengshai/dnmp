<?php

namespace app\service\google;

use app\enum\GoogleId;
use app\enum\Site;
use app\enum\Store;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsServerStreamDecorator;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V6\Enums\KeywordMatchTypeEnum\KeywordMatchType;
use Google\Ads\GoogleAds\V6\Services\GoogleAdsRow;
use Google\ApiCore\ApiException;

class Ads
{
    protected $_config_file = '../keys/google_ads_php.ini';
    protected $_customer_id;
    protected $_client;

    protected const ADS_CONFIG = [
        Site::ZEELOOL => [
            Store::IOS => GoogleId::ZEELOOL_IOS_GOOGLE_ADS_CUSTOMER_ID,
            Store::ANDROID => GoogleId::ZEELOOL_ANDROID_GOOGLE_ADS_CUSTOMER_ID,
        ],
    ];

    public function __construct($site, $platform = 'web')
    {
        $this->getCustomerId($site, $platform);
    }

    /**
     * 获取 google 的 customer_id
     */
    public function getCustomerId($site, $platform = 'web')
    {
        $this->_customer_id = self::ADS_CONFIG[$site][$platform] ?: null;
        if (!$this->_customer_id) {
            throw new \Exception('unknown config');
        }
        return $this->_customer_id;
    }

    public function getReport($start_time, $end_time)
    {
        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->fromFile($this->_config_file)
            ->build();

        // Construct a Google Ads client configured from a properties file and the OAuth2 credentials above.
        /** @var GoogleAdsClient $googleAdsClient */
        $googleAdsClient = (new GoogleAdsClientBuilder())->fromFile($this->_config_file)
            ->withOAuth2Credential($oAuth2Credential)
            ->build();


        $data = [];
        try {
            $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
            // Creates a query that retrieves all keyword statistics.
            $query =
                "SELECT metrics.cost_micros "
                . "FROM keyword_view "
                . "WHERE segments.date DURING LAST_7_DAYS "
                // Limits to the 50 keywords with the most impressions in the date range.
                . "ORDER BY metrics.impressions DESC "
                . "LIMIT 50";

            // Issues a search stream request.
            /** @var GoogleAdsServerStreamDecorator $stream */
            $stream = $googleAdsServiceClient->search($this->_customer_id, $query);

            // Iterates over all rows in all messages and prints the requested field values for
            // the keyword in each row.
            foreach ($stream->iterateAllElements() as $googleAdsRow) {
                /** @var GoogleAdsRow $googleAdsRow */
                $campaign = $googleAdsRow->getCampaign();
                $adGroup = $googleAdsRow->getAdGroup();
                $adGroupCriterion = $googleAdsRow->getAdGroupCriterion();
                $metrics = $googleAdsRow->getMetrics();

                $data[] = [
                    $adGroupCriterion->getKeyword()->getText(),
                    KeywordMatchType::name($adGroupCriterion->getKeyword()->getMatchType()),
                    $adGroupCriterion->getCriterionId(),
                    $adGroup->getName(),
                    $adGroup->getId(),
                    $campaign->getName(),
                    $campaign->getId(),
                    $metrics->getImpressions(),
                    $metrics->getClicks(),
                    $metrics->getCostMicros(),
                ];
            }
        } catch (GoogleAdsException $googleAdsException) {
            printf(
                "Request with ID '%s' has failed.%sGoogle Ads failure details:%s",
                $googleAdsException->getRequestId(),
                PHP_EOL,
                PHP_EOL
            );
            foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                /** @var GoogleAdsError $error */
                printf(
                    "\t%s: %s%s",
                    $error->getErrorCode()->getErrorCode(),
                    $error->getMessage(),
                    PHP_EOL
                );
            }
            exit(1);
        } catch (ApiException $apiException) {
            printf(
                "ApiException was thrown with message '%s'.%s",
                $apiException->getMessage(),
                PHP_EOL
            );
            exit(1);
        }
        return $data;
    }
}
