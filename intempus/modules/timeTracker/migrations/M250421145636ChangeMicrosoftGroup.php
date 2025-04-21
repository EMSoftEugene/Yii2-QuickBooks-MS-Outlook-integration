<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M250421145636ChangeMicrosoftGroup
 */
class M250421145636ChangeMicrosoftGroup extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('microsoft_group', 'verizon_id', $this->string()->null());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('microsoft_group', 'verizon_id', $this->integer()->null());
    }


}
