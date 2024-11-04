<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class m241103_170424_create_api_auth
 */
class m241103_170424_create_api_auth extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%api_auth}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'access_token' => $this->string()->null(),
            'refresh_token' => $this->string()->null(),
            'expires_in' => $this->string()->null(),
            'refresh_token_expires_in' => $this->string()->null(),
            'realm_id' => $this->string()->null()->comment('company id'),

            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->null()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%api_auth}}');
    }
}
