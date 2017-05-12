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
		return '1.0.0';
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

	public function init()
	{
		parent::init();

	//	Craft::import('plugins.commercereports.integrations.sproutreports.reports.*');
		Craft::import('plugins.commercereports.integrations.sproutreports.datasources.CommerceReportsProductRevenueDataSource');
	}
	/**
	 * @return array
	 */
	public function registerSproutReportsDataSources()
	{
		return array(
			new CommerceReportsProductRevenueDataSource()
		);
	}
}