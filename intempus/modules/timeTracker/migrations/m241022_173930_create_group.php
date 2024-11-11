<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class m241022_173930_create_group
 */
class m241022_173930_create_group extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%microsoft_group}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'microsoft_id' => $this->string()->null(),

            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->null()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%microsoft_group}}');
    }
}
