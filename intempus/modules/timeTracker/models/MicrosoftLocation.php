<?php

namespace app\modules\timeTracker\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property integer $id
 * @property string $displayName
 * @property string $lat
 * @property string $lon
 * @property boolean $haul_away
 * @property string $date_time
 * @property array $microsoft_id
 * @property timestamp $created_at
 * @property timestamp $updated_at
 */
class MicrosoftLocation extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%microsoft_location}}';
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
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
        return self::find()->where(['displayName' => $locationName])->one();
    }

    public function afterFind()
    {
        parent::afterFind();
        if (is_string($this->microsoft_id)) {
            $this->microsoft_id = json_decode($this->microsoft_id, true) ?: [];
        }
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (!is_array($this->microsoft_id)) {
            $this->microsoft_id = [];
        }

        $this->microsoft_id = array_values(array_unique(array_filter($this->microsoft_id)));

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (is_string($this->microsoft_id)) {
            $this->microsoft_id = json_decode($this->microsoft_id, true) ?: [];
        }
    }

    public function addMicrosoftId($id): void
    {
        if (empty($id)) {
            return;
        }

        $ids = is_string($this->microsoft_id) ?
            json_decode($this->microsoft_id, true) :
            (array)$this->microsoft_id;

        if (!in_array($id, $ids)) {
            $ids[] = $id;
            $this->microsoft_id = $ids;
        }
    }
}