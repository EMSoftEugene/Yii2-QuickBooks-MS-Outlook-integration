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
class TimeTrackerSearch extends TimeTracker
{
    public $date_range;
    public $date_start;
    public $date_end;

    public function rules()
    {
        // only fields in rules() are searchable
        return [
//            [['id'], 'integer'],
            [['locationName', 'user'], 'string'],
            [['date', 'date_range', 'date_start', 'date_end'], 'safe'],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => DateRangeBehavior::className(),
                'attribute' => 'date_range',
                'dateStartAttribute' => 'date_start',
                'dateEndAttribute' => 'date_end',
                'dateStartFormat' => 'Y-m-d',
                'dateEndFormat' => 'Y-m-d',

            ]
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        if(!$this->date_range && !$this->date_start && !$this->date_end){
            $dateStart = date('Y-m-01');
            $dateEnd = date('Y-m-t');
            $this->date_range = $dateStart . ' - ' . $dateEnd;
            $this->date_start = $dateStart;
            $this->date_end = $dateEnd;
        }

        $query = self::find();
        if (isset($params['locationName']) && $params['locationName']){
            $query->andFilterWhere(['locationName' => $params['locationName']]);
        }
        if (isset($params['userName']) && $params['userName']){
            $query->andFilterWhere(['user' => $params['userName']]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['date' => SORT_DESC, 'clock_in' => SORT_ASC]],
        ]);

        // load the search form data and validate
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        if ($this->date_start && $this->date_end) {
            $query->andFilterWhere(['between', 'date', $this->date_start, $this->date_end]);
        }

        // adjust the query by adding the filters
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['like', 'user', $this->user]);
        $query->andFilterWhere(['like', 'locationName', $this->locationName]);

        return $dataProvider;
    }

    public function searchIndex($params)
    {
        $query = self::find();
        $query->groupBy('locationName');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['date' => SORT_DESC, 'locationName' => SORT_ASC]],
        ]);

        // load the search form data and validate
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        // adjust the query by adding the filters
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['like', 'locationName', $this->locationName]);

        return $dataProvider;
    }

    public function searchUsers($params)
    {
        $query = self::find();
        $query->groupBy('user');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> ['defaultOrder' => ['date' => SORT_DESC, 'user' => SORT_ASC]],
        ]);

        // load the search form data and validate
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        // adjust the query by adding the filters
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['like', 'locationName', $this->locationName]);
        $query->andFilterWhere(['like', 'user', $this->user]);

        return $dataProvider;
    }

    public function autocompleteUser($params)
    {
        if(!$this->date_range && !$this->date_start && !$this->date_end){
            $dateStart = date('Y-m-01');
            $dateEnd = date('Y-m-t');
            $this->date_range = $dateStart . ' - ' . $dateEnd;
            $this->date_start = $dateStart;
            $this->date_end = $dateEnd;
        }

        $query = self::find()->select('user');
        if (isset($params['locationName']) && $params['locationName']){
            $query->andFilterWhere(['locationName' => $params['locationName']]);
        }

        if ($this->date_start && $this->date_end) {
            $query->andFilterWhere(['between', 'date', $this->date_start, $this->date_end]);
        }

        // adjust the query by adding the filters
        $query->andFilterWhere(['id' => $this->id]);
        $term = $params['term'] ?? '';
        $query->andFilterWhere(['like', 'user', $term]);
        $query->groupBy('user');

        return \yii\helpers\Json::encode($query->column());
    }

    public function autocompleteLocation($params)
    {
        if(!$this->date_range && !$this->date_start && !$this->date_end){
            $dateStart = date('Y-m-01');
            $dateEnd = date('Y-m-t');
            $this->date_range = $dateStart . ' - ' . $dateEnd;
            $this->date_start = $dateStart;
            $this->date_end = $dateEnd;
        }

        $query = self::find()->select('locationName');
        if (isset($params['userName']) && $params['userName']){
            $query->andFilterWhere(['user' => $params['userName']]);
        }

        if ($this->date_start && $this->date_end) {
            $query->andFilterWhere(['between', 'date', $this->date_start, $this->date_end]);
        }

        // adjust the query by adding the filters
        $query->andFilterWhere(['id' => $this->id]);
        $term = $params['term'] ?? '';
        $query->andFilterWhere(['like', 'locationName', $term]);
        $query->groupBy('locationName');

        return \yii\helpers\Json::encode($query->column());
    }

}