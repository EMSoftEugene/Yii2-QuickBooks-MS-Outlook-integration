<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M250421081053AddColumnToMicrosoftGroup
 */
class M250421081053AddColumnToMicrosoftGroup extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('microsoft_group', 'verizon_id', $this->integer()->null()->after('microsoft_id'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('microsoft_group', 'verizon_id');
    }

}
