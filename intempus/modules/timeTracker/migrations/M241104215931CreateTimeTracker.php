<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M241104215931CreateTimeTracker
 */
class M241104215931CreateTimeTracker extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%time_tracker}}', [
            'id' => $this->primaryKey(),
            'isMicrosoftLocation' => $this->boolean()->notNull()->defaultValue(0),
            'locationName' => $this->string()->notNull(),
            'date' => $this->date()->null(),
            'clock_in' => $this->time()->null(),
            'clock_out' => $this->time()->null(),
            'duration' => $this->string()->null(),
            'user_id' => $this->integer()->null(),
            'user' => $this->string()->null(),

            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->null()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%time_tracker}}');
    }
}
