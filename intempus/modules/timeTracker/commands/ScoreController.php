<?php

namespace app\modules\bonus\commands;

use Yii;
use yii\helpers\Console;
use yii\helpers\VarDumper;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

use app\modules\bonus\models\Score;
use app\modules\bonus\models\IncomePack;
use app\models\OrderStatus;

use app\job\exponea\SendBonusChangeInfoJob;
use app\job\exponea\SendBonusExpiresJob;

/**
 * Class ScheduleController
 * @package app\modules\subscription\components
 */
class ScoreController extends Controller
{
    /**
     * Каждый день после полуночи запускается скрипт, который списывает с
     * бонусных счетов клиентов суммы, у которых вчера была дата истечения
     */
    public function checkExpire()
    {
        // При этом должна быть проверка на активный заказ
        // Если у клиента есть активный заказ - списание бонусов за просрочку не происходит до момента перехода этого заказа в успешный или отмененный статус
        // Если текущий заказ отменен и нет другого активного заказа - сумма с истекшим сроком подлежит списанию по причине сгорания в момент отмены заказа
        // Если после учета в оплату заказа осталась часть бонусов с истекшим сроком - она списывается по причине сгорания в момент завершения заказа

        // находим все пачки с истёкшим сроком действия на которых остались бонусы
        $incomePacks = IncomePack::find()
                            ->where(['>', 'current_balance', 0 ])
                            ->andWhere(['<', 'expires_at', time()])
                            ->all();

        foreach ($incomePacks as $pack) {
            // проверяем есть ли у клиента активные заказы
            $avtiveOrdersCount = $pack->client->countOrders(['NOT', 'order.order_status', [OrderStatus::STATUS_CANCELED, OrderStatus::STATUS_DELIVERED]]);

            if ($avtiveOrdersCount > 0) {
                continue;
            }

            // списыва всё что осталось в пачке
            $pack->removeAllFunds();
        }
        echo "Done";
    }

    // Отправлять событие, когда до ближайшего сгорания бонусов осталось 30, 10, 2 дня.
    public function expireNotify()
    {
        // TODO Внимание: если в один день сгорает несколько отдельных начислений
        // - нужно присылать одно событие с просуммированным количеством сгораемых бонусов

        $plus30Days = (new \DateTime)->modify('+30 days');
        $plus30DaysBegin = $plus30Days->setTime(0, 0, 0)->getTimestamp();
        $plus30DaysEnd = $plus30Days->setTime(23, 59, 59)->getTimestamp();

        $incomePacks = IncomePack::find()
                            ->where(['>', 'current_balance', 0 ])
                            ->andWhere(['between', 'expires_at', $plus30DaysBegin, $plus30DaysEnd])
                            ->all();

        foreach ($incomePacks as $pack) {

            $eventInfo = [
                "notification_type" => "bonus_expired",
                "amount_expired_bonus" => $pack->current_balance,
                "left_days" => "30"
            ];

            Yii::$app->queue->push(new SendBonusExpiresJob([
                'eventInfo' => $eventInfo,
                'clientId' => $pack->scoreModel->client_id
            ]));
        }


        $plus10Days = (new \DateTime)->modify('+10 days');
        $plus10DaysBegin = $plus10Days->setTime(0, 0, 0)->getTimestamp();
        $plus10DaysEnd = $plus10Days->setTime(23, 59, 59)->getTimestamp();

        $incomePacks = IncomePack::find()
                            ->where(['>', 'current_balance', 0 ])
                            ->andWhere(['between', 'expires_at', $plus10DaysBegin, $plus10DaysEnd])
                            ->all();

        foreach ($incomePacks as $pack) {

            $eventInfo = [
                "notification_type" => "bonus_expired",
                "amount_expired_bonus" => $pack->current_balance,
                "left_days" => "10"
            ];

            Yii::$app->queue->push(new SendBonusExpiresJob([
                'eventInfo' => $eventInfo,
                'clientId' => $pack->scoreModel->client_id
            ]));
        }

        $plus2Days = (new \DateTime)->modify('+2 days');
        $plus2DaysBegin = $plus2Days->setTime(0, 0, 0)->getTimestamp();
        $plus2DaysEnd = $plus2Days->setTime(23, 59, 59)->getTimestamp();

        $incomePacks = IncomePack::find()
                            ->where(['>', 'current_balance', 0 ])
                            ->andWhere(['between', 'expires_at', $plus2DaysBegin, $plus2DaysEnd])
                            ->all();

        foreach ($incomePacks as $pack) {

            $eventInfo = [
                "notification_type" => "bonus_expired",
                "amount_expired_bonus" => $pack->current_balance,
                "left_days" => "2"
            ];

            Yii::$app->queue->push(new SendBonusExpiresJob([
                'eventInfo' => $eventInfo,
                'clientId' => $pack->scoreModel->client_id
            ]));
        }

        return true;

        // $incomePacks = IncomePack::find()
        //                     ->where(['>', 'current_balance', 0 ])
        //                     ->andWhere(['or',
        //                             ['between', 'expires_at', $plus30DaysBegin, $plus30DaysEnd]
        //                             ['between', 'expires_at', $plus10DaysBegin, $plus10DaysEnd]
        //                             ['between', 'expires_at', $plus2DaysBegin, $plus2DaysEnd]
        //                     ])
        //                     ->all();
    }


}
