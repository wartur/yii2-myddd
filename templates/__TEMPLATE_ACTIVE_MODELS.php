<?php

namespace app\models\activeModels;

//use yii\behaviors\TimestampBehavior;

/**
 * __TEMPLATE
 */
class __TEMPLATE extends \app\models\activeRecords\__TEMPLATE
{
    // =================================================== МЕТОДЫ ПОВЕДЕНИЯ

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            TimestampBehavior::className(),
        ]);
    }

    // =================================================== РЕЛЯЦИИ AR
    // =================================================== ГЕТТЕРЫ И СЕТТЕРЫ
    // =================================================== МЕТОДЫ AR
    // =================================================== МЕТОДЫ
    // =================================================== МЕТОДЫ ИНТЕРФЕЙСОВ
}
