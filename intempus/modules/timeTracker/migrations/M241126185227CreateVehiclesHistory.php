<?php

namespace app\modules\timeTracker\migrations;

use yii\db\Migration;

/**
 * Class M241126185226AddColumnToTsheetGeolocation
 */
class M241126185227CreateVehiclesHistory extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%vehicles_history}}', [
            'id' => $this->primaryKey(),
            'VehicleNumber' => $this->string()->null(),
            'VehicleName' => $this->string()->null(),
            'UpdateUtc' => $this->timestamp()->null(),
            'IsPrivate' => $this->string()->null(),
            'DriverNumber' => $this->string()->null(),
            'FirstName' => $this->string()->null(),
            'LastName' => $this->string()->null(),
            'AddressLine1' => $this->string()->null(),
            'AddressLine2' => $this->string()->null(),
            'Locality' => $this->string()->null(),
            'AdministrativeArea' => $this->string()->null(),
            'PostalCode' => $this->string()->null(),
            'Country' => $this->string()->null(),
            'Latitude' => $this->string()->null(),
            'Longitude' => $this->string()->null(),
            'Speed' => $this->string()->null(),
            'BatteryLevel' => $this->string()->null(),
            'TractionBatteryChargingLastStartUtc' => $this->string()->null(),
            'TractionBatteryChargingUtc' => $this->string()->null(),
            'location' => $this->string()->null()->comment('address composed of all values'),

            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->null()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%vehicles_history}}');
    }
}
