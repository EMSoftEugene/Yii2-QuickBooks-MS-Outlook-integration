<?php

namespace app\modules\timeTracker\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * Microsoft Location model
 *
 * @property integer $id
 * @property string $displayName
 * @property string $lat
 * @property string $lon
 * @property boolean $haul_away
 * @property string $date_time
 * @property string $microsoft_id
 * @property timestamp $created_at
 * @property timestamp $updated_at
 */
class MicrosoftLocation extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%microsoft_location}}';
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
        return self::find()->where(['displayName' => $locationName])->one();
    }

}