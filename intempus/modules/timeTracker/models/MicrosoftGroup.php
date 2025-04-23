<?php

namespace app\modules\timeTracker\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * Microsoft Group model
 *
 * @property integer $id
 * @property string $name
 * @property string $microsoft_id
 * @property string $email
 * @property string $verizon_id
 * @property timestamp $created_at
 * @property timestamp $updated_at
 */
class MicrosoftGroup extends ActiveRecord
{
    public function rules()
    {
        return [
            [['name', 'microsoft_id', 'email'], 'string'],
            [['verizon_id'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%microsoft_group}}';
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

    public static function getTech()
    {
        return self::find()->filterWhere(['like', 'name', 'Tech'])->all();
    }

    public static function getTechNames()
    {
        $result = [];
        $tech = self::getTech();
        foreach ($tech as $key => $value) {
            $arr = [];
            $arr['first_name'] = $value->getFirstName();
            $arr['last_name'] = $value->getLastName();
            $result[] = $arr;
        }
        return $result;
    }

    public function getFirstName()
    {
        $explode = explode(" ", $this->name);
        return $explode[1] ?? '';
    }

    public function getLastName()
    {
        $explode = explode(" ", $this->name);
        return $explode[2] ?? '';
    }

    public static function getAvailable(): array
    {
        return self::find()
            ->where(['not', ['verizon_id' => null]])
            ->asArray()
            ->all();
    }

}