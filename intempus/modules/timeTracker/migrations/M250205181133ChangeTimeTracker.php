<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M250205181133ChangeTimeTracker
 */
class M250205181133ChangeTimeTracker extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('time_tracker', 'user_id', $this->string()->null());//timestamp new_data_type
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('microsoft_group', 'microsoft_id', $this->integer()->null());//timestamp new_data_type
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M250205181133ChangeMicrosoftGroup cannot be reverted.\n";

        return false;
    }
    */
}
