<?php

namespace app\modules\timeTracker\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $VehicleNumber
 * @property string $VehicleName
 * @property string $UpdateUtc
 * @property string $IsPrivate
 * @property string $DriverNumber
 * @property string $FirstName
 * @property string $LastName
 * @property string $AddressLine1
 * @property string $AddressLine2
 * @property string $Locality
 * @property string $AdministrativeArea
 * @property string $PostalCode
 * @property string $Country
 * @property string $Latitude
 * @property string $Longitude
 * @property string $Speed
 * @property string $BatteryLevel
 * @property string $TractionBatteryChargingLastStartUtc
 * @property string $TractionBatteryChargingUtc
 * @property string $location
 *
 * @property timestamp $created_at
 * @property timestamp $updated_at
 */
class VehiclesHistory extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%vehicles_history}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value' => new Expression('NOW()')
            ],
        ];
    }

}