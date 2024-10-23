<?php

use yii\db\Migration;

/**
 * Class m241023_140023_update_user_table
 */
class m241023_140023_update_user_table extends Migration
{
    public function up()
    {
        $this->addColumn('{{%user}}', 'intuit_access_token', $this->string()->null());
        $this->addColumn('{{%user}}', 'intuit_refresh_token', $this->string()->null());
        $this->addColumn('{{%user}}', 'intuit_x_refresh_token_expires_in', $this->string()->null());
        $this->addColumn('{{%user}}', 'intuit_expires_in', $this->string()->null());
        $this->addColumn('{{%user}}', 'intuit_realm_id', $this->string()->null());
    }

    public function down()
    {
        $this->dropColumn('{{%user}}', 'intuit_access_token');
        $this->dropColumn('{{%user}}', 'intuit_refresh_token');
        $this->dropColumn('{{%user}}', 'intuit_x_refresh_token_expires_in');
        $this->dropColumn('{{%user}}', 'intuit_expires_in');
        $this->dropColumn('{{%user}}', 'intuit_realm_id');
    }
}
