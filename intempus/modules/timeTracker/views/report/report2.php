<?php

/** @var yii\web\View $this */

use app\models\MicrosoftEvent;
use app\models\TimeEntries;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\jui\DatePicker;
use yii\widgets\ActiveForm;
use yii\widgets\ListView;

$this->title = 'Outlook Service';
?>
<style>
    .data-table th {
        background: #f4f4f4;
    }

    .data-table td, .data-table th {
        padding: 4px 12px;
        border: 1px solid #ccc;
    }
</style>
<div class="site-index">

    <div class="body-content" style="padding-top: 40px;">
        <div class="row">
            <div class="col-lg-12 mb-3">
                <h2>Report Locations</h2>
                <p>&nbsp;</p>
                <?php

                $saleTotal = [
                    0 => 0,
                    1 => 0
                ];
                foreach ($provider->models as $item) {
                    $explode = explode(':', $item['duration']);
                    $h = $explode[0];
                    $m = $explode[1];
                    if ($item['duration']){
                        $saleTotal[0] = $saleTotal[0] + (int)$h;
                        $saleTotal[1] = $saleTotal[1] + (int)$m;
                    }
                }
                $extraHours = round($saleTotal[1] / 60);
                $saleTotal[0] = $saleTotal[0] + $extraHours;
                $saleTotal[0] = str_pad($saleTotal[0], 2, 0, STR_PAD_LEFT);;
                $saleTotal[1] = $saleTotal[1] - $extraHours*60;
                $saleTotal[1] = str_pad($saleTotal[1], 2, 0, STR_PAD_LEFT);;


                echo \yii\grid\GridView::widget([
                    'dataProvider' => $provider,
                    'filterModel' => $filter,
                    'columns' => [
                        [
                            'attribute' => 'user',
                            'footer' => '<b>Total:</b>',
                            'footerOptions' => ['class' => 'text-right',],
                            'value' => 'user',
                        ],
                        'location',
                        [
                            'attribute'=>'date',
                            'label'=>'Date',
                            'format'=>'text',
                            'filter'=> '<div class="drp-container input-group"><span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>'.
                                DatePicker::widget([
                                    'name'  => 'Data2[date]',
                                ]) . '</div>',
                            'content'=>function($data){
                                return \Yii::$app->formatter->asDatetime($data['date'], "php:Y-m-d");
                            }
                        ],
                        [
                            'attribute' => 'duration',
                            'footer' => $saleTotal[0].':'.$saleTotal[1],
                            'footerOptions' => ['class' => 'text-right',],
                            'value' => 'duration',
                        ],
                    ],
                    'showFooter' => true,
                ]);
                ?>

            </div>
        </div>
    </div>
</div>
