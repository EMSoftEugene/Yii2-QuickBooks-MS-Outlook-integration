<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class m241103_170424_create_api_auth
 */
class m241105_170424_update_api_auth extends Migration
{
    public function up()
    {
        $this->alterColumn('{{%api_auth}}', 'access_token', $this->text()->null());
        $this->alterColumn('{{%api_auth}}', 'refresh_token', $this->text()->null());
    }

    public function down()
    {
    }
}
