<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M241125194334CreateTsheetUserRaw
 */
class M241125194334CreateTsheetUserRaw extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%tsheet_user_raw}}', [
            'id' => $this->primaryKey(),
            'first_name' => $this->string()->null(),
            'last_name' => $this->string()->null(),
            'email' => $this->string()->null(),
            'external_id' => $this->integer()->null(),

            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->null()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%tsheet_user_raw}}');
    }
}
