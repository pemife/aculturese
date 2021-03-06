<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\LugaresSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Lugares';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lugares-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Lugares', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [

            'lat',
            'lon',
            'nombre',   //añadir accion con enlace para ver en google maps 28/03

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
