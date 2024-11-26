<?php

/** @var yii\web\View $this */

use app\modules\timeTracker\models\TimeTracker;
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
</style>
<div class="site-index">

    <div class="body-content" style="">
        <div class="row">
            <div class="col-lg-12 mb-3">
                <table>
                    <tr>
                        <td style="padding-right: 15px; vertical-align: top; min-width: 140px;">
                            <h4 style="font-weight: normal;">
                                <a style="text-decoration: none;"
                                   href="/time-tracker/report/user/<?= $id; ?>"><?= Icon::show('caret-left', ['class' => 'fa-sm', 'title' => 'Microsoft Outlook Location'], Icon::FA) ?>
                                </a>&nbsp;Technican:
                            </h4>

                        </td>
                        <td><h4><?= Html::encode($userName) ?></h4></td>
                    </tr>
                </table>
                <p>&nbsp;</p>
                <?php

                echo \kartik\grid\GridView::widget([
                    'dataProvider' => $provider,
                    'filterModel' => $filter,
                    'panel' => [
                        'heading' => '<h3 class="panel-title"></h3>',
//                        'type'=>'success',
                        'before' => '',
                        'after' => '',
                        'footer' => false
                    ],
                    'toolbar' => false,
                    'columns' => [
                        [
                            'label' => 'Converted Location',
                            'attribute' => 'converted_location',
                            'enableSorting' => false,
                            'value' => function ($model) {
                                return $model->converted_location ?? '';
                            },
                        ],
                        [
                            'label' => 'Lattitude',
                            'attribute' => 'lat',
                            'enableSorting' => false,
                            'value' => function ($model) {
                                return $model->lat;
                            },
                        ],
                        [
                            'label' => 'Longitude',
                            'attribute' => 'lon',
                            'enableSorting' => false,
                            'value' => function ($model) {
                                return $model->lon;
                            },
                        ],
                        [
                            'label' => 'DateTime',
                            'attribute' => 'lon',
                            'filter' => false,
                            'enableSorting' => false,
                            'value' => function ($model) {
                                return $model->tsheet_created;
                            },
                        ],
                    ],
                ]);
                ?>
            </div>
        </div>
    </div>
</div>
