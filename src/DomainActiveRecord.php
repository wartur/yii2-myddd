<?php
/**
 * @author      Artur Krivtsov (gwartur) <gwartur@gmail.com> | Made in Russia
 * @copyright   Artur Krivtsov © 2020
 * @link        https://github.com/wartur/yii2-myddd
 * @license     BSD 3-Clause License
 */

namespace wartur\myddd;

/**
 * Базовый класс доменной ActiveRecord.
 * Является прозрачной настройкой над Yii2 ActiveRecord,
 * за исключением отключённого механизма сценариев.
 * 
 * Это набор удобных инструментов для работы с методологией MyDDD в Yii2
 * Во главе угла ставится удобное конфигурирование класса, которое вместе
 * с названием класса является удобной документацией
 * Все инструменты за исключением сценариев надстроены над ядром Yii2
 * 
 * Небольшая инструкция к удобному использованию ActiveRecord в DDD.
 * 
 * Унаследованные классы от этой модели должны быть классами activeRecords.
 * Рекомендуется их генерировать через Gii в директорию /models/activeRecords
 * Если требуется обновить базу данных. То с помощью Gii можно просто произвести обновление
 * базовых классов со всеми rules и relations.
 * 
 * Далее в директории /models/activeModels требуется унаследовать все классы от
 * /models/activeRecords. Эти классы в лучшем случае должны быть анемичными.
 * В них должны быть только самые общие поведения вроде TimestampBehavior.
 * Их уже можно использовать в CRUD или в других моделях, но без программной логики, то есть только чистыми.
 * 
 * Далее в директории /models/activeDomains требуется унаследовать классы от
 * /models/activeModels для наращивания в них бизнес логики.
 * Вы можете выбрать любое мнемонически удобное название неймспейса.
 * Каждый класс в лучшем случае умеет делать одно единственное действие
 * 
 * Классы унаследованные от классов из директорий /models/activeModels
 * могут быть 2-х типов. Фронтенд и Бэкенд классы.
 * 
 * Примеры названий Фронтенд классов, которыми администратор может управлять пользователями.
 * /models/activeDomains/admin/user/ChangePasswordUser
 * /models/activeDomains/admin/user/ResetPasswordUser
 * /models/activeDomains/admin/user/EditUser
 * 
 * Названия классов фактически должны описывать API системы и действие этого класса
 * должно транзакционно менять состояние информационной системы из одного в другое
 * 
 * Бэкенд классы находятся в неймспейсе Фонтенд класса. Например
 * /models/activeDomains/admin/user/changePasswordUser/EventLogger
 * 
 * Помните
 * - НЕЛЬЗЯ вызывать Фронтенд классы из Бэкенд классов домена!
 * - НЕЛЬЗЯ вызывать Бэкенд классы одного домена в другом домене!
 * - МОЖНО вызывать один Фронтенд из другого Фронтенда, но вне транзакций, (в методе afterSaveTransaction)
 * а сами классы начинают считаться Комплексными.
 * 
 * Остальное читайте в документации к методологии MyDDD
 */
class DomainActiveRecord extends \yii\db\ActiveRecord implements common\DomainInterface
{

    use common\DomainTrait;

    /**
     * Включить механизм валидации в транзакции.
	 * 
	 * Рекомендую использовать встроенный механизм Yii2,
	 * который настраивается в transactionsUno.
	 *
	 * Зачем нужна валидация в транзакции?
     * - Иногда вам надо проверить в базе данных кое какие данные,
	 * перед тем, как начать дейтвия с данными
	 * - Возможно изменить уровень изолированности транзакций
     * 
     * Может принимать одно из следующих значений
     * [[READ_UNCOMMITTED]], [[READ_COMMITTED]], [[REPEATABLE_READ]] and [[SERIALIZABLE]]
     * 
     * ```php
     * // включим самый строгий режим
     * public function enableTransaction() {
     *     return \yii\db\Transaction::SERIALIZABLE;
     * }
     * ```
     * 
     * @return boolean|string Задать тип изоляции транзакции или указать что транзакции не нужны (FALSE)
     */
    public function enableTransaction()
    {
        return false;
    }

    /**
     * Действия при сохранении модели (insert|update, выполняющиеся ПОСЛЕ afterSave (после транзакции сохранения)
     * 
     * Сюда возможно добавить длительные вычислительные
     * операции когда база данных уже разблокирована
     * 
     * Кроме того механизм позволяет делать составные Фронтенд классы
     * 
     * ```php
     * // пример реализации
     * public function afterSaveTransaction($success) {
     *     // общий код
     *     if ($success) {
     *         // код в случае success
     *     } else {
     *         // код в случае fail
     *     }
     * }
     * ```
     * 
     * @param boolean|integer $success
     */
    public function afterSaveTransaction($success)
    {
		
    }

    /**
     * Действия при удалении модели, выполняющиеся ДО beforeDelete (перед транзакции удаления)
     * 
     * Механизм позволяет делать составные Фронтенд классы
     * 
     * ```php
     * // пример реализации
     * public function beforeDeleteTransaction() {
     *     if(!parent::beforeDeleteTransaction()) {
     *         return false;
     *     }
     *     // какой-то код
     *     return true;
     * }
     * ```
     * 
     * @return boolean
     */
    public function beforeDeleteTransaction()
    {
        return true;
    }

