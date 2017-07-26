<?php

namespace Craft;

class CommerceReportsOrderHistoryDataSource extends SproutReportsBaseDataSource
{
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
		$startDate = DateTime::createFromString($report->getOption('startDate'), craft()->timezone);
		$endDate   = DateTime::createFromString($report->getOption('endDate'), craft()->timezone);

		// @todo - needs to take into account multiple adjustments and the various types of adjustments
		$query = craft()->db->createCommand()
			->select('orders.number as Order Number,
			          orderadjustments.type as Type, 
			          orderadjustments.amount as Amount, 
			          orders.totalPaid as Total Paid,
			          orders.dateOrdered as Date Ordered')
			->from('commerce_orders as orders')
			->leftJoin('commerce_orderadjustments as orderadjustments', 'orders.id = orderadjustments.orderId');

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

		return $results;
	}
}