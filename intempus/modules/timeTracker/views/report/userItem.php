<?php

/** @var yii\web\View $this */

use app\modules\timeTracker\models\MicrosoftLocation;
use app\modules\timeTracker\models\TimeTracker;
use app\modules\timeTracker\models\VehiclesHistory;
use kartik\form\ActiveForm;
use kartik\grid\GridView;
use yii\helpers\Html;
use kartik\daterange\DateRangePicker;

$this->title = 'Outlook Service';

use kartik\icons\Icon;
use yii\jui\AutoComplete;
use yii\web\JsExpression;

?>
<style>
    .data-table th {
        background: #f4f4f4;
    }

    .data-table td, .data-table th {
        padding: 4px 12px;
        border: 1px solid #ccc;
    }

    .dateFilter {
        float: left;
        width: 50%;
    }

    .submit.btn {
        float: left;
        display: inline-block;
    }

    .field-timetrackersearch-date_range {
        float: left;
        margin-right: 15px;
    }

    .field-timetrackersearch-date_range label {
        float: left;
        margin-right: 15px;
        padding-top: 7px;
    }

    .field-timetrackersearch-date_range .input-group {
        float: left;
        width: auto !important;
    }
    .toolbar-container { width: 100%; }
    .toolbar-container .btn-group { width: 100%; display: inline-block; }
    .toolbar-container h4 { float: right; }
    .outlook-logo {
        width: 25px;
        margin-right: 5px;
    }
