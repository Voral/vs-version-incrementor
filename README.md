# Семантическое обновление версии


> ⚠️ **Внимание:**
> 
> В разработке.
 
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
composer require dev voral/version-increment
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

Пример выходного файла
```bash

# 1.0.1 (2023-10-01)

### Features
- New endpoint for user authentication
- Added support for dark mode

### Fixes
- Fixed a bug with login form validation
- Resolved issue with incorrect API response

### Other
- Updated dependencies
```

## Конфигурирование

Вы можете конфигурировать скрипт. Путем размещения в каталоге проекта файла `.vs-version-increment.php`  и выполнения в
нем
следующих настроек

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
описания релизного комиита.

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

*scope* - Не обязательный. Область проекта к которой относится коммит

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
| 40  | В репозитории нет измненений            |
| 60  | Ошибка выполнения команды git           |
| 70  | Неверный тип изменения версии           |
| 500 | Прочие ошибки                           |

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

## Полезные ссылки

- [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)
- [Semantic Versioning](https://semver.org/)
- [Репозиторий проекта](https://github.com/your-repo)
