<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class m241103_170424_create_api_auth
 */
class m241106_170424_create_microsoft_location extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%microsoft_location}}', [
            'id' => $this->primaryKey(),
            'displayName' => $this->string()->notNull(),
            'lat' => $this->string()->null(),
            'lon' => $this->string()->null(),

            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->null()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%microsoft_location}}');
    }
}