</style>
<div class="site-index">

    <div class="body-content" style="">
        <div class="row">
            <div class="col-lg-12 mb-3">
                <table>
                    <tr>
                        <td style="padding-right: 15px; vertical-align: top; min-width: 140px;">
                            <h4 style="font-weight: normal;">
                                <a style="text-decoration: none;" href="/time-tracker/report/user"><?= Icon::show('caret-left', ['class' => 'fa-sm', 'title' => 'Technicans'], Icon::FA) ?>
                                </a>&nbsp;Technican:
                            </h4>

                        </td>
                        <td><h4><?= Html::encode($userName) ?></h4></td>
                    </tr>
                </table>
                <p>&nbsp;</p>
                <?php

                $form = ActiveForm::begin(['method' => 'get']);
                $model = new TimeTracker();

                $total = [
                    0 => 0,
                    1 => 0
                ];
                $data = $provider->models;
                foreach ($data as $item) {
                    $explode = explode(':', $item['duration']);
                    $h = $explode[0];
                    $m = $explode[1];
                    if ($item['duration']) {
                        $total[0] = $total[0] + (int)$h;
                        $total[1] = $total[1] + (int)$m;
                    }
                }

                $extraHours = floor($total[1] / 60);
                $total[0] = $total[0] + $extraHours;
                $total[1] = $total[1] - $extraHours * 60;
                $h = str_pad($total[0], 2, '0', STR_PAD_LEFT);
                $i = str_pad($total[1], 2, '0', STR_PAD_LEFT);



                echo \kartik\grid\GridView::widget([
                    'dataProvider' => $provider,
                    'filterModel' => $filter,
                    'panel' => [
                        'heading' => '<div class="dateFilter">' . $form->field($filter, 'date_range', [
                                'addon' => ['prepend' => ['content' => '<i class="fas fa-calendar-alt"></i>']],
                                'options' => ['class' => 'drp-container mb-2',],
                            ])->label('For period')->widget(DateRangePicker::classname(), [
                                'useWithAddon' => true,
                                'convertFormat' => true,
                                'startAttribute' => 'date_start',
                                'endAttribute' => 'date_end',
                                'pluginOptions' => [
                                    'locale' => [
                                        'format' => 'Y-m-d',
//                                        'separator' => ' to ',
                                    ],
                                ],
                            ]) . '<input type="submit" class="submit btn btn-primary" value="Search" /></div>',
//                        'type'=>'success',
                        'before' => '',
                        'after' => '',
                        'footer' => false
                    ],
                    'toolbar' => [
                        [
                            'content' =>
//                            <a class="btn btn-outline-primary"
//                                    href="/time-tracker/report/user-billable-consolidated/' . $id . '?' .
//                                'TimeTrackerSearch%5Bdate_range%5D=' . $filter->date_start. '+-+' . $filter->date_end .
//                                '&TimeTrackerSearch%5Bdate_start%5D=' . $filter->date_start . '&TimeTrackerSearch%5Bdate_end%5D=' . $filter->date_end .'">
//                                    Billable Total Report
//                                    </a>
                                '<div style="float: left;">
                                    
                                    <a class="btn btn-outline-primary" 
                                    href="/time-tracker/report/user-billable/' . $id . '?' .
                                    'TimeTrackerSearch%5Bdate_range%5D=' . $filter->date_start. '+-+' . $filter->date_end .
                                    '&TimeTrackerSearch%5Bdate_start%5D=' . $filter->date_start . '&TimeTrackerSearch%5Bdate_end%5D=' . $filter->date_end .'">
                                    Billable Report
                                    </a>
                                    
                                    </div>' .
                                '<h4 style="font-weight: normal; display: none;">Total hours for selected period: <b>' . $h . 'h ' . $i . 'm</b></h4>',
                        ],
                    ],
                    'columns' => [
                        [
                            'attribute' => 'date',
                            'enableSorting' => false,
                            'value' => function ($model, $key, $index, $widget) {
                                return (new \DateTime($model['date']))->format('F dS, Y');
                            },
                            'hidden' => !$data,
                            'group' => true,  // enable grouping,
                            'groupedRow' => true,                    // move grouped column to a single grouped row
                            'groupOddCssClass' => 'kv-grouped-row',  // configure odd group cell css class
                            'groupEvenCssClass' => 'kv-grouped-row', // configure even group cell css class
                        ],
                        [
                            'label' => 'Location',
                            'attribute' => 'locationName',
                            'enableSorting' => false,
                            'format'=>'html',
                            'value' => function ($model, $key, $index, $widget) {
                                $icon = $model->isMicrosoftLocation ?
                                    '<img class="outlook-logo" src="/images/outlook3.png" />' : '';
                                $url = $icon .' <a style="text-decoration:none;" href="/time-tracker/report/location/'.$model["id"].'">'.$model['locationName'].'</a>';

                                if ($model->isMicrosoftLocation && isset($model->locationNameVerizon) && $model->locationNameVerizon) {
                                    $microsoftLocation = MicrosoftLocation::getLocation($model['locationName']);
//                                    $url .= ' | lat: '.$microsoftLocation->lat . ', lon: '.$microsoftLocation->lon;
                                    $verizonLocation = VehiclesHistory::getLocation($model['locationNameVerizon']);
                                    $url .= ' <br/>(<span title="Verizon location">' . $model->locationNameVerizon . ')';
//                                        . ' | lat: '. $verizonLocation['Latitude'] .' , lon: '. $verizonLocation['Longitude'] .'</span>)';
                                    $vehiclesHistory = new VehiclesHistory();
//                                    $distance = $vehiclesHistory->distance($microsoftLocation->lat, $microsoftLocation->lon,$verizonLocation['Latitude'],$verizonLocation['Longitude']);
//                                    $url .= '<br/>Distance: '.round($distance) . 'm';
                                }

                                return $url;
                            },
                            'filter' => AutoComplete::widget([
                                'model' => $filter,
                                'attribute' => 'locationName',
                                'clientOptions' => [
                                    'source' => new JsExpression("function(request, response) {
                                        $.getJSON('" . Yii::$app->request->url . "', {
                                            term: request.term
                                        }, response);
                                    }"),
                                    'minLength' => '2',
                                ],
                                'options' => ['class' => 'form-control'],
                            ]),
                        ],
                        [
                            'label' => 'Clock In',
                            'attribute' => 'clock_in',
                            'enableSorting' => false,
                            'value' => function ($model, $key, $index, $widget) {
                                $y = (new \DateTime($model['clock_in']))->format('H:i:s');
                                $rounded = date('h:i A', round(strtotime($y)/60)*60);
                                if ($model->isMicrosoftLocation) {
                                    if(!isset($model->locationNameVerizon) || !$model->locationNameVerizon){
                                        return '';
                                    }
                                }
                                return $rounded;
                            },
                        ],
                        [
                            'label' => 'Clock Out',
                            'attribute' => 'clock_out',
                            'enableSorting' => false,
                            'value' => function ($model, $key, $index, $widget) {
                                $y = (new \DateTime($model['clock_out']))->format('H:i:s');
                                $rounded = date('h:i A', round(strtotime($y)/60)*60);
                                if ($model->isMicrosoftLocation) {
                                    if(!isset($model->locationNameVerizon) || !$model->locationNameVerizon){
                                        return '';
                                    }
                                }
                                return $rounded;
                            },
                        ],
                        [
                            'attribute' => 'duration',
                            'enableSorting' => false,
                            'value' => function ($model, $key, $index, $widget) {
                                if ($model->isMicrosoftLocation) {
                                    if(!isset($model->locationNameVerizon) || !$model->locationNameVerizon){
                                        return 'No GPS records';
                                    }
                                }
                                return $model['duration'];
                            },
                        ],
                    ],
                    'responsive' => true,
                    'hover' => true
                ]);

                ActiveForm::end();
                ?>

            </div>
        </div>
    </div>
</div>
