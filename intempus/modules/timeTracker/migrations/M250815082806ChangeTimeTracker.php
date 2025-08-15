<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M250815082806ChangeTimeTracker
 */
class M250815082806ChangeTimeTracker extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('time_tracker', 'locationNameVerizon', $this->string()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('time_tracker', 'locationNameVerizon');
    }
}
