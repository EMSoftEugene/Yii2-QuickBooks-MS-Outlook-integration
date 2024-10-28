<?php

/** @var yii\web\View $this */

use app\models\MicrosoftEvent;
use app\models\TimeEntries;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\ListView;

$this->title = 'Outlook Service';
$timeEntries = TimeEntries::find();
$outlookLocations = MicrosoftEvent::find();
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

                <?php $form = ActiveForm::begin(['action' => '/tsheet/time-entries']) ?>

                <?= \yii\jui\DatePicker::widget([
                    'name'  => 'date',
                    'value' => date('Y-m-d'),
                    'dateFormat' => 'yyyy-MM-dd',
                ]); ?>

                <div>&nbsp;</div>
                <?= Html::submitButton('Refresh Time Emtries', ['class' => 'btn btn-primary', 'id' => 'save']) ?>
                <?php ActiveForm::end() ?>

                <p>&nbsp;</p>

                <h2>Time Entries</h2>
                <?php
                $timeEntriesProvider = new ActiveDataProvider([
                    'query' => $timeEntries,
                    'pagination' => [
                        'pageSize' => 10,
                    ],
                ]);
                $timeEntriesProvider->pagination->pageParam = 'time-page';
                $timeEntriesProvider->sort->sortParam = 'time-sort';

                echo GridView::widget([
                    'dataProvider' => $timeEntriesProvider,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'label' => 'External ID',
                            'attribute' => 'time_off_request_id',
                        ],
                        [
                            'label' => 'Date',
                            'attribute' => 'date',
                        ],
                        [
                            'label' => 'Duration',
                            'value' => function ($data) {
                                return $data->getDuration();
                            },
                        ],
                        [
                            'label' => 'Approver',
                            'value' => function ($data) {
                                return $data->getApprover();
                            },
                        ],
                        [
                            'label' => 'User',
                            'value' => function ($data) {
                                return $data->getUser();
                            },
                        ],
                        [
                            'label' => 'Timesheet Notes',
                            'attribute' => 'timesheet_notes',
                        ],
                        [
                            'label' => 'Location',
                            'value' => function ($data) {
                                return $data->getLocation();
                            },
                        ],
                    ],
                ]);
                ?>

                <p>&nbsp;</p>
                <hr/>
                <br/>

                <a href="/microsoft" class="btn btn-primary">Refresh Outlook Locations</a>
                <h2>Outlook Locations</h2>
                <?php
                $outlookProvider = new ActiveDataProvider([
                    'query' => $outlookLocations,
                    'pagination' => [
                        'pageSize' => 50,
                    ],
                ]);

                $outlookProvider->pagination->pageParam = 'outlook-page';
                $outlookProvider->sort->sortParam = 'outlook-sort';
                echo GridView::widget([
                    'dataProvider' => $outlookProvider,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'label' => 'Location',
                            'attribute' => 'location',
                        ],
                    ],
                ]);
                ?>
            </div>
        </div>
    </div>
</div>
