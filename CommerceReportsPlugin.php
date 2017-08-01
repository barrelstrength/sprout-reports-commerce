<?php

namespace Craft;

class CommerceReportsPlugin extends BasePlugin
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Commerce Reports');
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
		Craft::import('plugins.commercereports.integrations.sproutreports.datasources.CommerceReportsProductRevenueDataSource');
		Craft::import('plugins.commercereports.integrations.sproutreports.datasources.CommerceReportsOrderHistoryDataSource');

		return array(
			new CommerceReportsProductRevenueDataSource(),
			new CommerceReportsOrderHistoryDataSource()
		);
	}
}
