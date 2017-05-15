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
		return craft()->templates->render('commercereports/datasources/_options/productrevenue', array(
			'options' => $this->report->getOptions()
		));
	}

	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		// First, use dynamic options, fallback to report options
		if (!count($options))
		{
			$options = $report->getOptions();
		}

		$criteria = $criteria = craft()->elements->getCriteria('Commerce_Order');
		$criteria->limit = null;

		// Don't use the search
		$criteria->search = null;

		$query = craft()->elements->buildElementsQuery($criteria)
			->select('variants.id as \'Variants ID\', products.id as \'Product ID\', orders.id as \'Order ID\', sum(lineitems.total) as Revenue, variants.sku as SKU')
			->leftJoin('commerce_lineitems as lineitems', 'orders.id = lineitems.orderId')
			->leftJoin('commerce_variants as variants', 'lineitems.purchasableId = variants.id')
			->leftJoin('commerce_products as products', 'variants.productId = products.id');


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