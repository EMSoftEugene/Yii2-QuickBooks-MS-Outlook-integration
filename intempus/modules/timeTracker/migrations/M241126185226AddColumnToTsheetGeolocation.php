<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M241126185226AddColumnToTsheetGeolocation
 */
class M241126185226AddColumnToTsheetGeolocation extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('tsheet_geolocation', 'converted_location', $this->string()->null()->after('lon'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('tsheet_geolocation', 'converted_location');
    }
}
