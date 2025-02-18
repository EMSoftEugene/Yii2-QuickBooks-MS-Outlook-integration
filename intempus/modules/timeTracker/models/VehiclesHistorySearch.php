<?php

namespace app\modules\timeTracker\models;

use Yii;
use yii\base\Model;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\data\ActiveDataProvider;
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
class VehiclesHistorySearch extends VehiclesHistory
{

    public function rules()
    {
        // only fields in rules() are searchable
        return [
            [['location'], 'string'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = self::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['UpdateUtc' => SORT_DESC]],
        ]);

        // load the search form data and validate
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        // adjust the query by adding the filters
        $query->andFilterWhere(['like', 'location', $this->location]);


        return $dataProvider;
    }

}