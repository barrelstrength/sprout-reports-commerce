<?php

namespace Craft;

class SproutReportsCommerceProductRevenueDataSource extends SproutReportsBaseDataSource
{
	public function getName()
	{
		return Craft::t('Commerce Product Revenue');
	}

	public function getDescription()
	{
		return Craft::t('Create sales reports for your products and variants.');
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

		return craft()->templates->render('commercereports/datasources/productrevenue/_options', array(
			'options' => $options,
			'defaultStartDate' => new DateTime($defaultStartDate),
			'defaultEndDate'   => new DateTime($defaultEndDate)
		));
	}

	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		$displayVariants = false;
		$startDate = DateTime::createFromString($report->getOption('startDate'), craft()->timezone);
		$endDate   = DateTime::createFromString($report->getOption('endDate'), craft()->timezone);

		// First, use dynamic options, fallback to report options
		if (!count($options))
		{
			$options = $report->getOptions();
			$displayVariants = $options['variants'];
		}

		$criteria = $criteria = craft()->elements->getCriteria('Commerce_Order');
		$criteria->limit = null;

		// Don't use the search
		$criteria->search = null;

		$query = craft()->db->createCommand()
			->select('variants.id as \'Variants ID\', 
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
			->from('commerce_orders as orders')
			->leftJoin('commerce_lineitems as lineitems', 'orders.id = lineitems.orderId')
			->leftJoin('commerce_variants as variants', 'lineitems.purchasableId = variants.id')
			->leftJoin('commerce_products as products', 'variants.productId = products.id');

		if ($startDate && $endDate)
		{
			$query->andWhere('orders.dateOrdered > :startDate', array(':startDate' => $startDate->mySqlDateTime()));
		  $query->andWhere('orders.dateOrdered < :endDate', array(':endDate' => $endDate->mySqlDateTime()));
		}

		if (!empty($displayVariants))
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
				$productId = $result['Product ID'];
				$variantId = $result['Variants ID'];

				$productElement = craft()->elements->getElementById($productId);

				if (!empty($displayVariants))
				{
					$variantElement = craft()->elements->getElementById($variantId);

					if ($variantElement)
					{
						$results[$key]['Variant Title'] = $variantElement->title;
					}
					else
					{
						$results[$key]['Variant Title'] = Craft::t('Variant has been deleted');
					}
				}

				if ($productElement)
				{
					$results[$key]['Product Title'] = $productElement->title;
				}
				else
				{
					$results[$key]['Product Title'] = Craft::t('Product has been deleted');
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

	public function prepOptions($options)
	{

		$options['startDate'] = DateTime::createFromString($options['startDate']);
		$options['endDate'] = DateTime::createFromString($options['endDate']);

		return $options;	
	}
}
