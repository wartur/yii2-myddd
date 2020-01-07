<?php
/**
 * @author      Artur Krivtsov (gwartur) <gwartur@gmail.com> | Made in Russia
 * @copyright   Artur Krivtsov © 2020
 * @link        https://github.com/wartur/yii2-myddd
 * @license     BSD 3-Clause License
 */

namespace wartur\myddd\common;

/**
 * Интерфейс доменной модели
 * 
 * Используется в
 * - DomainModel
 * - DomainActiveRecord
 * - DomainTrait
 */
interface DomainInterface
{

    /**
     * Ошибка domainLastError по умолчанию
     */
    const ERROR_DEFAULT = 'Неизвестная ошибка';

    /**
     * Ошибка domainLastError по умолчанию
     */
    const ERROR_VALIDATION = 'Ошибка валидации модели';

    /**
     * Поведение по умолчанию в случае неудачной валидации
     */
    public function validateFailedDefaultBehavior();
}
