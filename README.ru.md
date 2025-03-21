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

## Установка

```bash
composer require --dev voral/version-increment
```

### Использование

Для автоматического выбора типа нового релиза

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

Получение списка зарегистрированных типов коммита

```bash
./vendor/bin/vs-version-increment --list
```

Для упрощения можно добавить скрипты в composer.json

```json
{
  "scripts": {
    "vinc:major": "php ./vendor/bin/vs-version-increment major",
    "vinc:minor": "php ./vendor/bin/vs-version-increment minor",
    "vinc:patch": "php ./vendor/bin/vs-version-increment patch",
    "vinc:auto": "php ./vendor/bin/vs-version-increment",
    "vinc:list": "php ./vendor/bin/vs-version-increment --list"
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

### Установка своего списка типов изменений

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSections([
        'custom1' => [
            'title' => 'Custom Type 1', 
            'order' => 20,
            'hidden' => false,
        ],   
        'custom2' => [
            'title' => 'Custom Type 2', 
            'order' => 10,
            'hidden' => false,
        ],   
    ]);
```

Где в качестве ключа код типа, который используется в формировании описания коммита. Каждый тип описывается тремя не
обязательными параметрами:

- *title* - заголовок группы в файле CHANGELOG
- *order* - порядок сортировки группы в файле CHANGELOG
- *hidden* - если `true` группа будет скрыта из файла CHANGELOG

Так же стоит обратить внимание:

- Если отсутствует тип `other` - он будет добавлен автоматически
- Если отсутствует тип соответствующий новом функционалу (по умолчанию `feat`) - то при автоматическом типе изменений не
  будет происходить смена минорной версии

### Настройка типа изменения

Можно настроить существующий тип и отдельно добавить новый

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('feat','Features') // Изменяем только заголовок
    ->setSection('fix','Fixes', 1)  // Изменяем заголовок и сортировку
    ->setSection('ci','CI', hidden: false) // Скрываем из CHANGELOG
    ->setSection('custom3','My custom type',400,  false) // Добавляем свой, который скрыт из CHANGELOG
    ;
```

### Настройка типа для релизов

При помощи данной настройки можно изменять (по умолчанию `chore`) тип, который будет использоваться для формирования
описания релизного коммита.

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('release','Releases', hidden: false)
    ->setReleaseSection('release');
```

### Конфигурирование основной ветки репозитория

По умолчанию основной веткой репозитория считается ветка `master`. Однако можно изменить это поведение при помощи
настройки

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setMasterBranch('main');
```

### Настройка типов для Мажорной версии

При автоматическом определении по умолчанию мажорная версия увеличивается только при наличии флага `!`. Однако можно
настроить типы коммитов при наличии которых в изменениях будет автоматически увеличена мажорная версия

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('global','Global Changes')
    ->setMajorTypes(['global']);
```

### Настройка типов для Минорной версии

При автоматическом определении по умолчанию минорная версия увеличивается только при наличии флага `feat` среди
коммитов. Однако можно настроить типы коммитов при наличии которых в изменениях будет автоматически увеличена минорная
версия

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setMajorTypes(['feat','fix']);
```

### Настройка релизной группы

При создании релиза создается коммит и в описании, по умолчанию, отображается группа `release`.

Его можно переназначить

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setReleaseScope('rel');
```

И тогда описание коммита будет выглядеть например так:

```
chore(rel): v3.0.0
```

Или убрать совсем

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setReleaseScope('');
```

И тогда описание коммита будет выглядеть например так:

```
chore: v3.0.0
```
### Настройка собственных правил распределения по типам

Иногда есть необходимость настроить собственные правила распределения коммитов по типам, для этого реализована система правил. Для этого создавайте свои правила имплементирующие интерфейс [Vasoft\VersionIncrement\SectionRules\SectionRuleInterface](https://github.com/Voral/vs-version-incrementor/blob/master/src/SectionRules/SectionRuleInterface.php). И устанавливайте их для соответствующих типов коммитов
```php
class ExampleRule1 implements SectionRuleInterface
{
    public function __invoke(string $type, string $scope, array $flags, string $comment): bool
    {
        return 'add' === $type;
    }
}

