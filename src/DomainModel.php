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
 * Это набор удобных инструментов для работы по методике MyDDD в Yii2
 * Во главе угла ставится удобное конфигурирование класса, которое вместе
 * с названием класса является удобной документацией.
 * Все инструменты за исключением сценариев надстроены над ядром Yii2
 * 
 * Считается, что класс должен выполнять одну единственную задачу "execute"
 * 
 * Пример оформления рабочего класса 
 * ```php
 * namespace app\models\frontend\someService;
 * 
 * class SomeOperationForm extends DomainModel
 * {
 *     const ERROR_SOMERROR = 'Какая-то ошибка';
 * 
 *     public $email;
 *     public $safeProperty;
 *
 *     public scenariosBackend() {
 *         return ['safeProperty'];
 *     }
 * 
 *     public scenariosFrontend() {
 *         return ['email'];
 *     }
 * 
 *     public function rules() {
 *         return [
 *             [['email', 'safeProperty'], 'required'],
 *             ['email', 'email'],
 *         ];
 *     }
 * 
 *     public function execute() {
 *         if(!$this->execute()) {
 *             return false;
 *         }
 *         
 *         // do somethink...
 * 
 *         if($someError) {
 *             return $this->falseWithError(self::ERROR_SOMERROR);
 *         }
 *         
 *         return true;
 *     }
 * }
 * ```
 * 
 * Остальное читайте в документации по методике MyDDD
 */
class DomainModel extends \yii\base\Model
{

    use common\DomainTrait;

    /**
     * Выполнить действие класса
     * 
     * Один класс может делать одно действие. Этот метод реализовывает это действие.
     * 
     * Валидация модели происходит ВСЕГДА в зависимости от правил валидации этой модели.
     * 
     * Если unsafe параметры имеют ошибки валидации, то выбросится исключение InvalidConfigException
     * 
     * @return null (void) ничего не должна возвращать
     */
    public function execute()
    {
        if (!$this->validate()) {
            return false;
        }

        return true;
    }

    /**
     * Выполнить или выкинуть исключение
     * 
     * Удобный синтаксический сахар
     * @param string $message Информация об ошибке
     * @param type $className Название класса исключения
     * @throws \yii\base\Exception
     */
    public function executeOrException($message = 'Ошибка выполнения', $className = \yii\base\Exception::class)
    {
        if ($this->execute()) {
            return true;
        }

        if ($message == 'Ошибка сохранения') {
            $reflect = new \ReflectionClass($this);
            $message .= ' ' . $reflect->getShortName();
        }
        throw new $className($message);
    }

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
     * ВАЖНО:
     * Вместо этого метода рекомендуется пользоваться
     * - scenariosBackend
     * - scenariosFrontend
     * Их можно в дальнейшем наследовать
     * 
     * @return array
     */
    public function scenariosUno()
    {
        return $this->domainScenariosUno();
    }

    /**
     * UNSAFE аттрибуты для бэкенд моделей
     * 
     * У аттрибутов НЕ НУЖНО ставить восклицательный знак "!" для признака unsafe
     * библиотека MyDDD самостоятельно приведёт к совместимости Yii2
     * 
     * @return array
     */
    public function scenariosBackend()
    {
        return [];
    }

    /**
     * SAFE аттрибуты для фроетенд моделей.
     * 
     * Работает так же как будто это был бы scenariosUno
     * Сюда хоть и можно добавлять восклицательный знак "!" для признака unsafe,
     * но делать это крайне не рекомендуется - этого не оценят будущие поколения
     * 
     * @return array
     */
    public function scenariosFrontend()
    {
        return [];
    }

    // =========================================================================
    // ПОДКАПОТНОЕ ПРОСТРАНСТВО
    // =========================================================================

    /**
     * Запрет системы сценариев. Оставляем только сценарий SCENARIO_DEFAULT, который требуется для ядра Yii2.
     * @return array сценарии Yii2 с одним возможным сценарием
     */
    public final function scenarios()
    {
        return [self::SCENARIO_DEFAULT => $this->scenariosUno()];
    }

    /**
     * Поведение модели при валидации изменено.
     * 
     * Подробнее wartur\myddd\common\DomainTrait::domainValidate($attributeNames = null, $clearErrors = true)
     * 
     * @param type $attributeNames
     * @param type $clearErrors
     * @return type
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        return $this->domainValidate($attributeNames, $clearErrors);
    }

}
