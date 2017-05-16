<?php

namespace Craft;

class CommerceReportsProductRevenueDataSource extends SproutReportsBaseDataSource
{
	public function getName()
	{
		return Craft::t('Commerce Product Revenue.');
	}

	public function getDescription()
	{
		return Craft::t('Displays a list of products and the total revenue earned');
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

		return craft()->templates->render('commercereports/datasources/_options/productrevenue', array(
			'options' => $options,
			'defaultStartDate' => new DateTime($defaultStartDate),
			'defaultEndDate'   => new DateTime($defaultEndDate)
		));
	}

	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		$startDate = DateTime::createFromString($report->getOption('startDate'), craft()->timezone);
		$endDate   = DateTime::createFromString($report->getOption('endDate'), craft()->timezone);


		// First, use dynamic options, fallback to report options
		if (!count($options))
		{
			$options = $report->getOptions();
		}

		$criteria = $criteria = craft()->elements->getCriteria('Commerce_Order');
		$criteria->limit = null;

		// Don't use the search
		$criteria->search = null;

		$query = craft()->db->createCommand()
			->select('variants.id as \'Variants ID\', products.id as \'Product ID\', orders.id as \'Order ID\', orders.dateOrdered as \'Date Ordered\', sum(lineitems.total) as Revenue, variants.sku as SKU')
			->from('commerce_orders as orders')
			->leftJoin('commerce_lineitems as lineitems', 'orders.id = lineitems.orderId')
			->leftJoin('commerce_variants as variants', 'lineitems.purchasableId = variants.id')
			->leftJoin('commerce_products as products', 'variants.productId = products.id');

		if ($startDate && $endDate)
		{
			$query->andWhere('orders.dateOrdered > :startDate', array(':startDate' => $startDate->mySqlDateTime()));
		  $query->andWhere('orders.dateOrdered < :endDate', array(':endDate' => $endDate->mySqlDateTime()));
		}

		if (!empty($options['variants']))
		{
			$query->group('lineitems.purchasableId');
		}
		else
		{
			$query->group('products.id');
		}

		$query->order('products.id DESC');


		$results = $query->queryAll();

		if ($results)
		{
			foreach ($results as $key => $result)
			{
				$variantId = $result['Variants ID'];

				$element = craft()->elements->getElementById($variantId);

				$results[$key]['Title'] = $element->title;

				if (empty($options['variants']))
				{
					// Do not display to avoid confusion
					unset($results[$key]['SKU']);
					unset($results[$key]['Variants ID']);
				}

				$results[$key] = array_reverse($results[$key], true);
			}
		}

		return $results;
	}
}