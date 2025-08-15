<?php

namespace app\modules\timeTracker\models;

use app\modules\timeTracker\traits\CoordinateTrait;
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
    use CoordinateTrait;
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

    public static function getLocation($locationName)
    {
        return self::find()->where(['location' => $locationName])->one();
    }

    public function distance($latitudeFrom = 0,  $longitudeFrom = 0,  $latitudeTo = 0,  $longitudeTo = 0) {
        if (!$latitudeFrom || !$longitudeFrom || !$latitudeTo || !$longitudeTo) {
            return 0;
        }
        return $this->getDistance($latitudeFrom,  $longitudeFrom,  $latitudeTo,  $longitudeTo);
    }

}