<?php

namespace app\models;

use yii\base\Model;

/**
 * Our data model extends yii\base\Model class so we can get easy to use and yet
 * powerful Yii 2 validation mechanism.
 */
class Data2 extends Model
{
    /**
     * We plan to get two columns in our grid that can be filtered.
     * Add more if required. You don't have to add all of them.
     */
    public $location;
    public $user;
    public $duration;
    public $date;

    /**
     * Here we can define validation rules for each filtered column.
     * See http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     * for more information about validation.
     */
    public function rules()
    {
        return [
            [['user', 'date'], 'string'],
            // our columns are just simple string, nothing fancy
        ];
    }

    /**
     * In this example we keep this special property to know if columns should be
     * filtered or not. See search() method below.
     */
    private $_filtered = false;

    /**
     * This method returns ArrayDataProvider.
     * Filtered and sorted if required.
     */
    public function search($params)
    {
        /**
         * $params is the array of GET parameters passed in the actionExample().
         * These are being loaded and validated.
         * If validation is successful _filtered property is set to true to prepare
         * data source. If not - data source is displayed without any filtering.
         */
        if ($this->load($params) && $this->validate()) {
            $this->_filtered = true;
        }

        return new \yii\data\ArrayDataProvider([
            // ArrayDataProvider here takes the actual data source
            'allModels' => $this->getData(),
            'sort' => [
                // we want our columns to be sortable:
                'attributes' => ['location', 'user', 'duration', 'date'],
            ],
        ]);
    }

    /**
     * Here we are preparing the data source and applying the filters
     * if _filtered property is set to true.
     */
    protected function getData()
    {
        $data = [
            ['location' => '9600 Fairway Dr, Roseville, CA, 95678-3548, US', 'user' => '(1794830) IT Eugene', 'duration' => '02:00', 'date' => '2024-10-22'],
            ['location' => '9600 Fairway Dr, Roseville, CA, 95678-3548, US', 'user' => '(1770430) Guzman Alejandro', 'duration' => '01:00', 'date' => '2024-10-22'],
            ['location' => '9600 Fairway Dr, Roseville, CA, 95678-3548, US', 'user' => '(1590442) Medrano Carlos', 'duration' => '0:15', 'date' => '2024-10-22'],
            ['location' => '977 Bellomy, Santa Clara, CA, 95050', 'user' => '(1640788) Olshko Nikolay', 'duration' => '08:00', 'date' => '2024-10-24'],
            ['location' => '92 Rancho Dr Unit F San Jose, CA 95111', 'user' => '(1763700) Lopez Edgar', 'duration' => '04:53', 'date' => '2024-10-23'],
            ['location' => '92 Rancho Dr Unit F San Jose, CA 95111', 'user' => '(1794830) IT Eugene	', 'duration' => '02:03', 'date' => '2024-10-22'],
        ];

        if ($this->_filtered) {
            $data = array_filter($data, function ($value) {
                $conditions = [true];
                if (!empty($this->location)) {
                    $conditions[] = strpos($value['location'], $this->location) !== false;
                }
                if (!empty($this->user)) {
                    $conditions[] = strpos($value['user'], $this->user) !== false;
                }
                if (!empty($this->date)) {
                    $conditions[] = strpos(\Yii::$app->formatter->asDatetime($value['date'], "php:Y-m-d"), \Yii::$app->formatter->asDatetime($this->date, "php:Y-m-d")) !== false;
                }
                return array_product($conditions);
            });
        }

        return $data;
    }
}