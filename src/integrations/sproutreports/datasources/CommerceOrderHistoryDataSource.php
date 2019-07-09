<?php

namespace barrelstrength\sproutreportscommerce\integrations\sproutreports\datasources;

use barrelstrength\sproutbasereports\base\DataSource;
use barrelstrength\sproutbasereports\elements\Report;
use barrelstrength\sproutbasereports\SproutBaseReports;
use craft\helpers\DateTimeHelper;
use craft\db\Query;
use Craft;

class CommerceOrderHistoryDataSource extends DataSource
{

    private $reportModel;

    public static function displayName(): string
    {
        return Craft::t('sprout-reports-commerce', 'Commerce Order History');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-reports-commerce', 'Displays all orders by date range');
    }

    /**
     *
     * Gets the results for the Order History Report
     *
     * @param Report $report
     * @param array  $settings
     *
     * @return array
     * @throws \Exception
     */
    public function getResults(Report $report, array $settings = []): array
    {
        $this->reportModel = $report;

        $calculateTotals = $report->getSetting('calculateTotals');

        if ($calculateTotals) {
            return $this->getReportWithCalculateTotals();
        }

        return $this->getReportWithLineItems();
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
        $settings = $this->report->getSettings();

        $defaultStartDate = null;
        $defaultEndDate = null;

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

        return Craft::$app->getView()->renderTemplate('sprout-reports-commerce/datasources/orderhistory/_settings', [
            'defaultStartDate' => new \DateTime($defaultStartDate),
            'defaultEndDate' => new \DateTime($defaultEndDate),
            'dateRanges' => $dateRanges,
            'settings' => $settings
        ]);
    }

    /**
     * Aggregates all results into a single line with totals
     *
     * @return array
     * @throws \Exception
     */
    protected function getReportWithCalculateTotals()
    {
        /**
         * @var $reportModel Report
         */
        $reportModel = $this->reportModel;

        $startEndDate = $reportModel->getStartEndDate();

        $startDate = $startEndDate->getStartDate();
        $endDate = $startEndDate->getEndDate();

        $query = new Query();
        $query->select("SUM([[orders.totalPaid]]) as totalRevenue")
            ->from('{{%commerce_orders}} as orders');

        if ($startDate && $endDate) {
            $query->andWhere(['>=', '[[orders.dateOrdered]]', $startDate->format('Y-m-d H:i:s')]);
            $query->andWhere(['<=', '[[orders.dateOrdered]]', $endDate->format('Y-m-d H:i:s')]);
        }

        $results = $query->all();

        if (!empty($results)) {
            foreach ($results as $key => $result) {
                $totalTax = $this->getTotalAdjustmentByType(null, 'Tax');
                $totalShipping = $this->getTotalAdjustmentByType(null, 'Shipping');

                $productRevenue = $result['totalRevenue'] - ($totalTax + $totalShipping);

                $results[$key]['Product Revenue'] = number_format($productRevenue, 2);
                $results[$key]['Tax'] = number_format($totalTax, 2);
                $results[$key]['Shipping'] = number_format($totalShipping, 2);
                $results[$key]['Total Revenue'] = number_format($result['totalRevenue'], 2);

                unset($results[$key]['totalRevenue']);
            }
        }

        return $results;
    }


    /**
     * Returns a row for each order in a given time period
     *
     * @return array
     * @throws \Exception
     */
    protected function getReportWithLineItems()
    {
        /**
         * @var $reportModel Report
         */
        $reportModel = $this->reportModel;

        $startEndDate = $reportModel->getStartEndDate();

        $startDate = $startEndDate->getStartDate();
        $endDate = $startEndDate->getEndDate();

        $query = new Query();

        $query->select('[[orders.id]] as orderId, 
                      [[orders.number]],
                      [[orders.totalPaid]],
                      [[orders.dateOrdered]]')
            ->from('{{%commerce_orders}} as orders');


        if ($startDate && $endDate) {
            $query->andWhere(['>=', '[[orders.dateOrdered]]', $startDate->format('Y-m-d H:i:s')]);
            $query->andWhere(['<=', '[[orders.dateOrdered]]', $endDate->format('Y-m-d H:i:s')]);
        }

        $query->orderBy(['[[orders.dateOrdered]]' => SORT_DESC]);

        $orders = $query->all();

        if (!empty($orders)) {
            foreach ($orders as $key => $order) {
                $totalTax = $this->getTotalAdjustmentByType($order, 'Tax');
                $totalShipping = $this->getTotalAdjustmentByType($order, 'Shipping');

                $productRevenue = $orders[$key]['totalPaid'] - ($totalShipping + $totalTax);

                $dateOrdered = DateTimeHelper::toDateTime($order['dateOrdered']);

                $orders[$key]['Order Number'] = substr($order['number'], 0, 7);
                $orders[$key]['Product Revenue'] = number_format($productRevenue, 2);
                $orders[$key]['Tax'] = number_format($totalTax, 2);
                $orders[$key]['Shipping'] = number_format($totalShipping, 2);
                $orders[$key]['Total Revenue'] = number_format($orders[$key]['totalPaid'], 2);
                $orders[$key]['Date Ordered'] = $dateOrdered->format('Y-m-d H:i:s');

                unset($orders[$key]['number']);
                unset($orders[$key]['orderId']);
                unset($orders[$key]['totalPaid']);
                unset($orders[$key]['dateOrdered']);
            }
        }

        return $orders;
    }

    /**
     * Calculate total tax and shipping include base values on orders table
     *
     * @param null $order
     * @param      $type
     *
     * @return bool|false|null|string
     * @throws \Exception
     */
    private function getTotalAdjustmentByType($order = null, $type)
    {
        $orderId = $order['orderId'];

        /**
         * @var $reportModel Report
         */
        $reportModel = $this->reportModel;

        $query = new Query();
        $query->select('SUM([[orderadjustments.amount]])')
            ->from('{{%commerce_orders}} as orders')
            ->leftJoin('{{%commerce_orderadjustments}} as orderadjustments', '[[orders.id]] = [[orderadjustments.orderId]]')
            ->where("[[orderadjustments.type]] = '$type'");

        if ($orderId != null) {
            // For Line Item Order History Report
            $query->andWhere(['[[orderadjustments.orderId]]' => $orderId]);
        } else {
            // For Aggregate Order History Report
            $startEndDate = $reportModel->getStartEndDate();

            $startDate = $startEndDate->getStartDate();
            $endDate = $startEndDate->getEndDate();

            $query->andWhere(['>=', 'orders.dateOrdered  :startDate', $startDate->format('Y-m-d H:i:s')]);
            $query->andWhere(['<=', 'orders.dateOrdered', $endDate->format('Y-m-d H:i:s')]);
        }

        return $query->scalar();
    }

    /**
     * @param $options
     *
     * @return mixed
     * @throws \Exception
     */
    public function prepOptions($options)
    {
        $options['startDate'] = DateTimeHelper::toDateTime($options['startDate']);
        $options['endDate'] = DateTimeHelper::toDateTime($options['endDate']);

        return $options;
    }
}
