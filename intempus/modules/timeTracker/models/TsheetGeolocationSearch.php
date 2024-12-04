<?php

namespace app\modules\timeTracker\models;

use kartik\daterange\DateRangeBehavior;
use Yii;
use yii\base\Model;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * @inheritDoc
 */
class TsheetGeolocationSearch extends TsheetGeolocation
{
    public function rules()
    {
        // only fields in rules() are searchable
        return [
            [['converted_location', 'lat', 'lon'], 'string'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $dateStart = $params['date_start'] ?? null;
        $dateEnd = $params['date_end'] ?? null;
        if(!$dateStart && !$dateEnd){
            $dateStart = date('Y-m-01'). ' 00:00:00';
            $dateEnd = date('Y-m-t') . ' 23:59:59';
        }

        $query = self::find();
        $query->where(['tsheet_user_id' => $params['tsheetUserId']])
            ->andWhere(['between', 'tsheet_created', $dateStart, $dateEnd]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['tsheet_created' => SORT_ASC]],
        ]);

        // load the search form data and validate
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        // adjust the query by adding the filters
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['like', 'converted_location', $this->converted_location]);
        $query->andFilterWhere(['like', 'lat', $this->lat]);
        $query->andFilterWhere(['like', 'lon', $this->lon]);

        return $dataProvider;
    }

}