<?php

namespace barrelstrength\sproutreportscommerce;

use barrelstrength\sproutbasereports\services\DataSources;
use barrelstrength\sproutreportscategories\integrations\sproutreports\datasources\Categories;
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
    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public $hasCpSection = false;

    /**
     * @var bool
     */
    public $hasCpSettings = false;

    public function init()
    {
        parent::init();

        Event::on(DataSources::class, DataSources::EVENT_REGISTER_DATA_SOURCES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Categories::class;
        });
    }
}
