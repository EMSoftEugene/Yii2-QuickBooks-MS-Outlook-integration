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
 * @property string $name
 * @property string $date
 * @property string $access_token
 * @property string $refresh_token
 * @property string $expires_in
 * @property string $refresh_token_expires_in
 * @property string $realm_id
 * @property timestamp $created_at
 * @property timestamp $updated_at
 */
class AuthApi extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%api_auth}}';
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

    public static function getOrSetAuthApi(string $name): ?AuthApi
    {
        $authApi = self::findOne(['name' => $name]);
        if (!$authApi) {
            $authApi = new AuthApi();
            $authApi->name = $name;
            $authApi->save();
        }
        return $authApi;
    }

}