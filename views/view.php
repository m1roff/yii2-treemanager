<?php
/** @var $this View */
/** @var \mirkhamidov\treemanager\TreeManagerWidget $widget */
/** @var \yii\web\Session $session */

use mirkhamidov\treemanager\assets\Assets;
use rmrevin\yii\fontawesome\FA;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

Assets::register($this);

$widget = $this->context;

$this->registerJs('$("#' . $widget->pjaxId . '").on("pjax:timeout", function(event) {event.preventDefault();});', View::POS_READY);
?>
<?php Pjax::begin([
    'id' => $widget->pjaxId,
]) ?>
<?php
// Notification
if ($session->hasFlash($widget::SUCCESS_FLASH)) {
    $notifyData = [
        'type' => 'success',
        'text' => '<i class="fa fa-thumbs-o-up" aria-hidden="true"></i>&nbsp;'
            . $session->getFlash($widget::SUCCESS_FLASH)
    ];
    $this->registerJs('noty(' . Json::encode($notifyData) . ');', View::POS_READY);
}
if ($session->hasFlash($widget::FAIL_FLASH)) {
    $notifyData = [
        'type' => 'error',
        'text' => '<i class="fa fa-thumbs-o-down" aria-hidden="true"></i>&nbsp;'
            . $session->getFlash($widget::FAIL_FLASH)
    ];
    $this->registerJs('noty(' . Json::encode($notifyData) . ');', View::POS_READY);
}
// END Notification
?>
<div class="row">
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><?= $widget->title ?></h3>
            </div>
            <div class="panel-body">
            <?php foreach ($widget->data as $item) : ?>
                <div class="tree-item-carrier <?= ($widget->getIsItemActive($item) ? 'selectedit' : null) ?>">
                    <div class="btn-group pull-right" role="group" aria-label="...">
                    <?= Html::a(FA::icon('trash'), $widget->getItemDeleteLinkRoute($item), [
                        'class' => 'btn btn-default btn-sm',
                        'data' => [
                            'confirm' => 'Уверены?',
                            'pjax' => true,
                            'toggle' => 'tooltip',
                            'placement' => 'left',
                        ],
                        'title' => 'Удаление элемента без потомков. Потомки переместятся "вверх".',
                    ]) ?>

                    <?= Html::a(FA::icon('plus'), $widget->getItemChildLinkRoute($item), [
                        'class' => 'btn btn-default btn-sm',
                        'data' => [
                            'pjax' => true,
                            'toggle' => 'tooltip',
                            'placement' => 'left',
                        ],
                        'title' => 'Добавить нового потомка (в конец списка).',
                    ]) ?>

                    <?= Html::a(FA::icon('arrow-up'), $widget->getItemUpLinkRoute($item), [
                        'class' => 'btn btn-default btn-sm',
                        'data' => [
                            'pjax' => true,
                            'toggle' => 'tooltip',
                            'placement' => 'left',
                        ],
                        'title' => 'Перемещение вверх (с потомками)',
                    ]) ?>

                    <?= Html::a(FA::icon('arrow-down'), $widget->getItemDownLinkRoute($item), [
                        'class' => 'btn btn-default btn-sm',
                        'data' => [
                            'pjax' => true,
                            'toggle' => 'tooltip',
                            'placement' => 'right',
                        ],
                        'title' => 'Перемещение вниз (с потомками)',
                    ]) ?>
                    </div>


                        <?= Html::a(
                            $widget->getItemLinkLabel($item),
                            $widget->getItemEditLinkRoute($item),
                            [
                                'class' => 'btn tree-item btn-block ',
                                'data' => [
                                    'pjax' => true,
                                ],
                                'type' => 'button',
                            ]
                        ) ?>
                </div>
            <?php endforeach ?>
            </div>
            <div class="panel-footer">
                <div class="text-right">
                    <?= Html::a('Новый корневой', $widget->getItemNewLinkRoute(), [
                        'class' => 'btn btn-success',
                        'data' => [
                            'pjax' => true,
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">

        <?php if (!empty($widget->currentItem)) : ?>
            <?php
            if ($widget->getNeedRedirect()) {
                $formAction = $widget->getNeedRedirect();
            } else {
                $formAction = $widget->getCurrentUrl();
            }
            ?>
            <?php $form = ActiveForm::begin([
                'options' => [
                    'data' => [
                        'pjax' => true,
                    ],
                ],
                'action' => $formAction,
            ]) ?>
            <?php

            ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><?= $widget->currentItem->isNewRecord ? 'Добавление нового' : 'Редактирование' ?></h3>
                </div>
                <div class="panel-body">
                    <?= Html::errorSummary($widget->currentItem, ['class' => 'alert alert-danger']) ?>
                    <?= $form->field($widget->currentItem, 'name') ?>
                </div>
                <div class="panel-footer text-right">
                    <?= Html::a('Отмена', $widget->getItemCancelLinkRoute(), []) ?>
                    <?= Html::submitButton(
                        $widget->currentItem->isNewRecord ? 'Добавить' : 'Сохранить',
                        [
                            'class' => 'btn btn-' . ($widget->currentItem->isNewRecord ? 'success' : 'primary'),
                        ]
                    ) ?>
                </div>
            </div>
            <?php ActiveForm::end() ?>
        <?php endif ?>
    </div>
</div>

<?php Pjax::end() ?>