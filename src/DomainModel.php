<?php
/**
 * @author      Artur Krivtsov (gwartur) <gwartur@gmail.com> | Made in Russia
 * @copyright   Artur Krivtsov © 2020
 * @link        https://github.com/wartur/yii2-myddd
 * @license     BSD 3-Clause License
 */

namespace wartur\myddd;

/**
 * Базовый класс доменной Model.
 * Является прозрачной настройкой над Yii2 Model,
 * за исключением отключённого механизма сценариев.
 * 
 * Это набор удобных инструментов для работы с методологией MyDDD в Yii2
 * Во главе угла ставится удобное конфигурирование класса, которое вместе
 * с названием класса является удобной документацией.
 * Все инструменты за исключением сценариев надстроены над ядром Yii2
 * 
 * Пример оформления рабочего класса 
 * ```php
 * namespace app\models\someService;
 * 
 * class SomeOperationForm extends DomainModel
 * {
 *     const ERROR_SOMERROR = 'Какая-то ошибка';
 * 
 *     public $email;
 *     public $safeProperty;
 * 
 *     public function rules() {
 *         return [
 *             [['email', 'safeProperty'], 'required'],
 *             ['email', 'email'],
 *         ];
 *     }
 * 
 *     public function scenariosUno() {
 *         return ['email', '!safeProperty'];
 *     }
 * 
 *     public function execute () {
 *         if(!$this->validate()) {
 *             return false; // falseWithError не нужно, оно обрабатывается автоматически
 *         }
 *         
 *         // do somethink...
 * 
 *         if($someError) {
 *             return $this->falseWithError(self::ERROR_SOMERROR);
 *         }
 *         
 *         return $this->true('Это сообщние пойдет в Yii::info');
 *     }
 * 
 *     // считается, что класс должен выполнять одну единственную задачу,
 *     // но никто не мешает сюда напихать ещё кода, ибо форма может быть
 *     // одна но действия могут с ней быть разными,
 *     // поэтому не всегда надо дробить код при RAD
 * }
 * ```
 * 
 * Остальное читайте в документации к методологии MyDDD
 */
class DomainModel extends \yii\base\Model implements common\DomainInterface
{

    use common\DomainTrait;

    /**
     * Конфигурация единственного сценария SCENARIO_DEFAULT для ядра Yii2
     * 
     * ```php
     * // обычно при создании доменной модели для точного ограничения полей
     * return ['publicProperty', '!safeProperty'];
     * 
     * // в случае если доменная модель с наследованием от абстрактной
     * return array_merge(parent::scenariosUno(), ['publicProperty', '!safeProperty']);
     * ```
     * 
     * @return array
     */
    public function scenariosUno()
    {
        return parent::scenarios() [self::SCENARIO_DEFAULT];    // берем сценарии AR-модели по умолчанию
    }

    // =========================================================================
    // ПОДКАПОТНОЕ ПРОСТРАНСТВО
    // =========================================================================

    /**
     * Изменение поведения валидации
     * Добавлено дополнительное поведение через validateFailedDefaultBehavior
     * в случае неуспешной валидации по умолчанию добавлять ошибку в $this->domainLastError
     * 
     * validateFailedDefaultBehavior возможно переопределить,
     * например можно выкидывать исключение в случае неуспешной валидации
     * 
     * @param string[]|string $attributeNames attribute name or list of attribute names that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     * @param bool $clearErrors whether to call [[clearErrors()]] before performing validation
     * @return bool whether the validation is successful without any error.
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        if (parent::validate($attributeNames, $clearErrors)) {
            return true;
        } else {
            $this->validateFailedDefaultBehavior();
            return false;
        }
    }

    /**
     * Запрет системы сценариев. Оставляем только сценарий SCENARIO_DEFAULT, который требуется для ядра Yii2.
     * @return array сценарии Yii2 с одним возможным сценарием
     */
    public final function scenarios()
    {
        return [self::SCENARIO_DEFAULT => $this->scenariosUno()];
    }

}
