<?php

namespace barrelstrength\sproutreportscommerce\integrations\sproutreports\datasources;

use barrelstrength\sproutbasereports\base\DataSource;
use barrelstrength\sproutbasereports\elements\Report;
use barrelstrength\sproutbasereports\SproutBaseReports;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\helpers\DateTimeHelper;
use craft\db\Query;
use Craft;

class CommerceProductRevenueDataSource extends DataSource
{
    /**
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('sprout-reports-commerce','Commerce Product Revenue');
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return Craft::t('sprout-reports-commerce','Create sales reports for your products and variants.');
    }

    /**
     * @param array $options
     *
     * @return null|string
     * @throws \Exception
     */
    public function getSettingsHtml(array $options = [])
    {
        $defaultStartDate = null;
        $defaultEndDate = null;

        /**
         * @var $report Report
         */
        $report = $this->report;

        $settings = $report->getSettings();

        if ($settings !== null) {
            if (isset($settings['startDate'])) {
                $startDateValue = (array)$settings['startDate'];

                $settings['startDate'] = DateTimeHelper::toDateTime($startDateValue);
            }

            if (isset($settings['endDate'])) {
                $endDateValue = (array)$settings['endDate'];

                $settings['endDate'] = DateTimeHelper::toDateTime($endDateValue);
            }
        }

        $dateRanges = SproutBaseReports::$app->reports->getDateRanges();

        return Craft::$app->getView()->renderTemplate('sprout-reports-commerce/datasources/productrevenue/_settings', [
            'settings' => $settings,
            'defaultStartDate' => new \DateTime($defaultStartDate),
            'defaultEndDate' => new \DateTime($defaultEndDate),
            'dateRanges' => $dateRanges
        ]);
    }

    /**
     *
     * Gets the results for the Order History Report
     * @param Report $report
     * @param array  $settings
     *
     * @return array
     * @throws \Exception
     */
    public function getResults(Report $report, array $settings = []): array
    {
        $displayVariants = false;
        /**
         * @var $reportModel Report
         */
        $reportModel = $this->report;

        $startEndDate = $reportModel->getStartEndDate();

        $startDate = $startEndDate->getStartDate();
        $endDate   = $startEndDate->getEndDate();
        // First, use dynamic options, fallback to report options
        if ($settings !== null) {
            $options = $report->getSettings();
            $displayVariants = $options['variants'];
        }

        $query = new Query();
        $query->select('
            [[variants.id]] as variantId,
            [[products.id]] as productId,
            SUM([[lineitems.total]]) as total,
            SUM([[lineitems.saleAmount]]) as saleAmount,
            SUM([[lineitems.salePrice]] * [[lineitems.qty]]) as productRevenue,
            SUM([[lineitems.qty]]) as quantitySold,
            [[variants.sku]] as SKU')
            ->from('{{%commerce_orders}} as orders')
            ->leftJoin('{{%commerce_lineitems}} as lineitems', '[[orders.id]] = [[lineitems.orderId]]')
            ->leftJoin('{{%commerce_variants}} as variants', '[[lineitems.purchasableId]] = [[variants.id]]')
            ->leftJoin('{{%commerce_products}} as products', '[[variants.productId]] = [[products.id]]');

        if ($startDate && $endDate) {
            $query->andWhere(['>=', '[[orders.dateOrdered]]', $startDate->format('Y-m-d H:i:s')]);
            $query->andWhere(['<=', '[[orders.dateOrdered]]', $endDate->format('Y-m-d H:i:s')]);
        }

        $query->groupBy('variantId');

        $query->orderBy(['[[products.id]]' => SORT_DESC]);

        $results = $query->all();

        $rows = [];
        if ($results) {
            foreach ($results as $key => $result) {
//                if ($result['productId'] === null) {
//                    continue;
//                }
                #$lineItemId = $result['lineItemId'];
                #$lineItem = \craft\commerce\Plugin::getInstance()->lineItems->getLineItemById($lineItemId);

                $productId = $result['productId'];
                $variantId = $result['variantId'];
                $rows[$key]['Variant ID'] = $variantId ?? '–';
                $rows[$key]['Product ID'] = $productId ?? '–';
                $rows[$key]['Line Item Revenue'] = number_format($result['total'], 2);
                $rows[$key]['Sale Amount'] = number_format($result['saleAmount'], 2);
                #$rows[$key]['Shipping Cost'] = number_format($lineItem->getAdjustmentsTotalByType('shipping'), 2);
                #$rows[$key]['Tax'] = number_format($lineItem->getAdjustmentsTotalByType('tax'), 2);
                $rows[$key]['Product Revenue'] = number_format($result['productRevenue'], 2);
                $rows[$key]['Quantity Sold'] = $result['quantitySold'];
                $rows[$key]['SKU'] = $result['SKU'] ?? '–';

                /**@var $productElement Product */
                $productElement = $productId ? Craft::$app->elements->getElementById($productId) : null;

                if (!empty($displayVariants)) {
                    /**
                     * @var $variantElement Variant
                     */
                    $variantElement = $variantId ? Craft::$app->elements->getElementById($variantId) : null;

                    if ($variantElement) {
                        $rows[$key]['Variant Title'] = $variantElement->title;
                    } else {
                        $rows[$key]['Variant Title'] = Craft::t('sprout-reports-commerce', 'Variant has been deleted');
                    }
                }

                if ($productElement) {
                    $rows[$key]['Product Title'] = $productElement->title;
                } else {
                    $rows[$key]['Product Title'] = Craft::t('sprout-reports-commerce','Product has been deleted');
                }

               $rows[$key] = array_reverse($rows[$key], true);
            }
        }

        return $rows;
    }

    /**
     * @param array $options
     *
     * @return array
     * @throws \Exception
     */
    public function prepOptions(array $options)
    {
        $options['startDate'] = DateTimeHelper::toDateTime($options['startDate']);
        $options['endDate'] = DateTimeHelper::toDateTime($options['endDate']);

        return $options;
    }
}
