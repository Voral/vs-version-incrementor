# Семантическое обновление версии

[EN](https://github.com/Voral/vs-version-incrementor/blob/master/README.md)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Voral/vs-version-incrementor/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Voral/vs-version-incrementor/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Voral/vs-version-incrementor/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Voral/vs-version-incrementor/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/Voral/vs-version-incrementor/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Этот инструмент автоматизирует процесс обновления версий в Composer-проектах на основе анализа Git-коммитов и генерации
CHANGELOG. Он помогает соблюдать семантическое версионирование и
стандарт [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/).

Версия строятся согласно семантическому правилу <Мажорная>.<Минорная>.<Патч>

- *Мажорная* - изменяется при серьезных обновлениях, обновлениях ломающих обратную совместимость и т.п.
- *Минорное* - добавление новых функций без изменения существующих и без нарушения обратной совместимости
- *Патч* - Незначительные изменения

## Основные функции

- Анализ текущей версии из composer.json.
- Определение типа изменения (major, minor, patch) на основе коммитов.
- Обновление файла composer.json с новой версией.
- Создание тегов Git для релизов и коммит.
- Поддержка пользовательских конфигураций для типов коммитов
- Поддержка пользовательского форматирования CHANGELOG

## Установка

```bash
composer require --dev voral/version-increment
```

### Использование

Примеры использования утилиты

```bash
# Автоматическое определение типа релиза
./vendor/bin/vs-version-increment

# Увеличение мажорной версии
./vendor/bin/vs-version-increment major

# Увеличение минорной версии
./vendor/bin/vs-version-increment minor

# Увеличение патч-версии
./vendor/bin/vs-version-increment patch
```
Вызов справки по использованию утилиты

```bash
./vendor/bin/vs-version-increment --help
```

Получение списка зарегистрированных типов коммита

```bash
./vendor/bin/vs-version-increment --list
```

Флаг `--debug` позволяет предварительно просмотреть изменения, которые будут внесены в CHANGELOG и версию, без их реального применения.

```bash
# Автоматическое определение типа релиза
./vendor/bin/vs-version-increment --debug

# Увеличение мажорной версии
./vendor/bin/vs-version-increment --debug major

# Увеличение минорной версии
./vendor/bin/vs-version-increment --debug minor

# Увеличение патч-версии
./vendor/bin/vs-version-increment --debug patch
```

Для упрощения можно добавить скрипты в composer.json

```json
{
  "scripts": {
    "vinc:major": "php ./vendor/bin/vs-version-increment major",
    "vinc:minor": "php ./vendor/bin/vs-version-increment minor",
    "vinc:patch": "php ./vendor/bin/vs-version-increment patch",
    "vinc:auto": "php ./vendor/bin/vs-version-increment",
    "vinc:list": "php ./vendor/bin/vs-version-increment --list",
    "vinc:debug:auto": "php ./vendor/bin/vs-version-increment --debug"
  }
}
```

Пример выходного файла

```bash

# 1.0.1 (2023-10-01)

### Features
- New endpoint user authentication
- Added support dark mode

### Fixes
- Fixed a bug with login form validation
- Resolved issue with incorrect API response

### Other
- Updated dependencies
```

## Конфигурирование

Вы можете конфигурировать скрипт. Путем размещения в каталоге проекта файла `.vs-version-increment.php`  и выполнения в
нем следующих настроек

- [Установка своего списка типов изменений](docs/config.ru.md#установка-своего-списка-типов-изменений)
- [Настройка типа изменения](docs/config.ru.md#настройка-типа-изменения)
- [Настройка типа для релизов](docs/config.ru.md#настройка-типа-для-релизов)
- [Конфигурирование основной ветки репозитория](docs/config.ru.md#конфигурирование-основной-ветки-репозитория)
- [Настройка типов для Мажорной версии](docs/config.ru.md#настройка-типов-для-мажорной-версии)
- [Настройка типов для Минорной версии](docs/config.ru.md#настройка-типов-для-минорной-версии)
- [Настройка релизной группы](docs/config.ru.md#настройка-релизной-группы)
- [Настройка собственных правил распределения по типам](docs/config.ru.md#настройка-собственных-правил-распределения-по-типам)
- [Игнорирование не отслеживаемых файлов](docs/config.ru.md#игнорирование-не-отслеживаемых-файлов)
- [Настройка форматирования CHANGELOG](docs/config.ru.md#настройка-форматирования-changelog)
  - [Использование форматера, сохраняющего скоупы](docs/config.ru.md#использование-форматера-сохраняющего-скоупы)
  - [Создание собственного форматера](docs/config.ru.md#создание-собственного-форматера)
- [Настройка сквошенных коммитов](docs/config.ru.md#настройка-сквошенных-коммитов)
  - [Объединяющий коммит по умолчанию](docs/config.ru.md#объединяющий-коммит-по-умолчанию)
  - [Определение сквошенного коммита через группу](docs/config.ru.md#определение-сквошенного-коммита-через-группу)
  - [Комбинированное определение сквошенного коммита](docs/config.ru.md#комбинированное-определение-сквошенного-коммита)
  - [Общие правила полного описания коммита](docs/config.ru.md#общие-правила-полного-описания-коммита)
- [Настройка парсера описания коммитов](docs/config.ru.md#настройка-парсера-описания-коммитов)  
- [Отключение обновления версии в composer.json](docs/config.ru.md#отключение-обновления-версии-в-composerjson)  
  
## Описания коммитов

Для правильного функционирования утилиты описания коммитов необходимо формировать по следующему шаблону

```
<type>[(scope)][!]: <description>

[body]
```

*type* - тип коммита. Рекомендуется использовать типовой для конкретного проекта список. По типу изменения будут
группироваться в истории изменений. Если указан не зарегистрированный тип - изменение будет отнесено к типу
по-умолчанию. Так же имеет значение тип настроенный как тип относящийся к новому функционалу (по-умолчанию: feat) - при
автоопределнии в случае наличия среди изменений с прошлого релиза коммитов с таким типом будет изменена минорная версия

*scope* - Не обязательный. Группа к которой относится коммит

*!* - Признак того, что коммит нарушает обратную совместимость. При автоопределении типа изменений и при наличии такого
признака будет увеличена мажорная версия

*description* - Короткое описание.

*body* - Подробное описание коммита в работе утилиты не используется.

Примеры:

```
feat(router): New endpoint
```

```
doc: Described all features
```

```
feat!: Removed old API endpoints
```

## Типы коммитов по умолчанию

| Тип        | Назначение                                                           |
|------------|----------------------------------------------------------------------|
| `feat`     | Добавление нового функционала                                        |
| `fix`      | Исправление ошибок                                                   |
| `chore`    | Рутинные задачи (например, обновление зависимостей)                  |
| `docs`     | Изменения в документации                                             |
| `style`    | Форматирование кода (отступы, пробелы и т.д.)                        |
| `refactor` | Рефакторинг кода без добавления новых функций или исправления ошибок |
| `test`     | Добавление или изменение тестов                                      |
| `perf`     | Оптимизация производительности                                       |
| `ci`       | Настройка непрерывной интеграции (CI)                                |
| `build`    | Изменения, связанные со сборкой проекта                              |
| `other`    | Все остальные изменения, не попадающие под стандартные категории     |

## Интеграция с CI/CD

Скрипт можно интегрировать c CI/CD. При возникновении ошибок он возвращает различные коды

| Код | Описание                                |
|-----|-----------------------------------------|
| 10  | Ошибка конфигурации composer            |
| 20  | Ошибка ветки Git не основная            |
| 30  | Незакоммиченные изменения в репозитории |
| 40  | В репозитории нет изменений             |
| 50  | Неверный конфигурационный файл          |
| 60  | Ошибка выполнения команды git           |
| 70  | Неверный тип изменения версии           |
| 500 | Прочие ошибки                           |

Можете использовать в командной строке, например:

```bash
./vendor/bin/vs-version-increment && echo 'Ok' || echo 'Error'
```

Пример для Github Actions

```
jobs:
  version-update:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v3

      - name: Run version increment script
        run: ./vendor/bin/vs-version-increment
```

## Примеры конфигураций

Чтобы помочь вам быстрее начать работу с библиотекой, предлагаю готовые примеры конфигураций для различных сценариев использования.

### 1. Конфигурация для Keep a Changelog

Этот пример конфигурации предназначен для проектов, которые следуют стандарту [Keep a Changelog](https://keepachangelog.com/). Он отображает изменения в виде категорий (`Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, `Security`), что делает changelog удобным для чтения.

- *Файл:* [`examples/keepachangelog.php`](https://github.com/Voral/vs-version-incrementor/blob/master/examples/keepachangelog.php)

## Полезные ссылки

- [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)
- [Semantic Versioning](https://semver.org/)
- [Репозиторий проекта](https://github.com/Voral/vs-version-incrementor)
