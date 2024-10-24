<?php

use yii\db\Migration;

/**
 * Class m241024_170559_add_tsheet_auth_to_user
 */
class m241024_170559_add_tsheet_auth_to_user extends Migration
{
    public function up()
    {
        $this->addColumn('{{%user}}', 'tsheets_access_token', $this->string()->null());
        $this->addColumn('{{%user}}', 'tsheets_refresh_token', $this->string()->null());
        $this->addColumn('{{%user}}', 'tsheets_refresh_token_expires_in', $this->string()->null());
        $this->addColumn('{{%user}}', 'tsheets_expires_in', $this->string()->null());
        $this->addColumn('{{%user}}', 'tsheets_realm_id', $this->string()->null()->comment('company_id'));
    }

    public function down()
    {
        $this->dropColumn('{{%user}}', 'tsheets_access_token');
        $this->dropColumn('{{%user}}', 'tsheets_refresh_token');
        $this->dropColumn('{{%user}}', 'tsheets_refresh_token_expires_in');
        $this->dropColumn('{{%user}}', 'tsheets_expires_in');
        $this->dropColumn('{{%user}}', 'tsheets_realm_id');
    }
}
