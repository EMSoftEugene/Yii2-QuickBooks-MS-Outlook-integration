<?php
namespace app\modules\timeTracker\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * Time Tracker model
 *
 * @property integer $id
 * @property boolean $isMicrosoftLocation
 * @property string $locationName
 * @property string $locationNameVerizon
 * @property date $date
 * @property time $clock_in
 * @property time $clock_out
 * @property string $duration
 * @property integer $user_id
 * @property string $user
 * @property boolean $haul_away
 *
 * @property timestamp $created_at
 * @property timestamp $updated_at
 */
class TimeTracker extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%time_tracker}}';
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