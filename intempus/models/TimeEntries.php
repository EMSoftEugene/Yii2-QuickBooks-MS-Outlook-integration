<?php

namespace app\models;

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
 * @property integer $time_off_request_id
 * @property string $date
 * @property integer $duration
 * @property integer $approver_id
 * @property string $approver_last_name
 * @property string $approver_first_name
 * @property integer $user_id
 * @property string $user_last_name
 * @property string $user_first_name
 * @property string $timesheet_notes
 * @property string $location_addr
 * @property string $location_city
 * @property string $location_state
 * @property string $location_zip
 * @property string $location_country
 */
class TimeEntries extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%time_entries}}';
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

    public function getApprover(): string
    {
        return '(' . $this->approver_id . ') ' . trim($this->approver_last_name . ' ' . $this->approver_first_name);
    }

    public function getUser(): string
    {
        return '(' . $this->user_id . ') ' . trim($this->user_last_name . ' ' . $this->user_first_name);
    }

    public function getLocation(): string
    {
        $location = $this->location_addr ? $this->location_addr : '';
        $location .= $this->location_city ? ', ' . $this->location_city : '';
        $location .= $this->location_state ? ', ' . $this->location_state : '';
        $location .= $this->location_zip ? ', ' . $this->location_zip : '';
        $location .= $this->location_country ? ', ' . $this->location_country : '';
        return $location;
    }

    public function getDuration(): string
    {
        return gmdate("H:i", $this->duration);
    }

}