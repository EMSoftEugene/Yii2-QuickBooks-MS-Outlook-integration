<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class m241103_170424_create_api_auth
 */
class m241108_170424_create_tsheet_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%tsheet_user}}', [
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
        $this->dropTable('{{%tsheet_user}}');
    }
}
