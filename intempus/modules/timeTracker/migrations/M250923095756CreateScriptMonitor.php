<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M250923095756CreateScriptMonitor
 */
class M250923095756CreateScriptMonitor extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('script_monitor', [
            'id' => $this->primaryKey(),
            'script_name' => $this->string(100)->notNull(),
            'status' => $this->string(20)->notNull(),
            'execution_date' => $this->date()->notNull(),
            'created_at' => $this->datetime(),
        ]);

        $this->createIndex('idx_script_monitor_date_name', 'script_monitor', ['execution_date', 'script_name'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('script_monitor');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M250923095756CreateScriptMonitor cannot be reverted.\n";

        return false;
    }
    */
}
