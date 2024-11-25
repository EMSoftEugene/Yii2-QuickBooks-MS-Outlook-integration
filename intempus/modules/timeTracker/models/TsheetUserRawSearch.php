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
class TsheetUserRawSearch extends TsheetUserRaw
{
    public function rules()
    {
        // only fields in rules() are searchable
        return [
            [['first_name', 'last_name', 'email'], 'string'],
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
            'pagination' => false,
            'sort' => ['defaultOrder' => ['first_name' => SORT_ASC, 'last_name' => SORT_ASC]],
        ]);

        // load the search form data and validate
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        // adjust the query by adding the filters
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['like', 'first_name', $this->first_name]);
        $query->andFilterWhere(['like', 'last_name', $this->last_name]);
        $query->andFilterWhere(['like', 'email', $this->email]);

        return $dataProvider;
    }

}