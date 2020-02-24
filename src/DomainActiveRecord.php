<?php
/**
 * @author      Artur Krivtsov (gwartur) <gwartur@gmail.com> | Made in Russia
 * @copyright   Artur Krivtsov © 2020
 * @link        https://github.com/wartur/yii2-myddd
 * @license     BSD 3-Clause License
 */

namespace wartur\myddd;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;

/**
 * Базовый класс доменной ActiveRecord.
 * Является прозрачной настройкой над Yii2 ActiveRecord,
 * за исключением отключённого механизма сценариев.
 * 
 * Это набор удобных инструментов для работы по методике MyDDD в Yii2
 * Во главе угла ставится удобное конфигурирование класса, которое вместе
 * с названием класса является удобной документацией
 * Класс полностью надстроен над ядром Yii2
 * 
 * Небольшая инструкция к удобному использованию ActiveRecord в MyDDD.
 * 
 * Унаследованные классы от этой модели должны быть классами activeRecords.
 * Рекомендуется их генерировать через Gii в директорию /models/activeRecords
 * Если требуется обновить базу данных. То с помощью Gii можно просто произвести обновление
 * базовых классов со всеми rules и relations.
 * 
 * Далее в директории /models/activeModels требуется унаследовать все классы от
 * /models/activeRecords. Эти классы в лучшем случае должны быть анемичными.
 * В них должны быть только самые общие поведения вроде TimestampBehavior.
 * В этих классах можно настраивать удобные геттеры для повышения удобства кодирования.
 * Их уже можно использовать в CRUD или в других моделях, но без программной логики, то есть только чистыми.
 * 
 * Далее в директорию /models/frontend пишем фронтенд классы
 * Далее в директорию /models/backend пишем бэкенд классы
 * 
 * Что такое MyDDD читайте в документации. Удачной работы
 */
class DomainActiveRecord extends \yii\db\ActiveRecord
{

    use common\DomainTrait;

    /**
     * Флаг, указывающий на то, что модель уже загружена в режиме FOR UPDATE
     * Нужен для оптимизаций при определённых случаях работы
     * 
     * С точки зрения доступа это PROTECTED INTERNAL метод, а не PUBLIC метод
     * Поэтому эту проперти в унаследованном коде можно только читать,
     * писать в неё нельзя - этим вы запутаете будущие поколения
     * 
     * @var boolean
     */
    public $domainModelIsAllreadyForUpdate = false;

    /**
     * Действия, которые надо выполнить до транзакции
     */
    public function beforeTransaction()
    {
        return true;
    }

    /**
     * Действия, которые надо выполнить после транзакции
     * 
     * @param boolean|int $success успешо или не успешно выполнена транзакция (insert|update|delete)
     */
    public function afterTransaction($success)
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
     * Так как обычно модель делает одно/два действия, рекомендую использовать
     * метод с синтаксическим сахаром transactionsActivate
     * 
     * @return boolean|integer FALSE или конфигурация транзакций ядра Yii2 БЕЗ сценариев
     */
    public function transactionsUno()
    {
        if ($this->transactionsActivate()) {
            return self::OP_ALL;
        } else {
            return false;   // по умолчанию транзакции отключены как в transactions
        }
    }

    /**
     * Синтаксический сахар активации/деактивации транзакций
     * 
     * === ПО УМОЛЧАНИЮ ===
     * СОГЛАСНО МЕТОДИКЕ И ДЛЯ ИСКЛЮЧЕНИЯ ОШИБОК ПРИ РАЗРАБОТКЕ
     * ТРАНЗАКЦИИ ВСЕГДА АКТИВИРОВАНЫ
     * 
     * Модель обычно имеет только одно/два действия.
     * Следовательно удобнее всего активировать транзакции OP_ALL
     * Или же вообще их не активировать
     * 
     * Этот метод просто возвращает true, если нужно активировать транзакции
     * 
     * @return boolean
     */
    public function transactionsActivate()
    {
        return true;
    }

