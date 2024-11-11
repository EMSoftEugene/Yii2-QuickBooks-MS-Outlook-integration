<?php

/** @var yii\web\View $this */

use app\models\MicrosoftEvent;
use app\models\TimeEntries;
use yii\data\ActiveDataProvider;
use kartik\grid\GridView;
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
                <a href="/time-tracker/report/location">Locations</a>
                <h2>Location: </h2>
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
                    if ($item['duration']) {
                        $saleTotal[0] = $saleTotal[0] + (int)$h;
                        $saleTotal[1] = $saleTotal[1] + (int)$m;
                    }
                }
                $extraHours = round($saleTotal[1] / 60);
                $saleTotal[0] = $saleTotal[0] + $extraHours;
                $saleTotal[1] = $saleTotal[1] - $extraHours * 60;

                echo \kartik\grid\GridView::widget([
                    'dataProvider' => $provider,
                    'filterModel' => $filter,
                    'columns' => [
                        [
                            'attribute' => 'id',
                            'width' => '310px',
                            'value' => function ($model, $key, $index, $widget) {
                                return $model['date'];
                            },
                            'filterType' => GridView::FILTER_SELECT2,
                            'filter' => [
                                    ['id' => 1, 'date' => '2024-10-22'],
                                    ['id' => 3, 'date' => '2024-10-22'],
                                    ['id' => 2, 'date' => '2024-10-23'],
                            ],
//                            'filter' => ArrayHelper::map(Suppliers::find()->orderBy('company_name')->asArray()->all(), 'id', 'company_name'),
                            'filterWidgetOptions' => [
                                'pluginOptions' => ['allowClear' => true],
                            ],
                            'filterInputOptions' => ['placeholder' => 'Any supplier'],
                            'group' => true,  // enable grouping,
                            'groupedRow' => true,                    // move grouped column to a single grouped row
                            'groupOddCssClass' => 'kv-grouped-row',  // configure odd group cell css class
                            'groupEvenCssClass' => 'kv-grouped-row', // configure even group cell css class
                        ],
                        [
                            'label' => 'Tech Name',
                            'attribute' => 'user',
                            'value' => 'user',
                            'group' => true,  // enable grouping,
                        ],
                        [
                            'label' => 'Clock In',
                            'attribute' => 'clock_in',
                            'value' => 'clock_in',
                            'group' => true,  // enable grouping,
                        ],
                        [
                            'label' => 'Clock Out',
                            'attribute' => 'clock_out',
                            'value' => 'clock_out',
                            'group' => true,  // enable grouping,
                        ],
                        [
                            'attribute' => 'duration',
                            'value' => 'duration',
                            'group' => true,  // enable grouping,
                        ],
                    ],

                    'responsive' => true,
                    'hover' => true
                ]);


//                echo \yii\grid\GridView::widget([
//                    'dataProvider' => $provider,
//                    'filterModel' => $filter,
//                    'columns' => [
//                        'location', 'date',
//                        [
//                            'label' => 'Tech Name',
//                            'attribute' => 'user',
//                            'footer' => '<b>Total hours for selected period:</b>',
//                            'footerOptions' => ['class' => 'text-right',],
//                            'value' => 'user',
//                        ],
//                        [
//                            'label' => 'Clock In',
//                            'attribute' => 'clock_in',
//                            'footer' => '',
//                            'footerOptions' => ['style' => 'border-left:0px !important;',],
//                            'value' => 'clock_in',
//                        ],
//                        [
//                            'label' => 'Clock Out',
//                            'attribute' => 'clock_out',
//                            'footer' => '',
//                            'value' => 'clock_out',
//                        ],
//                        [
//                            'attribute' => 'duration',
//                            'footer' => $saleTotal[0] . ':' . $saleTotal[1],
//                            'footerOptions' => ['class' => 'text-right',],
//                            'value' => 'duration',
//                        ],
//                    ],
//                    'showFooter' => true,
//                ]);
                ?>

            </div>
        </div>
    </div>
</div>
