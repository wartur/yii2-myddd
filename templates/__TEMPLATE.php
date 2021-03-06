<?php

namespace app\models\activeDomains\__TEMPLATE_NAMESPACE;

/**
 * __TEMPLATE
 */
class __TEMPLATE extends \app\models\activeModels\__TEMPLATE_PARENT_MODEL
{
    // =================================================== МЕТОДЫ ПОВЕДЕНИЯ

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
                //'name' => 'Название',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function scenariosUno()
    {
        return [];
        //return array_merge(parent::scenariosUno(), []);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge([], parent::rules(), [
                //['name', 'string']
        ]);
    }

    // =================================================== СВЯЗИ AR
    // =================================================== ГЕТТЕРЫ И СЕТТЕРЫ
    // =================================================== МЕТОДЫ AR

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
    }

    // =================================================== МЕТОДЫ
    // =================================================== МЕТОДЫ ИНТЕРФЕЙСОВ
}
