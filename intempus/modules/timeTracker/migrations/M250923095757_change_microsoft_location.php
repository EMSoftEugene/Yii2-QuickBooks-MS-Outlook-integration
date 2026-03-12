<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M250923095757_change_microsoft_location
 */
class M250923095757_change_microsoft_location extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%microsoft_location}}', 'microsoft_ids', 'JSON AFTER `microsoft_id`');

        $this->execute("UPDATE {{%microsoft_location}} SET microsoft_ids = JSON_ARRAY(microsoft_id) WHERE microsoft_id IS NOT NULL");

        $this->dropColumn('{{%microsoft_location}}', 'microsoft_id');

        $this->renameColumn('{{%microsoft_location}}', 'microsoft_ids', 'microsoft_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('{{%microsoft_location}}', 'microsoft_id_old', 'VARCHAR(255) AFTER `microsoft_id`');

        $this->execute("UPDATE {{%microsoft_location}} SET microsoft_id_old = JSON_UNQUOTE(JSON_EXTRACT(microsoft_id, '$[0]'))");

        $this->dropColumn('{{%microsoft_location}}', 'microsoft_id');

        // Переименовываем обратно
        $this->renameColumn('{{%microsoft_location}}', 'microsoft_id_old', 'microsoft_id');
    }
}
