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
<!--                            <h4 style="font-weight: normal;">-->
<!--                                <a style="text-decoration: none;"-->
<!--                                   href="/time-tracker/report/user/--><?php //= $id; ?><!--">--><?php //= Icon::show('caret-left', ['class' => 'fa-sm', 'title' => 'Microsoft Outlook Location'], Icon::FA) ?>
<!--                                </a>&nbsp;Technican:-->
<!--                            </h4>-->

                        </td>
<!--                        <td><h4>--><?php //= Html::encode($userName) ?><!--</h4></td>-->
                    </tr>
                </table>
                <p>&nbsp;</p>
                <p>Raw Tsheets Users:</p>
                <table class="kv-grid-table table table-bordered table-striped kv-table-wrap">
                    <tr>
                        <td>
                            <pre><?php print_r($data); ?></pre>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