    /**
     * Действия при удалении модели, выполняющиеся ПОСЛЕ afterDelete (после транзакции удаления)
     * 
     * Сюда возможно добавить длительные вычислительные
     * операции когда база данных уже разблокирована
     * 
     * Кроме того механизм позволяет делать составные Фронтенд классы
     * 
     * ```php
     * // пример реализации
     * public function afterDeleteTransaction($success) {
     *     // общий код
     *     if ($success) {
     *         // код в случае success
     *     } else {
     *         // код в случае fail
     *     }
     * }
     * ```
     * 
     * @param boolean|integer $success
     */
    public function afterDeleteTransaction($success)
    {
        
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
     * @return array
     */
    public function scenariosUno()
    {
        return parent::scenarios() [self::SCENARIO_DEFAULT];    // берем сценарии AR-модели по умолчанию
    }

    /**
     * Функционал конфигурирования транзакций
     * БЕЗ механизма сценариев
     * 
     * Принимаемые значения
     * [[OP_INSERT]], [[OP_UPDATE]] and [[OP_DELETE]] or [[OP_ALL]]
     * 
     * ```php
     * // обычно это настраивается так
     * public function transactionsUno()
     * {
     *     return self::OP_ALL;
     * }
     * ```
     * 
     * @return boolean|integer FALSE или конфигурация транзакций ядра Yii2 БЕЗ сценариев
     */
    public function transactionsUno()
    {
        return false;   // по умолчанию транзакции отключены как в transactions
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
     * Реализация сохранения модели (insert|update) с добавлением следующего функционала:
     * - afterSaveTransaction - код, выполняющийся после того как транзакция была завершена (успешно или нет)
     * - функционал механизма транзакций при валидации. Смотри enableTransaction
     * 
     * @param bool $runValidation whether to perform validation (calling [[validate()]])
     * before saving the record. Defaults to `true`. If the validation fails, the record
     * will not be saved to the database and this method will return `false`.
     * @param array $attributeNames list of attribute names that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return bool whether the saving succeeded (i.e. no validation errors occurred).
     * @throws \Exception не перехваченная ошибка при транзакции
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        $enableTransaction = $this->enableTransaction();
        if ($enableTransaction) {                   // если активирован механизм транзакций при валидации
            $this->checkTransactionsConfig();       // проверить конфигурацию модели
            $trans = $this->getDb()->beginTransaction($enableTransaction);
            try {
                // валидируем и сохраняем
                $result = parent::save($runValidation, $attributeNames);
                if ($result) {
                    $trans->commit();
                } else {
                    $trans->rollBack();
                }
                $this->afterSaveTransaction($result);
                return $result;
            } catch (Exception $ex) {
                $trans->rollBack();
                throw $ex;
            }
        } else {
            $result = parent::save($runValidation, $attributeNames);
            $this->afterSaveTransaction($result);
            return $result;
        }
    }

    /**
     * Реализация удаления модели (delete) с добавлением следующего функционала:
     * - beforeDeleteTransaction - кол, выполняющийся до того как транзакция началась
     * - afterSaveTransaction - код, выполняющийся после того как транзакция была завершена (успешно или нет)
     * 
     * @return int|false the number of rows deleted, or `false` if the deletion is unsuccessful for some reason.
     * Note that it is possible the number of rows deleted is 0, even though the deletion execution is successful.
     * @throws \Exception не перехваченная ошибка при транзакции
     */
    public function delete()
    {
        if (!$this->beforeDeleteTransaction()) {
            return false;
        }

        $enablTransactionLevel = $this->enableTransaction();
        if ($enablTransactionLevel) {
            $this->checkTransactionsConfig();
            $trans = $this->getDb()->beginTransaction($enablTransactionLevel);
            try {
                $result = parent::delete();
                if ($result) {
                    $trans->commit();
                } else {
                    $trans->rollBack();
                }
                $this->afterDeleteTransaction($result);
                return $result;
            } catch (Exception $ex) {
                $trans->rollBack();
                throw $ex;
            }
        } else {
            $result = parent::delete();
            $this->afterDeleteTransaction($result);
            return $result;
        }
    }

    /**
     * Проверка того, есть ли ошибка в конфигурации механизма транзакций
     * Дело в том, что при активации enableTransaction() и настройки transactionsUno
     * будет происходит создание двойной транзакции, что является лишним.
     * 
     * Поэтому в случае если активирован enableTransaction, система проверят нет ли конфликтов
     * 
     * @throws \yii\base\InvalidConfigException в случае, если обнаружена проблема в конфигурации
     */
    protected function checkTransactionsConfig()
    {
        if (!empty($this->transactions())) {
            throw new \yii\base\InvalidConfigException('При ручной транзакции DomainActiveRecord::enableTransaction() требуется отключить транзакции в модели. Метод DomainActiveRecord::transactionsUno() должен возвращать <empty> значение');
        }
    }

    /**
     * Запрет системы сценариев. Оставляем только сценарий SCENARIO_DEFAULT, который требуется для ядра Yii2.
     * 
     * @return array сценарии Yii2 с одним возможным сценарием
     */
    public final function scenarios()
    {
        return [self::SCENARIO_DEFAULT => $this->scenariosUno()];
    }

    /**
     * Запрет системы транзакций с мультисценариями.
     * Оставляем только сценарий SCENARIO_DEFAULT, который требуется для ядра Yii2.
     * 
     * @return array сценарии Yii2 с одним возможным сценарием
     */
    public final function transactions()
    {
        $trans = $this->transactionsUno();
        if (empty($trans)) {
            return [];
        } else {
            return [self::SCENARIO_DEFAULT => $trans];
        }
    }

}
