<?php

namespace Craft;

class SproutReportsCommercePlugin extends BasePlugin
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Sprout Reports for Craft Commerce');
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return '0.6.0';
	}

	/**
	 * @return string
	 */
	public function getSchemaVersion()
	{
		return '0.5.0';
	}

	/**
	 * @return string
	 */
	public function getDeveloper()
	{
		return 'Barrel Strength Design';
	}

	/**
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return 'http://barrelstrengthdesign.com';
	}

	/**
	 * @return bool
	 */
	public function hasCpSection()
	{
		return false;
	}

	/**
	 * @return array
	 */
	public function registerSproutReportsDataSources()
	{
		Craft::import('plugins.sproutreportscommerce.integrations.sproutreports.datasources.SproutReportsCommerceProductRevenueDataSource');
		Craft::import('plugins.sproutreportscommerce.integrations.sproutreports.datasources.SproutReportsCommerceOrderHistoryDataSource');

		return array(
			new SproutReportsCommerceProductRevenueDataSource(),
			new SproutReportsCommerceOrderHistoryDataSource()
		);
	}
}
