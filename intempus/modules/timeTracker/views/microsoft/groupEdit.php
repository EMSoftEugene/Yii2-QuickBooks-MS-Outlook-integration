<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Post */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="post-form" style="max-width: 400px;">

    <p>
        Name: <b><?php echo $model->name ?></b><br/>
        Email: <b><?php echo $model->email ?></b>
    </p>


    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'verizon_id')->textInput(['type' => 'number']) ?>

    <br/><br/>
    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>


    <?php ActiveForm::end(); ?>

</div>