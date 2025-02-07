<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M250204210605UpdateLocation
 */
class M250204210605UpdateLocation extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('microsoft_location', 'date_time', $this->string()->null()->after('lon'));
        $this->addColumn('microsoft_location', 'haul_away', $this->boolean()->defaultValue(0)->after('date_time'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('microsoft_location', 'date_time');
        $this->dropColumn('microsoft_location', 'haul_away');

    }

}
