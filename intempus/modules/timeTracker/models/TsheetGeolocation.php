<?php

namespace app\modules\timeTracker\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * Geolocation model
 *
 * @property integer $id
 * @property integer $tsheet_user_id
 * @property integer $tsheet_id
 * @property string $lat
 * @property string $lon
 * @property string $converted_location
 * @property string $speed
 * @property timestamp $tsheet_created
 *
 * @property timestamp $created_at
 * @property timestamp $updated_at
 */
class TsheetGeolocation extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tsheet_geolocation}}';
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