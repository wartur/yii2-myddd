<?php
/**
 * @author      Artur Krivtsov (gwartur) <gwartur@gmail.com> | Made in Russia
 * @copyright   Artur Krivtsov © 2020
 * @link        https://github.com/wartur/yii2-myddd
 * @license     BSD 3-Clause License
 */

namespace wartur\myddd\common;

use yii\base\InvalidConfigException;
use yii\helpers\Json;

/**
 * Поведение доменной модели
 * 
 * Здесь располагаются общий функционал для методики MyDDD для:
 * - DomainModel
 * - DomainActiveRecord
 * 
 * @property-read string $errorsJson Ошибки закодированные в JSON (нужно для удобного чтения и анализа)
 */
trait DomainTrait
{

    /**
     * @var string последняя ошибка фронтенд-модели.
     * По умолчанию "неизвестная ошибка"
     * 
     * Удобно кастомизировать через:
     * ```php
     * $this->falseWithError('Описание ошибки');
     * ```
     * 
     * В классе рекомендуется создавать константы по шаблону ERROR_*
     * для перечисления всех поддерживаемых ошибок класса
     */
    public $domainLastError = null;

    /**
     * Синтаксический сахар, используется только для фронтент-модели
     * 
     * Установить ошибку domainLastError и вернуть false
     * 
     * В качестве опции, можно добавить addError для отображения пользователю
     * о каких-либо проблемах на каком-то аттрибуте
     * 
     * ```php
     * public function beforeSave()
     * {
     *     // some code
     *     return $this->falseWithError('Ошибка. Нет доступа к API платёжной системы');
     * }
     * ```
     * 
     * @param string $domainLastError информация о последней ошибке
     * @param string $addErrorAttribute атрибут модели для которой требуется добавить
     * ошибку как в domainLastError
     * @return boolean ВСЕГДА возвращает FALSE
     */
    public function falseWithError($domainLastError = 'Неизвестная ошибка', $addErrorAttribute = '_domainLastError')
    {
        $this->domainLastError = $domainLastError;
        if ($addErrorAttribute) {
            $this->addError($addErrorAttribute, $this->domainLastError);
        }
        return false;
    }

    /**
     * Получить список unsafe аттрибутов.
     * 
     * Данные берутся из сценария
     * если в сценарии одновременно встретились safe и unsafe аттрибуты,
     * устанавливаем приоритет safe аттрибута
     * 
     * Это позволяет брать бэкенд модель и делать из нее фронтенд модель
     * 
     * @return array
     */
    public function unsafeAttributes()
    {
        $scenario = $this->getScenario();
        $scenarios = $this->scenarios();
        if (!isset($scenarios[$scenario])) {
            return [];
        }
        $unsafeAttributes = [];
        $safeAttributes = [];
        foreach ($scenarios[$scenario] as $attribute) {
            // получим unsafe аттрибуты в сценарии
            if ($attribute[0] === '!' && !in_array($attribute, $unsafeAttributes)) {
                $unsafeAttributes[] = substr($attribute, 1); // уберем "!"
            }

            // получим safe аттрибуты в сценарии
            if ($attribute[0] !== '!' && !in_array($attribute, $safeAttributes)) {
                $safeAttributes[] = $attribute;
            }
        }

        // если одновременно встретились safe и unsafe аттрибуты,
        // устанавливаем приоритет safe аттрибута
        $return = [];
        foreach ($unsafeAttributes as $entry) {
            // если аттрибут найден в safe, то не добавляем его
            if (!in_array($entry, $safeAttributes)) {
                $return[] = $entry;
            }
        }
        return $return;
    }

    /**
     * Валидация модели
     * 
     * Видоизменяем процесс валидации модели в зависимости от того
     * какая модель сконфигурирована - фронтенд или бэкенд
     * 
     * если в unsafe аттрибутах присутствуют ошибки
     * то в бэкенд или в фронтенд-моделе следует выбросить исключение InvalidConfigException
     * InvalidConfigException - считается ошибкой программиста
     * 
     * @param array|null $attributeNames
     * @param boolean $clearErrors
     * @return boolean
     * @throws InvalidConfigException ошибка конфигурации объекта
     */
    protected function domainValidate($attributeNames = null, $clearErrors = true)
    {
        if (parent::validate($attributeNames, $clearErrors)) {
            return true;
        } else {
            // если в unsafe аттрибутах присутствуют ошибки
            // то в бэкенд или в фронтенд-моделе следует выбросить исключение InvalidConfigException
            // InvalidConfigException - считается ошибкой программиста
            foreach ($this->unsafeAttributes() as $entry) {
                if ($this->hasErrors($entry)) {
                    throw new InvalidConfigException('Ошибка конфигурации ' . $this->className() . ' : ' . Json::encode($this->getErrors()));
                }
            }

            return false;
        }
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
    protected function domainScenariosUno()
    {
        $scenariosBackend = $this->scenariosBackend();
        $scenariosFrontend = $this->scenariosFrontend();

        // Зададим приоритет фронтенда, если он был переопредлен
        $scenariosBackend = array_filter($scenariosBackend, function($entry) use ($scenariosFrontend) {
            return !in_array($entry, $scenariosFrontend);
        });

        // если не заданы 2 специализировнных метода, то считаем что это повдение AR-модели по умолчанию
        if (empty($scenariosBackend) && empty($scenariosFrontend)) {
            return parent::scenarios() [self::SCENARIO_DEFAULT];    // берем сценарии AR-модели по умолчанию
        }

        // добавим восклицательный знак. Пометим атррибуты как unsafe
        foreach ($scenariosBackend as &$entry) {
            $entry = '!' . $entry;
        }
        unset($entry);

        // смерджим, получим общий набор правил для скармливания Yii2
        return array_merge($scenariosBackend, $scenariosFrontend);
    }

    /**
     * Просто удаляем все required правила
     * Метод помогает полноценно работать search модлям
     */
    public static function clearRequiredRules($rules)
    {
        $result = [];
        foreach ($rules as $entry) {
            if ($entry[1] == 'required' || $entry[1] == 'unique') {
                continue;
            }
            $result[] = $entry;
        }
        return $result;
    }

    /**
     * Получить Errors закодированное в JSON
     * @return string
     */
    public function getErrorsJson()
    {
        return Json::encode($this->getErrors());
    }

}
