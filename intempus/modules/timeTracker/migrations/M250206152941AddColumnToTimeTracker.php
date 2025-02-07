<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M250206152941AddColumnToTimeTracker
 */
class M250206152941AddColumnToTimeTracker extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('time_tracker', 'haul_away', $this->boolean()->defaultValue(0)->after('user'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('time_tracker', 'haul_away');
    }
}
