<?php

use yii\db\Migration;

/**
 * Class m241027_153352_create_time_entries
 */
class m241027_153352_create_time_entries extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%time_entries}}', [
            'id' => $this->primaryKey(),
            'time_off_request_id' => $this->integer()->notNull(),
            'date' => $this->date()->notNull(),
            'duration' => $this->integer()->notNull(),
            'approver_id' => $this->integer()->null(),
            'approver_last_name' => $this->string()->null(),
            'approver_first_name' => $this->string()->null(),
            'user_id' => $this->integer()->null(),
            'user_last_name' => $this->string()->null(),
            'user_first_name' => $this->string()->null(),
            'timesheet_notes' => $this->string()->null(),
            'location_addr' => $this->string()->null(),
            'location_city' => $this->string()->null(),
            'location_state' => $this->string()->null(),
            'location_zip' => $this->string()->null(),
            'location_country' => $this->string()->null(),

            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->null()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%time_entries}}');
    }
}
