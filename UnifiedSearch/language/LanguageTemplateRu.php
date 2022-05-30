<?php

namespace UnifiedSearch\language;

abstract class LanguageTemplateRu extends LanguageTemplate
{
    public static $language_data = [
        'START_PAGE_HEADING' => 'Unified Search',
        'START_PAGE_ABOUT' => 'Унифицированная поисковая система',
        'NO_UPLOAD_ACCESS' => 'Нет прав на загрузку данных портфолио',
        'START_TEXT' => 'Унифицированная поисковая система Unified Search является платформой, которая может использоваться в разных сценариях полнотекстового поиска информации о деталях, продуктах, их атрибутах в домене автозапчастей.',
        'GETTING_STARTED' => 'Как использовать',
        'SHOW_DEMO' => 'Посмотреть демо',
        'SEARCH_DEMO' => 'Искать',
        'DOCUMENTATION' => 'Документация',
        'DOWNLOADS' => 'SDK',
        'SEARCH_PLACEHOLDER' => 'Введите VIN и/или наименование детали',
        'CONSIDER_CROSSES' => 'Усиливать замены',
        'IN_STOCK' => 'Только в наличии',
        'WITH_OFFERS' => 'С предложениями',
        'FILTER_BY_TAG' => 'Фильтровать по тэгу',
        'UNKNOWN_VIN' => 'Неизвестный VIN: %s. Пожалуйста подождите пока завершится индексация',
        'ENTER_QUERY' => 'Пожалуйста введите запрос и тэг для начала поиска',
        'ENTER_QUERY_DESC' => '',
        'ENTER_QUERY_LINK'          => 'Узнайте как интегрировать в Вашу систему',
        'LOAD_OFFERS'               => 'Загрузить наличие',
        'NOTHING_FOUND'             => 'Ничего не найдено',
        'BRAND'                     => 'Бренд',
        'SCORE'                     => 'Релевантность',
        'OEM'                       => 'Артикул',
        'DETAIL'                    => 'Деталь',
        'NAME'                      => 'Наименование',
        'TAGS'                      => 'Тэги',
        'DROP_FILE_HERE'            => 'Перетащите файл сюда',
        'SELECT_FILE'               => 'Выбрать файл',
        'FILE_EXAMPLE'              => 'Образец файла наличия:',
        'UNITS'                     => 'Узлы',
        'NOT_AUTHORIZED'            => 'Авторизация',
        'NOT_AUTHORIZED_DESC'       => 'Этот функционал доступен только пользователям Unified Search',
        'SUGGESTED_QUERIES_FOUND'   => 'Возможно, вы имели в виду:',
        'UPLOADS'                   => 'Загрузка наличия',
        'LOCALIZED_NAME_TABLE_HEAD' => 'Наименование (%s)',
        'CHANGE_MODIFICATION'       => 'Изменить модификацию',
        'INDEXATION_TIMEOUT_ERROR'  => 'Переиндексация Vin-кода не может быть начата раньше, чем через 15 минут после предыдущей индексации',
        'UNKNOWN_INDEXATION_ERROR'  => 'Неизвестная ошибка индексации',
        'VENDOR_CODES'              => 'Коды поставщика',
        'ALLOWED_FILE_TYPES'        => 'Поддерживаемые форматы csv, txt, zip.<br /> Название не должно содержать кириллических символов',
        'FILE_TABLE_COLUMN_NAME'    => 'Название',
        'FILE_TABLE_COLUMN_PROGRESS'    => 'Прогресс',
        'FILE_TABLE_COLUMN_CREATED'    => 'Дата создания',
        'FILE_TABLE_COLUMN_ERRORS'    => 'Ошибки',
        'DOWNLOAD_FILE_WITH_ERRORS'    => 'Скачать файл с ошибками',
        'SELECT' => 'Выбрать',
        'UNIT_NOT_FOUND' => 'Узел не найден',
        'CANCEL_PROCESSING' => 'Отменить обработку',
        'USER_NAMING' => 'Пользовательское название',
        'SYSTEM_NAMING' => 'Системное название',
    ];
}
