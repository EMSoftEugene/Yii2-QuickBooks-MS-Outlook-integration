<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M250422143800AddColumnToLocation
 */
class M250422143800AddColumnToLocation extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('microsoft_location', 'microsoft_id', $this->string()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('microsoft_location', 'microsoft_id');
    }
}
