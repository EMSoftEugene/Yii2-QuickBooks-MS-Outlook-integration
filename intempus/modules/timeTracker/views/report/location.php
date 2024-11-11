<?php

/** @var yii\web\View $this */

use yii\jui\DatePicker;

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
                <h2>Location Report</h2>
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

                echo \yii\grid\GridView::widget([
                    'dataProvider' => $provider,
                    'filterModel' => $filter,
                    'columns' => [
                        [
                            'attribute' => 'location',
                            'format' => 'html',
                            'value' => function ($model) {
                                $model = (object)$model;
                                return '<a href="/time-tracker/report/location/'.$model->id.'">' . $model->location . '</a>    ';
                            },
                        ],
                    ],
                ]);
                ?>

            </div>
        </div>
    </div>
</div>