    /**
     * Загрузить данные модели заблокировав запись в транзакции
     * 
     * Используется для того, чтобы во время выполнения транзакции заблокировать
     * модель прочитав её из базы данных
     * 
     * А так же получить последнюю версию данных, перед исключительной блокировкой FOR UPDATE
     * 
     * @param array|string|null $attributeNames какие поля загрузить. Можно оптимизировать запросы
     * - null - загрузить ВСЕ поля модели
     * - string - загрузить одно поле модели
     * - array - загрузить список полей модели
     * 
     * @param type $forceUpdate обновить модель вне зависимости от того, была ли она помечена
     * как блокированная FOR UPDATE. Не знаю зачем это надо, но этот параметр явно не буде лишним.
     * Во всяком случае, я не рекомендую использовать подобные - это не оценят будущие поколения
     * 
     * @return boolean
     * @throws InvalidConfigException
     */
    public function refreshForUpdate($attributeNames = null, $forceUpdate = false)
    {
        // очень часто требуется инвализировать только одну переменную
        if (isset($attributeNames) && is_string($attributeNames)) {
            $attributeNames = [$attributeNames];
        }

        // если модель уже загружена в режиме ForUpdate, перезагружать её не нужно, метод сработает в холостую
        // как следствие делать любые другие проверки не обязательно
        if ($this->domainModelIsAllreadyForUpdate && !$forceUpdate) {
            return true;
        }

        // Если не активированы транзакции, попросим активировать
        if (empty($this->transactionsUno())) {
            throw new InvalidConfigException('Включите транзакцию при использовании refreshForUpdate');
        }

        // Если запись новая, то очевидно мы упадем
        if ($this->isNewRecord) {
            throw new InvalidConfigException('Новая модель не может сделать refresh');
        }

        // скопируем код из parent::refresh(), видимо для поддержки составных первичных ключей
        $query = static::find();
        $tableName = key($query->getTablesUsedInFrom());
        $pk = [];
        // disambiguate column names in case ActiveQuery adds a JOIN
        foreach ($this->getPrimaryKey(true) as $key => $value) {
            $pk[$tableName . '.' . $key] = $value;
        }
        $query->where($pk);

        // в select будем грузить только то что нужно
        if (isset($attributeNames)) {
            $query->select($attributeNames); // запросим толко определенные колонки
        }

        // добавим блокировку FOR UPDATE
        $sql = $query->createCommand()->getRawSql();
        $record = static::findBySql($sql . ' FOR UPDATE')->one(); /* @var $record BaseActiveRecord */
        if (empty($record)) {
            return false;
        }

        if (isset($attributeNames)) {
            // частично обновим
            foreach ($attributeNames as $attribute) {
                $this->setOldAttribute($attribute, $record->getOldAttribute($attribute));
            }
        } else {
            // полностью обновим
            $this->setOldAttributes($record->_oldAttributes);
        }

        return true;
    }

    /**
     * Синтаксический сахар, используется только для фронтент-модели ActiveRecord
     * 
     * Установить ошибку domainLastError и выбросить исключение для перехвата
     * на форнтенд-моделе и вывода управляемой ошибки domainLastError
     * Если точнее, то использовать требуется в afterSave|afterDelete методах,
     * когда требуется отменить процесс завершения транзакции, так как в Yii2
     * нет механизма остановить её дальнейшее протекание, кроме как через исключение
     * 
     * @param string $domainLastError информация о последней ошибке
     * @param string $addErrorAttribute атрибут модели для которой требуется добавить
     * ошибку как в domainLastError
     * @param string $exClass название класса исключния
     * @throws \yii\base\Exception выбрасывамое исключение, перехватываемое в save|delete методе
     */
    public function falseWithExcepion($domainLastError = 'Неизвестная ошибка', $addErrorAttribute = '_domainLastError', $exClass = \yii\base\Exception::class)
    {
        $this->falseWithError($domainLastError, $addErrorAttribute);
        throw new $exClass($domainLastError);
    }

