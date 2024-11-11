<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class m241103_170424_create_api_auth
 */
class m241109_170424_create_geolocation extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%tsheet_geolocation}}', [
            'id' => $this->primaryKey(),
            'tsheet_user_id' => $this->integer()->null(),
            'tsheet_id' => $this->integer()->null(),
            'lat' => $this->string()->null(),
            'lon' => $this->string()->null(),
            'speed' => $this->integer()->null(),
            'tsheet_created' => $this->timestamp()->null(),

            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->null()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%tsheet_geolocation}}');
    }
}
