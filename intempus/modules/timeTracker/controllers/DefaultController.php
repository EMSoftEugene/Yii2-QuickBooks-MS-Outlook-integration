<?php

namespace app\modules\timeTracker\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
/**
 */
class DefaultController extends \yii\web\Controller
{
    public string $defaultRoute = 'default';


	public function behaviors()
    {
        return [
            'access' => [
				'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ]
            ],
        ];
    }
    /**
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->redirect('/time-tracker/report/location');
        return $this->render('index');
    }

}
