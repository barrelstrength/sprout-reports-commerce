<?php

namespace barrelstrength\sproutreportscommerce;

use barrelstrength\sproutbasereports\services\DataSources;
use barrelstrength\sproutbasereports\SproutBaseReports;
use barrelstrength\sproutreportscommerce\integrations\sproutreports\datasources\CommerceOrderHistoryDataSource;
use barrelstrength\sproutreportscommerce\integrations\sproutreports\datasources\CommerceProductRevenueDataSource;
use craft\base\Plugin;
use yii\base\Event;
use craft\events\RegisterComponentTypesEvent;

/**
 * Class SproutReportsCommercePlugin
 *
 * @author    Barrel Strength Design LLC <sprout@barrelstrengthdesign.com>
 * @copyright Copyright (c) 2012, Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 * @see       http://sprout.barrelstrengthdesign.com
 * @package   craft.plugins.sproutreportscommerceplugin
 * @since     2.0
 */
class SproutReportsCommerce extends Plugin
{
    public function init()
    {
        parent::init();

        Event::on(DataSources::class, DataSources::EVENT_REGISTER_DATA_SOURCES, static function(RegisterComponentTypesEvent $event) {
            $event->types[] = CommerceOrderHistoryDataSource::class;
            $event->types[] = CommerceProductRevenueDataSource::class;
        });
    }

    /**
     * @inheritDoc
     */
    protected function afterInstall()
    {
        $dataSourceTypes = [
            CommerceOrderHistoryDataSource::class,
            CommerceProductRevenueDataSource::class
        ];

        SproutBaseReports::$app->dataSources->installDataSources($dataSourceTypes);
    }
}