class ExampleRule2 implements SectionRuleInterface
{
    public function __invoke(string $type, string $scope, array $flags, string $comment): bool
    {
        return str_starts_with(strtolower($comment), 'added');
    }
}

return (new \Vasoft\VersionIncrement\Config())
    ->addSectionRule('feat', new ExampleRule1())
    ->addSectionRule('feat', new ExampleRule2());
```

### Игнорирование не отслеживаемых файлов

При запуске утилиты необходимо чтобы были приняты все изменения, а так же, по умолчанию, отсутствовали не отслеживаемые
файлы. Чтобы игнорировать наличие не отслеживаемых файлов, необходимо выполнить следующую настройку.

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setIgnoreUntrackedFiles(true);
```

### Настройка сквошенных коммитов

В некоторых проектах возникает необходимость работы со сквошенными коммитами, например, созданными командой `git merge --squash some-branch`. Для обработки таких коммитов предусмотрены следующие возможности:

#### Объединяющий коммит по умолчанию

Если описание сквошенного коммита не изменяется, то оно имеет следующую форму:

```text
Squashed commit of the following:

commit 2bf0dc5a010f17abc35d15c0f816c636d81cbfd2
Author: Author: Name Lastname <devemail@email.com>
Date:   Sun Mar 23 15:20:02 2023 +0300

   docs: update README with configuration examples 5
   
commit cbae8944207f28a6676a493cf2d9f591ce3c1756
Author: Author: Name Lastname <devemail@email.com>
Date:   Sun Mar 23 15:19:55 2023 +0300

   docs: update README with configuration examples 4

```

Для обработки таких коммитов включите соответствующую настройку (по умолчанию выключено):

```php
return (new Config())
    ->setProcessDefaultSquashedCommit(true);
```

Если первая строка коммита отличается от стандартной (`Squashed commit of the following:`), её можно настроить:

```php
return (new Config())
    ->setSquashedCommitMessage('Squashed commit:')
    ->setProcessDefaultSquashedCommit(true);
```

### Определение сквошенного коммита через группу

Сквошенный коммит также может быть связан с определенной группой. В этом случае коммиты, относящиеся к этой группе, будут распознаваться как сквошенные. Настройка выполняется следующим образом:

```php
return (new Config())
    ->setAggregateSection('aggregate');
```
### Комбинированое определение сквошенного коммита

Вы можете кобинировать оба варианта
```php
return (new Config())
    ->setAggregateSection('aggregate')
    ->setSquashedCommitMessage('Squashed commit:')
    ->setProcessDefaultSquashedCommit(true);
```

### Общие правила полного описания коммита

В обоих случаях подробное описание коммитов должно содержать список изменений в формате Conventional Commits. При этом:

- рассматриваются только строки имеющий такой формат.
- в начале таких строк допускаются пробелы, символы табуляции, `-` и `*`.
- возможно применение признака изменения нарушающего обратную совместимость

Например

```text
commit 2bf0dc5a010f17abc35d15c0f816c636d81cbfd2
Author: Author: Name Lastname <devemail@email.com>
Date:   Sun Mar 23 15:20:02 2025 +0300

   docs: update README with configuration examples 1
   
 -  docs: update README with configuration examples 2
 *  docs: update README with configuration examples 3
 -  docs!: update README with configuration examples 4
```

В результате будет увеличена мажорная версия и в CHANGELOG будут добавлены в раздел Документация следующий записи

```text
### Documentation
 - update README with configuration examples 1
 - update README with configuration examples 2
 - update README with configuration examples 3
 - update README with configuration examples 4
```

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

- **Файл:** [`examples/keepachangelog.php`](https://github.com/Voral/vs-version-incrementor/blob/master/examples/keepachangelog.php)

## Полезные ссылки

- [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)
- [Semantic Versioning](https://semver.org/)
- [Репозиторий проекта](https://github.com/Voral/vs-version-incrementor)
