<?php

namespace barrelstrength\sproutreportscommerce\integrations\sproutreports\datasources;

use barrelstrength\sproutbasereports\base\DataSource;
use barrelstrength\sproutbasereports\elements\Report;
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
    public function getName(): string
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
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
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

        return Craft::$app->getView()->renderTemplate('sprout-reports-commerce/datasources/productrevenue/_settings', [
            'settings' => $settings,
            'defaultStartDate' => new \DateTime($defaultStartDate),
            'defaultEndDate' => new \DateTime($defaultEndDate)
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

        $startDate = DateTimeHelper::toDateTime($reportModel->getSetting('startDate'));
        $endDate   = DateTimeHelper::toDateTime($reportModel->getSetting('endDate'));

        // First, use dynamic options, fallback to report options
        if ($settings !== null) {
            $options = $report->getSettings();
            $displayVariants = $options['variants'];
        }


/*        @todo find out why we are querying criteria here
        $criteria = $criteria = craft()->elements->getCriteria('Commerce_Order');
        $criteria->limit = null;

        // Don't use the search
        $criteria->search = null;*/
        $query = new Query();
        $query->select('variants.id as \'Variants ID\', 
                              products.id as \'Product ID\',
                              orders.id as \'Order ID\',
                              FORMAT(SUM(lineitems.total), 2) as \'Line Item Revenue\',
                              SUM(lineitems.discount) as \'Line Item Discount\',
                              SUM(lineitems.shippingCost) as \'Line Item Shipping Cost\',
                              SUM(lineitems.taxIncluded) as \'Line Item Tax Included\',
                              SUM(lineitems.tax) as \'Line Item Tax\',
                              FORMAT(SUM(lineitems.salePrice * lineitems.qty), 2) as \'Product Revenue\',
                              SUM(lineitems.qty) as \'Quantity Sold\',
                              variants.sku as SKU')
            ->from('{{%commerce_orders}} as orders')
            ->leftJoin('{{%commerce_lineitems}} as lineitems', 'orders.id = lineitems.orderId')
            ->leftJoin('{{%commerce_variants}} as variants', 'lineitems.purchasableId = variants.id')
            ->leftJoin('{{%commerce_products}} as products', 'variants.productId = products.id');

        if ($startDate && $endDate) {
            $query->andWhere('orders.dateOrdered > :startDate', [':startDate' => $startDate->format('Y-m-d H:i:s')]);
            $query->andWhere('orders.dateOrdered < :endDate', [':endDate' => $endDate->format('Y-m-d H:i:s')]);
        }

        if (!empty($displayVariants)) {
            $query->groupBy('lineitems.purchasableId');
        } else {
            $query->groupBy('products.id');
        }

        $query->orderBy(['products.id' => SORT_DESC]);

        $results = $query->all();

        if ($results) {
            foreach ($results as $key => $result) {
                $productId = $result['Product ID'];
                $variantId = $result['Variants ID'];
                /**
                 * @var $productElement Product
                 */
                $productElement = Craft::$app->elements->getElementById($productId);

                if (!empty($displayVariants)) {
                    /**
                     * @var $variantElement Variant
                     */
                    $variantElement = Craft::$app->elements->getElementById($variantId);

                    if ($variantElement) {
                        $results[$key]['Variant Title'] = $variantElement->title;
                    } else {
                        $results[$key]['Variant Title'] = Craft::t('sprout-reports-commerce', 'Variant has been deleted');
                    }
                }

                if ($productElement) {
                    $results[$key]['Product Title'] = $productElement->title;
                } else {
                    $results[$key]['Product Title'] = Craft::t('sprout-reports-commerce','Product has been deleted');
                }

                // Do not display IDs
                unset($results[$key]['Product ID']);
                unset($results[$key]['Variants ID']);
                unset($results[$key]['Order ID']);

                $results[$key] = array_reverse($results[$key], true);
            }
        }

        return $results;
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
