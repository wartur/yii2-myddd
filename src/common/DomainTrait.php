<?php
/**
 * @author      Artur Krivtsov (gwartur) <gwartur@gmail.com> | Made in Russia
 * @copyright   Artur Krivtsov © 2020
 * @link        https://github.com/wartur/yii2-myddd
 * @license     BSD 3-Clause License
 */

namespace wartur\myddd\common;

use Yii;
use yii\base\Exception;

/**
 * Поведение доменной модели
 * 
 * Здесь располагаются общий функционал для методологии Yii2::DDD для:
 * - DomainModel
 * - DomainActiveRecord
 */
trait DomainTrait
{

    /**
     * @var string последняя ошибка доменной модели.
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
    public $domainLastError = DomainInterface::ERROR_DEFAULT;

    /**
     * Поведение при неуспешной валидации.
     * Это поведение возможно изменить, переопределив данный метод.
     * 
     * Например можно активировать автоматический выброс исключений
     * при валидации Бэкенд моделей (не знаю зачем, но можно)
     * 
     * ```php
     * public function validateFailedDefaultBehavior()
     * {
     *     $this->falseWithException('Ошибка валидации модели');
     * }
     * ```
     * 
     * Или вообще ничего не делать
     * ```php
     * public function validateFailedDefaultBehavior()
     * {
     *     // не делаем ничего, потому что...
     * }
     * ```
     */
    public function validateFailedDefaultBehavior()
    {
        $this->falseWithError(DomainInterface::ERROR_VALIDATION);
    }

    /**
     * Синтаксический сахар
     * 
     * Установить ошибку domainLastError и вернуть false
     * В основном используется для работы в Фронтент моделях
     * 
     * В качестве опции, можно добавить addError для отображения пользователю
     * о каких-либо проблемах
     * 
     * ```php
     * public function beforeSave()
     * {
     *     // some code
     *     return $this->falseWithError('Ошибка. Нет доступа к API платёжной системы');
     * }
     * ```
     * 
     * @param string $domainLastError информация о последней ошибке,
     * рекомендуется использовать константы ERROR_*
     * для возможной дальнейшей обработке выше по стеку вызова.
     * @param string $addErrorAttribute атрибут модели для которой требуется добавить
     * ошибку как в domainLastError
     * @return boolean ВСЕГДА возвращает FALSE
     */
    public function falseWithError($domainLastError, $addErrorAttribute = false)
    {
        $this->domainLastError = $domainLastError;
        if ($addErrorAttribute) {
            $this->addError($addErrorAttribute, $this->domainLastError);
        }
        return false;
    }

    /**
     * Синтаксический сахар
     * 
     * Установить ошибку и выкинуть исключение
     * В основном используется для работы в Бэкед моделях
     * 
     * ```php
     * public function beforeSave()
     * {
     *     // some code
     *     return $this->falseWithException('У пользователя не хватает средств');
     * }
     * ```
     * 
     * @param string $domainLastError информация о последней ошибке,
     * рекомендуется использовать константы ERROR_*
     * для возможной дальнейшей обработке выше по стеку вызова.
     * @param class $exceptionClass класс, который требуется использовать в исключении
     * Если требуется создать более умный класс с доп полями
     * не используйте данный метод, реализуйте его самостоятельно
     * @throws $exceptionClass
     */
    public function falseWithException($domainLastError, $exceptionClass = Exception::class)
    {
        $this->domainLastError = $domainLastError;
        throw new $exceptionClass($domainLastError);
    }

    /**
     * Синтаксический сахар
     * 
     * Установить ошибку, записать в warning лог информацию и вернуть false
     * 
     * ```php
     * public function beforeSave()
     * {
     *     // some code
     *     return $this->falseWithWarning('Неудалось получить информацию о пользователе от API');
     * }
     * ```
     * 
     * @param string $domainLastError информация о последней ошибке,
     * рекомендуется использовать константы ERROR_*
     * для возможной дальнейшей обработке выше по стеку вызова.
     * @param string $logCategory категория лога (второй параметр Yii::warning)
     * @return boolean ВСЕГДА возвращает FALSE
     */
    public function falseWithWarning($domainLastError = null, $logCategory = null)
    {
        $this->domainLastError = $domainLastError;
        Yii::warning($domainLastError, $logCategory);    // кладем в лог
        return false;
    }

    /**
     * Синтаксический сахар
     * 
     * Установить ошибку, записать в warning лог информацию и вернуть false
     * Если приложение работает в режиме разработки выкинуть исключение
     * Позволяет обращать внимание на места, где возникает ошибка, однако код может
     * быть продолжен на продакшене
     * 
     * ```php
     * public function beforeSave()
     * {
     *     // some code
     *     return $this->falseWithWarningOnDebugExecption('Что-то не получилось');
     * }
     * ```
     * 
     * @param string $domainLastError информация о последней ошибке,
     * рекомендуется использовать константы ERROR_*
     * для возможной дальнейшей обработке выше по стеку вызова.
     * @param string $logCategory категория лога (второй параметр Yii::warning)
     * @return boolean ВСЕГДА возвращает FALSE
     * @throws Exception исключение в случае YII_DEBUG
     */
    public function falseWithWarningOnDebugExecption($domainLastError = null, $logCategory = null)
    {
        $this->falseWithWarning($domainLastError, $logCategory);
        if (YII_DEBUG) {
            throw new Exception($domainLastError);
        }
    }

    /**
     * Синтаксический сахар
     * 
     * Записать в лог Yii::info информацию о событии и вернуть true
     * Позволяет логгировать некоторые моменты
     * 
     * ```php
     * public function beforeSave()
     * {
     *     // some code
     *     return $this->trueWithInfo('Права пользователя проверены на сервере авторизации');
     * }
     * ```
     * 
     * @param string $logMessage информация о событии
     * @param string $logCategory категория лога (второй параметр Yii::info)
     * @return boolean ВСЕГДА возвращает TRUE
     */
    public function trueWithInfo($logMessage = null, $logCategory = null)
    {
        if (isset($logMessage)) {
            Yii::info($logMessage, $logCategory);
        }

        return true;
    }

}
