<?php

namespace Craft;

class CommerceReportsOrdersDataSource extends SproutReportsBaseDataSource
{
	public function getName()
	{
		return Craft::t('Commerce Orders Report');
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

		$orderAdjustmentTypeOptions = array(
			'Shipping' => 'Shipping', 'Tax' => 'Tax'
		);

		return craft()->templates->render('commercereports/datasources/orders/_options', array(
			'options' => $options,
			'orderAdjustmentTypeOptions' => $orderAdjustmentTypeOptions,
			'defaultStartDate' => new DateTime($defaultStartDate),
			'defaultEndDate'   => new DateTime($defaultEndDate)
		));
	}

	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		$startDate = DateTime::createFromString($report->getOption('startDate'), craft()->timezone);
		$endDate   = DateTime::createFromString($report->getOption('endDate'), craft()->timezone);

		$type = 'Shipping';

		// First, use dynamic options, fallback to report options
		if (!count($options))
		{
			$options = $report->getOptions();

			$type = $options['orderAdjustmentType'];
		}

		$query = craft()->db->createCommand()
			->select('orders.number as Order Number, orders.dateOrdered as Date Ordered, orderadjustments.orderId as Order ID, orderadjustments.type as Type, orderadjustments.amount as Amount, orders.totalPaid as Total Paid')
			->from('commerce_orders as orders')
			->where("orderadjustments.type = '$type'")
			->leftJoin('commerce_orderadjustments as orderadjustments', 'orders.id = orderadjustments.orderId');

		if ($startDate && $endDate)
		{
			$query->andWhere('orders.dateOrdered > :startDate', array(':startDate' => $startDate->mySqlDateTime()));
			$query->andWhere('orders.dateOrdered < :endDate', array(':endDate' => $endDate->mySqlDateTime()));
		}

		//$query->group('orderadjustments.type');

		$results = $query->queryAll();

		return $results;
	}
}