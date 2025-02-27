<?php

/** @var yii\web\View $this */

use app\modules\timeTracker\models\TimeTracker;
use kartik\form\ActiveForm;
use kartik\grid\GridView;
use yii\bootstrap5\Modal;
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

        .toolbar-container {
            width: 100%;
        }

        .toolbar-container .btn-group {
            width: 100%;
            display: inline-block;
        }

        .toolbar-container h4 {
            float: right;
        }

        .outlook-logo {
            width: 25px;
            margin-right: 5px;
        }

        .rule {
            cursor: pointer;
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
                                    <a style="text-decoration: none;" href="/time-tracker/report/user"><?= Icon::show(
                                            'caret-left',
                                            ['class' => 'fa-sm', 'title' => 'Technicans'],
                                            Icon::FA
                                        ) ?>
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
                        $explode = explode(':', $item['rule4']);
                        $h = $explode[0];
                        $m = $explode[1];
                        if ($item['rule4']) {
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
                                    '<div style="float: left;">
                                    &nbsp;
                                    </div>' .
                                    '<h4 style="font-weight: normal;">Total hours for selected period: <b>' . $h . 'h ' . $i . 'm</b></h4>',
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
                                'groupFooter' => function ($model, $key, $index, $widget) use ($totalDay, $totalDay0
                                ) { // Closure method
                                    return [
//                                    'mergeColumns' => [[1,3]], // columns to merge in summary
                                        'content' => [             // content to show in each summary cell
                                            1 => 'Total:',
                                            9 => '<div data-name="' . $model['date'] . '" class="total_date0" style="cursor: pointer;" 
                                            data-content-id="' . $model['date'] . 'z2' . '"
                                            >' . $totalDay0[$model['date']] . '</div>',
                                            10 => '<div data-name="' . $model['date'] . '" class="total_date" style="cursor: pointer;" 
                                            data-content-id="' . $model['date'] . 'z' . '"
                                            >' . $totalDay[$model['date']] . '</div>',
                                        ],
//                                    'contentFormats' => [      // content reformatting for each summary cell
//                                        4 => ['format' => 'number', 'decimals' => 2],
//                                        8 => ['format' => 'number', 'decimals' => 2],
//                                    ],
//                                    'contentOptions' => [      // content html attributes for each summary cell
//                                        1 => ['style' => 'font-variant:small-caps'],
//                                        8 => ['style' => 'text-align:right'],
//                                    ],
                                        // html attributes for group summary row
                                        'options' => ['class' => 'info table-info', 'style' => 'font-weight:bold;']
                                    ];
                                }
                            ],
                            [
                                'label' => '#',
//                                'attribute' => 'clock_in',
                                'enableSorting' => false,
                                'value' => function ($model, $key, $index, $widget) {
                                    return 'L' . $model['L'];
                                },
                            ],
                            [
                                'label' => 'Location',
                                'attribute' => 'locationName',
                                'enableSorting' => false,
                                'format' => 'html',
                                'value' => function ($model, $key, $index, $widget) {
                                    $icon = $model['isMicrosoftLocation'] ?
                                        '<img class="outlook-logo" src="/images/outlook3.png" />' : '';
                                    return $icon . ' <a style="text-decoration:none;" href="/time-tracker/report/location/' . $model["id"] . '">' . $model['locationName'] . '</a>';
                                },
                                'filter' => AutoComplete::widget([
                                    'model' => $filter,
                                    'attribute' => 'locationName',
                                    'clientOptions' => [
                                        'source' => new JsExpression(
                                            "function(request, response) {
                                        $.getJSON('" . Yii::$app->request->url . "', {
                                            term: request.term
                                        }, response);
                                    }"
                                        ),
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
                                    $rounded = date('h:i A', round(strtotime($y) / 60) * 60);
                                    return $rounded;
                                },
                            ],
                            [
                                'label' => 'Clock Out',
                                'attribute' => 'clock_out',
                                'enableSorting' => false,
                                'value' => function ($model, $key, $index, $widget) {
                                    $y = (new \DateTime($model['clock_out']))->format('H:i:s');
                                    $rounded = date('h:i A', round(strtotime($y) / 60) * 60);
                                    return $rounded;
                                },
                            ],
                            [
                                'attribute' => 'duration',
                                'enableSorting' => false,
                                'value' => 'duration',
                            ],
                            [
                                'label' => 'Rule 1',
                                'enableSorting' => false,
                                'format' => 'raw',
                                'value' => function ($model, $key, $index, $widget) {
                                    $value = $model['rule1'] ?: '';
                                    $desc = $model['rule1_desc'] ?: '';
                                    $id = $model['id'] . 'rule1';

                                    return '<span class="rule" data-name="Rule 1" data-content-id="' . $id . '">'
                                        . $value . '</span>'
                                        . '<div style="display:none;" id="' . $id . '">' . $desc . '</div>';
                                },
                            ],
                            [
                                'label' => 'Rule 2',
                                'enableSorting' => false,
                                'format' => 'raw',
                                'value' => function ($model, $key, $index, $widget) {
                                    $value = $model['rule2'] ?: '';
                                    $desc = $model['rule2_desc'] ?: '';
                                    $id = $model['id'] . 'rule2';

                                    return '<span class="rule" data-name="Rule 2" data-content-id="' . $id . '">'
                                        . $value . '</span>'
                                        . '<div style="display:none;" id="' . $id . '">' . $desc . '</div>';
                                },
                            ],
                            [
                                'label' => 'Rule 3',
                                'enableSorting' => false,
                                'format' => 'raw',
                                'value' => function ($model, $key, $index, $widget) {
                                    $value = $model['rule3'] ?: '';
                                    $desc = $model['rule3_desc'] ?: '';
                                    $id = $model['id'] . 'rule3';

                                    return '<span class="rule" data-name="Rule 3" data-content-id="' . $id . '">'
                                        . $value . '</span>'
                                        . '<div style="display:none;" id="' . $id . '">' . $desc . '</div>';
                                },
                            ],
                            [
                                'label' => 'Rule 4',
                                'enableSorting' => false,
                                'format' => 'raw',
                                'pageSummary' => true,
                                'value' => function ($model, $key, $index, $widget) {
                                    $value = $model['rule4'] ?: '';
                                    $desc = $model['rule4_desc'] ?: '';
                                    $id = $model['id'] . 'rule4';

                                    return '<span class="rule" data-name="Rule 4" data-content-id="' . $id . '">'
                                        . $value . '</span>'
                                        . '<div style="display:none;" id="' . $id . '">' . $desc . '</div>';
                                },
                            ],
                            [
                                'label' => 'Rule 5',
                                'enableSorting' => false,
                                'format' => 'raw',
                                'pageSummary' => true,
                                'value' => function ($model, $key, $index, $widget) {
                                    return '';
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

            <?php
            $i = 1;
            foreach ($formula as $key => $value): $i++; ?>
                <div id="<?= $key . 'z' ?>" style="display: none;" class="extendme">
                    <div>
                        <?= $value[1] ?>
                    </div>
                </div>
                <div id="<?= $key . 'z2' ?>" style="display: none;">
                    <div>
                        <?= $value[2] ?>
                    </div>
                </div>
            <?php
            endforeach; ?>
            <?php
            Modal::begin([
                'id' => 'helperModal',
                'title' => '<h4 id="helperModalTitle">Hello world</h4>',
            ]);
            echo '<div style="cursor: pointer" id="helperModalContent"></div><br/><br/><div style="display: none;" id="helperModalContent2"></div>';
            Modal::end();
            ?>
        </div>
    </div>

<?php
$script = <<< JS
    $(function() {
        let modalEl = $('#helperModal');
        let modalTitle = $('#helperModalTitle');
        let modalContent = $('#helperModalContent');
        let modalContent2 = $('#helperModalContent2');
        $('.total_date').click(function() {
          let name = $(this).attr('data-name');
          let id = $(this).attr('data-content-id');
          let content = $('#'+id).html();
          modalTitle.html(name);
          modalContent.html(content);
          
          let id2 = $(this).attr('data-content-id2');
          let content2 = $('#'+id2).html();
          modalContent2.html(content2);

          modalEl.modal('toggle');
        });
        $('.total_date0').click(function() {
          let name = $(this).attr('data-name');
          let id = $(this).attr('data-content-id');
          let content = $('#'+id).html();
          modalTitle.html(name);
          modalContent.html(content);
          
          let id2 = $(this).attr('data-content-id2');
          let content2 = $('#'+id2).html();
          modalContent2.html(content2);

          modalEl.modal('toggle');
        });
        // $('#helperModalContent').click(function() {
        //   modalContent2.show();
        // });
        // modalEl.on('hidden.bs.modal', function () {
        //    modalContent2.hide();
        // });
        
        $('.rule').click(function() {
          let name = $(this).attr('data-name');
          let id = $(this).attr('data-content-id');
          let content = $('#'+id).html();
          modalTitle.html(name);
          modalContent.html(content);
          modalEl.modal('toggle');
        });
  });

JS;
$this->registerJs($script);
?>