    /**
     * {@inheritdoc}
     * 
     * К findOne добавлен функционал указания типа блокировки
     * ДЛЯ БЛОКИРОВКИ record lock, gap lock или next-key lock
     * Подробнее https://habr.com/ru/post/238513/
     * 
     * ВНИМАНИЕ!!!
     * В случае использования этой функциональности с FOR UPDATE убедитесь:
     * - что вы находитесь в транзакции
     * - подчинённый код не откатит текущую транзакцию
     * - вызываемой моделе в beforeTransaction нет сложных вычислений
     * 
     * Возможно, правильнее использовать стандартное поведение модели, то есть
     * механизм двойного чтения с блокировкой при использовании refreshForUpdate
     * 
     * @param string $lock тип блокировки
     * '' - пустая строка (по умолчанию)
     * FOR UPDATE
     * LOCK IN SHARE MODE
     * 
     * @return static|null ActiveRecord instance matching the condition, or `null` if nothing matches.
     */
    public static function findOne($condition, $lock = '')
    {
        $sql = static::findByCondition($condition)->createCommand()->getRawSql();
        $model = static::findBySql($sql . ' ' . $lock)->one();
        if (empty($model)) {
            return $model;
        }
        /* @var $model DomainActiveRecord */
        if ($lock == 'FOR UPDATE') {
            $model->domainModelIsAllreadyForUpdate = true;
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     * 
     * К findAll добавлен функционал указания типа блокировки
     * ДЛЯ БЛОКИРОВКИ record lock, gap lock или next-key lock
     * Подробнее https://habr.com/ru/post/238513/
     * 
     * ВНИМАНИЕ!!!
     * В случае использования этой функциональности с FOR UPDATE убедитесь:
     * - что вы находитесь в транзакции
     * - подчинённый код не откатит текущую транзакцию
     * - вызываемой моделе в beforeTransaction нет сложных вычислений
     * 
     * Этот метод рекомендуется использовать для исключения массовой активации
     * механизма двойного чтения с блокировкой при использовании refreshForUpdate
     * 
     * @param string $lock тип блокировки
     * '' - пустая строка (по умолчанию)
     * FOR UPDATE
     * LOCK IN SHARE MODE
     * 
     * @return static[] an array of ActiveRecord instances, or an empty array if nothing matches.
     */
    public static function findAll($condition, $lock = '')
    {
        $sql = static::findByCondition($condition)->createCommand()->getRawSql();
        $models = static::findBySql($sql . ' ' . $lock)->all();
        /* @var $models DomainActiveRecord[] */
        if ($lock == 'FOR UPDATE') {
            foreach ($models as &$model) {
                $model->domainModelIsAllreadyForUpdate = true;
            }
            unset($model);
        }

        return $models;
    }

    /**
     * Создание произвольных запросов и получение одной записи ->one()
     * С ПОДДЕРЖКОЙ БЛОКИРОВКИ record lock, gap lock или next-key lock
     * Подробнее https://habr.com/ru/post/238513/
     * 
     * ВНИМАНИЕ!!!
     * В случае использования этой функциональности с FOR UPDATE убедитесь:
     * - что вы находитесь в транзакции
     * - подчинённый код не откатит текущую транзакцию
     * - вызываемой моделе в beforeTransaction нет сложных вычислений
     * 
     * Возможно, правильнее использовать стандартное поведение модели, то есть
     * механизм двойного чтения с блокировкой при использовании refreshForUpdate
     * 
     * @param string $lock тип блокировки
     * '' - пустая строка (по умолчанию)
     * FOR UPDATE
     * LOCK IN SHARE MODE
     * 
     * @return static|null ActiveRecord instance matching the condition, or `null` if nothing matches.
     */
    public static function findOneByQuery(ActiveQuery $query, $lock = '')
    {
        $sql = $query->createCommand()->getRawSql();
        $model = static::findBySql($sql . ' ' . $lock)->one();
        if (empty($model)) {
            return $model;
        }
        /* @var $model DomainActiveRecord */
        if ($lock == 'FOR UPDATE') {
            $model->domainModelIsAllreadyForUpdate = true;
        }

        return $model;
    }

    /**
     * Создание произвольных запросов и получение списка записей ->all()
     * С ПОДДЕРЖКОЙ БЛОКИРОВКИ record lock, gap lock или next-key lock
     * Подробнее https://habr.com/ru/post/238513/
     * 
     * ВНИМАНИЕ!!!
     * В случае использования этой функциональности с FOR UPDATE убедитесь:
     * - что вы находитесь в транзакции
     * - подчинённый код не откатит текущую транзакцию
     * - вызываемой моделе в beforeTransaction нет сложных вычислений
     * 
     * Этот метод рекомендуется использовать для исключения массовой активации
     * механизма двойного чтения с блокировкой при использовании refreshForUpdate
     * 
     * @param string $lock тип блокировки
     * '' - пустая строка (по умолчанию)
     * FOR UPDATE
     * LOCK IN SHARE MODE
     * 
     * @return static[] an array of ActiveRecord instances, or an empty array if nothing matches.
     */
    public static function findAllByQuery(ActiveQuery $query, $lock = '')
    {
        $sql = $query->createCommand()->getRawSql();
        $models = static::findBySql($sql . ' ' . $lock)->all();
        /* @var $models DomainActiveRecord[] */
        if ($lock == 'FOR UPDATE') {
            foreach ($models as &$model) {
                $model->domainModelIsAllreadyForUpdate = true;
            }
            unset($model);
        }

        return $models;
    }

    /**
     * Клонировать текущий объект ActiveRecord в новый объект нужного типа
     * 
     * Позволяет не перезагружая базу данных использовать модель ActiveRecord
     * Для создания модели с текущим состоянием
     * 
     * @param type $className
     * @param type $callback
     * @return \wartur\myddd\DomainActiveRecord возвращаем доменную модель
     * @throws InvalidConfigException ошибки использования
     */
    public function cloneTo($className, $callback = null)
    {
        $model = new $className();  /* @var $model DomainActiveRecord */
        if (!(is_a($model, $this->cloneToMinimalClass()))) {
            throw new InvalidConfigException('Модель не является наследником ' . $this->cloneToMinimalClass());
        }
        if ($model->tableName() !== $this->tableName()) {
            throw new InvalidConfigException('Модель не является идентичной');
        }
        $model->setOldAttributes($this->getOldAttributes());
        $model->afterFind();
        $model->afterCloneTo($this);
        return $model;
    }

    /**
     * Действия которые можно совершить после клонирования.
     * Позволяет клонировать какие-то специфические для модели поля.
     * 
     * @param DomainActiveRecord $sourсeModel
     */
    public function afterCloneTo($sourсeModel)
    {
        
    }

    /**
     * Позволяет задать класс, от которого можно совершать клон объекта
     * текущего уровня наследованя
     * 
     * @return string название класса
     * Метод должен переопределяться и вызывать код return self::class;
     */
    public function cloneToMinimalClass()
    {
        return self::class; // по умолчанию все от DomainActiveRecord
    }

    // =========================================================================
    // ПОДКАПОТНОЕ ПРОСТРАНСТВО
    // =========================================================================

    /**
     * Полностью переопределен метод ActiveRecord
     * Добавил 2 новых метода для WORKFLOW MyDDD
     * - beforeTransaction
     * - afterTransaction
     * 
     * Валидацию вынес в этот метод, а в insert|update
     * 
     * Все остальное поведение совместимо с оригиналом
     * 
     * @param boolean $runValidation
     * @param array $attributeNames
     * @return boolean
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            Yii::info('Model not saved due to validation error.', __METHOD__);
            return false;
        }

        if (!$this->beforeTransaction()) {
            return false;
        }

        // ловим исключения из afterSave
        try {
            if ($this->getIsNewRecord()) {
                $result = $this->insert(false, $attributeNames);
            } else {
                $result = $this->update(false, $attributeNames) !== false;
            }
        } catch (\Exception $ex) {
            if (empty($this->domainLastError)) {
                $this->afterTransaction(false);
                throw $ex;
            } else {
                $result = false;
            }
        }

        $this->afterTransaction($result);

        return $result;
    }

    /**
     * Сохранить или выкинуть исключение
     * 
     * Удобный синтаксический сахар
     * @param string $message Информация об ошибке
     * @param type $className Название класса исключения
     * @throws \yii\base\Exception
     */
    public function saveOrException($message = 'Ошибка сохранения', $className = \yii\base\Exception::class)
    {
        if ($this->save()) {
            return true;
        }

        if ($message == 'Ошибка сохранения') {
            $reflect = new \ReflectionClass($this);
            $message .= ' ' . $reflect->getShortName();
        }
        throw new $className($message);
    }

    /**
     * Полностью переопределен метод ActiveRecord
     * Добавил 2 новых метода для WORKFLOW MyDDD
     * - beforeTransaction
     * - afterTransaction
     * 
     * Добавлена валидация
     * Ведь могут быть backend зависимости при удалении, например "удаляет делает" или "копирование информации в другую таблицу",
     * Могут быть и frotnend зависимости, например "введите номер заказа для подтверждения удаления"
     * 
     * Метод в целом никак не отличается от save, только мнемонически и основным действием
     * 
     * @param boolean $runValidation
     * @param array $attributeNames
     * @return boolean
     */
    public function delete($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            Yii::info('Model not deleted due to validation error.', __METHOD__);
            return false;
        }

        if (!$this->beforeTransaction()) {
            return false;
        }

        // ловим исключения из afterSave
        try {
            $result = parent::delete();
        } catch (\Exception $ex) {
            if (empty($this->domainLastError)) {
                $this->afterTransaction(false);
                throw $ex;
            } else {
                $result = false;
            }
        }

        $this->afterTransaction($result);
        return $result;
    }

    /**
     * Сохранить или выкинуть исключение
     * 
     * Удобный синтаксический сахар
     * @param string $message Информация об ошибке
     * @param type $className Название класса исключения
     * @throws \yii\base\Exception
     */
    public function deleteOrException($message = 'Ошибка удаления', $className = \yii\base\Exception::class)
    {
        if ($this->delete()) {
            return true;
        }

        if ($message == 'Ошибка сохранения') {
            $reflect = new \ReflectionClass($this);
            $message .= ' ' . $reflect->getShortName();
        }
        throw new $className($message);
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
    public function validate($attributeNames = null, $clearErrors = true)
    {
        return $this->domainValidate($attributeNames, $clearErrors);
    }

}
