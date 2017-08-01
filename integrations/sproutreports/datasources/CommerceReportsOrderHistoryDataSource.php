<?php

namespace Craft;

class CommerceReportsOrderHistoryDataSource extends SproutReportsBaseDataSource
{
	/**
	 * @var SproutReports_ReportModel
	 */
	private $reportModel;

	public function getName()
	{
		return Craft::t('Commerce Order History');
	}

	public function getDescription()
	{
		return Craft::t('Displays all orders by date range');
	}

	/**
	 * @param array $options
	 *
	 * @return string
	 */
	public function getOptionsHtml(array $options = array())
	{
		$defaultStartDate = null;
		$defaultEndDate   = null;

		$options = $this->report->getOptions();

		if (!empty($options))
		{
			$options['startDate'] = DateTime::createFromString($this->report->getOption('startDate'), craft()->timezone);
			$options['endDate']   = DateTime::createFromString($this->report->getOption('endDate'), craft()->timezone);
		}

		return craft()->templates->render('commercereports/datasources/orderhistory/_options', array(
			'options'          => $options,
			'defaultStartDate' => new DateTime($defaultStartDate),
			'defaultEndDate'   => new DateTime($defaultEndDate)
		));
	}

	/**
	 * Gets the results for the Order History Report
	 *
	 * @param SproutReports_ReportModel $report
	 * @param array                     $options
	 *
	 * @return array|\CDbDataReader
	 */
	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		$this->reportModel = $report;

		$calculateTotals = $report->getOption('calculateTotals');

		if ($calculateTotals)
		{
			return $this->getReportWithCalculateTotals();
		}

		return $this->getReportWithLineItems();
	}

	/**
	 * Aggregates all results into a single line with totals
	 *
	 * @return array|\CDbDataReader
	 */
	protected function getReportWithCalculateTotals()
	{
		$startDate = DateTime::createFromString($this->reportModel->getOption('startDate'), craft()->timezone);
		$endDate   = DateTime::createFromString($this->reportModel->getOption('endDate'), craft()->timezone);

		$query = craft()->db->createCommand()
			->select("SUM(orders.totalPaid) as totalRevenue")
			->from('commerce_orders as orders');

		if ($startDate && $endDate)
		{
			$query->andWhere('orders.dateOrdered > :startDate', array(
				':startDate' => DateTimeHelper::formatTimeForDb($startDate)
			));
			$query->andWhere('orders.dateOrdered < :endDate', array(
				':endDate' => DateTimeHelper::formatTimeForDb($endDate)
			));
		}

		$results = $query->queryAll();

		if (!empty($results))
		{
			foreach ($results as $key => $result)
			{
				$totalTax      = $this->getTotalAdjustmentByType(null, 'Tax');
				$totalShipping = $this->getTotalAdjustmentByType(null, 'Shipping');

				$productRevenue = $result['totalRevenue'] - ($totalTax + $totalShipping);

				$results[$key]['Product Revenue'] = number_format($productRevenue, 2);
				$results[$key]['Tax']             = number_format($totalTax, 2);
				$results[$key]['Shipping']        = number_format($totalShipping, 2);
				$results[$key]['Total Revenue']   = number_format($result['totalRevenue'], 2);

				unset($results[$key]['totalRevenue']);
			}
		}

		return $results;
	}

	/**
	 * Returns a row for each order in a given time period
	 *
	 * @return array|\CDbDataReader
	 */
	protected function getReportWithLineItems()
	{
		$startDate = DateTime::createFromString($this->reportModel->getOption('startDate'), craft()->timezone);
		$endDate   = DateTime::createFromString($this->reportModel->getOption('endDate'), craft()->timezone);

		$query = craft()->db->createCommand()
			->select("orders.id as orderId, 
			          orders.number,
			          orders.totalPaid,
			          orders.dateOrdered")
			->from('commerce_orders as orders');

		if ($startDate && $endDate)
		{
			$query->andWhere('orders.dateOrdered > :startDate', array(
				':startDate' => DateTimeHelper::formatTimeForDb($startDate)
			));
			$query->andWhere('orders.dateOrdered < :endDate', array(
				':endDate' => DateTimeHelper::formatTimeForDb($endDate)
			));
		}

		$query->order('dateOrdered DESC');

		$orders = $query->queryAll();

		if (!empty($orders))
		{
			foreach ($orders as $key => $order)
			{
				$totalTax      = $this->getTotalAdjustmentByType($order, 'Tax');
				$totalShipping = $this->getTotalAdjustmentByType($order, 'Shipping');

				$productRevenue = $orders[$key]['totalPaid'] - ($totalShipping + $totalTax);

				$dateOrdered = DateTime::createFromString($order['dateOrdered']);

				$orders[$key]['Order Number']    = substr($order['number'], 0, 7);
				$orders[$key]['Product Revenue'] = number_format($productRevenue, 2);
				$orders[$key]['Tax']             = number_format($totalTax, 2);
				$orders[$key]['Shipping']        = number_format($totalShipping, 2);
				$orders[$key]['Total Revenue']   = number_format($orders[$key]['totalPaid'], 2);
				$orders[$key]['Date Ordered']    = $dateOrdered;

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
	 * @return bool|\CDbDataReader|mixed|string
	 */
	private function getTotalAdjustmentByType($order = null, $type)
	{
		$orderId = $order['orderId'];

		$query = craft()->db->createCommand()
			->select('SUM(orderadjustments.amount)')
			->from('commerce_orders as orders')
			->leftJoin('commerce_orderadjustments as orderadjustments', 'orders.id = orderadjustments.orderId')
			->where("orderadjustments.type = '$type'");

		if ($orderId != null)
		{
			// For Line Item Order History Report
			$query->andWhere('orderadjustments.orderId = :orderId', array(
				'orderId' => $orderId
			));
		}
		else
		{
			// For Aggregate Order History Report
			$startDate = DateTime::createFromString($this->reportModel->getOption('startDate'), craft()->timezone);
			$endDate   = DateTime::createFromString($this->reportModel->getOption('endDate'), craft()->timezone);

			$query->andWhere('orders.dateOrdered > :startDate', array(
				':startDate' => DateTimeHelper::formatTimeForDb($startDate)
			));
			$query->andWhere('orders.dateOrdered < :endDate', array(
				':endDate' => DateTimeHelper::formatTimeForDb($endDate)
			));
		}

		return $query->queryScalar();
	}
}