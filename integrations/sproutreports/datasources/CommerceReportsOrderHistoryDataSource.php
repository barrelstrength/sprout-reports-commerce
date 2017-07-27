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

		return craft()->templates->render('commercereports/datasources/orders/_options', array(
			'options' => $options,
			'defaultStartDate' => new DateTime($defaultStartDate),
			'defaultEndDate'   => new DateTime($defaultEndDate)
		));
	}

	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		$this->reportModel = $report;

		$calculateTotals = $report->getOption('calculateTotals');

		if ($calculateTotals)
		{
			return $this->getCalculateTotals();
		}

		return $this->getOrdersTotal();
	}

	protected function getCalculateTotals()
	{
		$startDate = DateTime::createFromString($this->reportModel->getOption('startDate'), craft()->timezone);
		$endDate   = DateTime::createFromString($this->reportModel->getOption('endDate'), craft()->timezone);

		$query = craft()->db->createCommand()
			->select("SUM(orders.totalPaid) as 'Total Revenue'")
			->from('commerce_orders as orders');

		if ($startDate && $endDate)
		{
			$query->andWhere('orders.dateOrdered > :startDate', array(':startDate' => $startDate->mySqlDateTime()));
			$query->andWhere('orders.dateOrdered < :endDate', array(':endDate' => $endDate->mySqlDateTime()));
		}

		$results = $query->queryAll();

		if (!empty($results))
		{
			foreach ($results as $key => $result)
			{
				$totalTax      = $this->getTotalAdjustmentByType(null);
				$totalShipping = $this->getTotalAdjustmentByType(null, 'Shipping');

				$results[$key]['Total Revenue']   = number_format($result['Total Revenue'], 2);
				$results[$key]['Product Revenue'] = number_format($result['Total Revenue'] - ($totalTax + $totalShipping), 2);

				$results[$key]['Tax']      = number_format($totalTax, 2);
				$results[$key]['Shipping'] = number_format($totalShipping, 2);
			}
		}

		return $results;
	}

	protected function getOrdersTotal()
	{
		$startDate = DateTime::createFromString($this->reportModel->getOption('startDate'), craft()->timezone);
		$endDate   = DateTime::createFromString($this->reportModel->getOption('endDate'), craft()->timezone);

		// @todo - needs to take into account multiple adjustments and the various types of adjustments
		$query = craft()->db->createCommand()
			->select("orders.id as Order ID, orders.number as Order Number,			      
			          orders.dateOrdered, orders.totalPaid as Total Revenue")
			->from('commerce_orders as orders');

		if ($startDate && $endDate)
		{
			$query->andWhere('orders.dateOrdered > :startDate', array(':startDate' => $startDate->mySqlDateTime()));
			$query->andWhere('orders.dateOrdered < :endDate', array(':endDate' => $endDate->mySqlDateTime()));
		}

		$results = $query->queryAll();

		if ($results)
		{
			foreach ($results as $key => $result)
			{
				$results[$key]['Order Number'] = substr($result['Order Number'], 0, 7);
			}
		}

		if (!empty($results))
		{
			foreach ($results as $key => $result)
			{
				$orderId = $result['Order ID'];

				$totalTax      = $this->getTotalAdjustmentByType($orderId);

				$totalShipping = $this->getTotalAdjustmentByType($orderId, 'Shipping');

				$results[$key]['Tax']      = $totalTax;
				$results[$key]['Shipping'] = $totalShipping;

				$productRevenue = $results[$key]['Total Revenue'] - ($totalShipping + $totalTax);

				$results[$key]['Product Revenue']  = number_format($productRevenue, 2);
				$results[$key]['Date Ordered']  = $result['dateOrdered'];

				// Place dateOrdered on the last column
				unset($results[$key]['dateOrdered']);
			}
		}

		return $results;
	}

	/**
	 * Calculate total tax and shipping include base values on orders table
	 *
	 * @param        $orderId
	 * @param string $type
	 *
	 * @return bool|\CDbDataReader|mixed|string
	 */
	private function getTotalAdjustmentByType($orderId = null, $type = 'Tax')
	{
		$included = 'orders.baseTax + orders.baseTaxIncluded';

		if ($type == 'Shipping')
		{
			$included = 'orders.baseShippingCost';
		}

		$query = craft()->db->createCommand()
			->select("SUM(orderadjustments.amount) + $included")
			->from('commerce_orders as orders')
			->leftJoin('commerce_orderadjustments as orderadjustments', 'orders.id = orderadjustments.orderId');

			if ($orderId != null)
			{
				$query->where('orders.id = :orderId', array('orderId' => $orderId));
			}
			else
			{
				$startDate = DateTime::createFromString($this->reportModel->getOption('startDate'), craft()->timezone);
				$endDate   = DateTime::createFromString($this->reportModel->getOption('endDate'), craft()->timezone);

				$query->andWhere('orders.dateOrdered > :startDate', array(':startDate' => $startDate->mySqlDateTime()));
				$query->andWhere('orders.dateOrdered < :endDate', array(':endDate' => $endDate->mySqlDateTime()));
			}

			$query->andWhere("orderadjustments.type = '$type'");

		return $query->queryScalar();
	}
}