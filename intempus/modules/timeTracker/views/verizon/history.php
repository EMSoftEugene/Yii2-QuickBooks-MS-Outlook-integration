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
    .data-table th {
        background: #f4f4f4;
    }

    .data-table td, .data-table th {
        padding: 4px 12px;
        border: 1px solid #ccc;
    }

    .outlook-logo {
        width: 25px;
        margin-right: 5px;
    }
</style>
<div class="site-index">

    <div class="body-content">
        <div class="row">
            <div class="col-lg-12 mb-3">
                <table>
                    <tr>
                        <td style="padding-right: 15px; vertical-align: top;"><h4 style="font-weight: normal;">Verizon History
                            </h4></td>
                        <td><h4></h4></td>
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
//                        'footer' => false
                    ],
                    'toolbar' => false,
                    'columns' => [
                        [
                            'label' => 'UpdateUtc',
                            'attribute' => 'UpdateUtc',
                            'enableSorting' => false,
                            'value' => function ($model) {
                                return $model->UpdateUtc;
                            },
                        ],
                        [
                            'label' => 'location',
                            'attribute' => 'location',
                            'enableSorting' => false,
                            'value' => function ($model) {
                                return $model->location;
                            },
                        ],
                    ],
                ]);
                ?>

            </div>
        </div>
    </div>
</div>
