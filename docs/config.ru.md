# Конфигурирование

Вы можете конфигурировать скрипт. Путем размещения в каталоге проекта файла `.vs-version-increment.php`  и выполнения в
нем следующих настроек

## Установка своего списка типов изменений

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

## Настройка типа изменения

Можно настроить существующий тип и отдельно добавить новый

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('feat','Features') // Изменяем только заголовок
    ->setSection('fix','Fixes', 1)  // Изменяем заголовок и сортировку
    ->setSection('ci','CI', hidden: false) // Скрываем из CHANGELOG
    ->setSection('custom3','My custom type',400,  false) // Добавляем свой, который скрыт из CHANGELOG
    ;
```

## Настройка типа для релизов

При помощи данной настройки можно изменять (по умолчанию `chore`) тип, который будет использоваться для формирования
описания релизного коммита.

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('release','Releases', hidden: false)
    ->setReleaseSection('release');
```

## Конфигурирование основной ветки репозитория

По умолчанию основной веткой репозитория считается ветка `master`. Однако можно изменить это поведение при помощи
настройки

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setMasterBranch('main');
```

## Настройка типов для Мажорной версии

При автоматическом определении по умолчанию мажорная версия увеличивается только при наличии флага `!`. Однако можно
настроить типы коммитов при наличии которых в изменениях будет автоматически увеличена мажорная версия

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('global','Global Changes')
    ->setMajorTypes(['global']);
```

## Настройка типов для Минорной версии

При автоматическом определении по умолчанию минорная версия увеличивается только при наличии флага `feat` среди
коммитов. Однако можно настроить типы коммитов при наличии которых в изменениях будет автоматически увеличена минорная
версия

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setMajorTypes(['feat','fix']);
```

## Настройка релизной группы

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

## Настройка собственных правил распределения по типам

Иногда есть необходимость настроить собственные правила распределения коммитов по типам, для этого реализована система
правил. Для этого создавайте свои правила имплементирующие
интерфейс [Vasoft\VersionIncrement\Contract\SectionRuleInterface](https://github.com/Voral/vs-version-incrementor/blob/master/src/Contract/SectionRuleInterface.php).
И устанавливайте их для соответствующих типов коммитов

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

Классы правил в пакете (расположены в пространстве имен \Vasoft\VersionIncrement\SectionRules):

- `DefaultRule` - Правило раздела по умолчанию, которое применяется ко всем типам коммитов. Выполняется, если тип
  коммита соответствует коду раздела. Добавляется автоматически к каждому разделу.
- `BreakingRule` - Правило для коммитов, нарушающих обратную совместимость. Коммиты с признаком нарушения обратной
  совместимости (например, !) попадают в раздел, если они не были распределены в другие разделы согласно правилам
  сортировки. Пример:

```php
use \Vasoft\VersionIncrement\SectionRules;
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('breaking', 'BREAKING CHANGES', 0);
    ->addSectionRule('breaking', new SectionRules\BreakingRule());
```

> Примечание: Убедитесь, что раздел 'breaking' находится в правильной последовательности сортировки, чтобы правило
> работало корректно.

## Игнорирование не отслеживаемых файлов

При запуске утилиты необходимо чтобы были приняты все изменения, а так же, по умолчанию, отсутствовали не отслеживаемые
файлы. Чтобы игнорировать наличие не отслеживаемых файлов, необходимо выполнить следующую настройку.

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setIgnoreUntrackedFiles(true);
```

## Настройка форматирования CHANGELOG

По умолчанию в `CHANGELOG.md` *не сохраняются скоупы* из коммитов. Однако, если вам нужно изменить это поведение, вы
можете использовать один из следующих подходов:

### Использование форматера, сохраняющего скоупы

Если вы хотите сохранить определённые скоупы в `CHANGELOG.md`, используйте класс `ScopePreservingFormatter` из
пространства имён `Vasoft\VersionIncrement\Changelog`.

#### Особенности работы `ScopePreservingFormatter`:

- Форматтер принимает массив скоупов или интерпретаторов скоупов в конструкторе.
- Если массив скоупов пустой, то сохраняются *все скоупы*.
- В противном случае будут включены только скоупы, соответствующие указанным шаблонам или интерпретаторам.

#### Базовое использование со статическими скоупами:

```php
use Vasoft\VersionIncrement\Changelog\ScopePreservingFormatter;

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new ScopePreservingFormatter(['dev', 'deprecated']));
```

В этом примере в `CHANGELOG.md` будут сохранены только комментарии с скоупами `dev` и `deprecated`. Остальные скоупы
будут игнорироваться.

#### Продвинутое использование с динамической интерпретацией скоупов:

Для более сложных сценариев вы можете использовать `RegexpScopeInterpreter` или `SinglePreservedScopeInterpreter` для
динамического преобразования скоупов на основе регулярных выражений:

```php
use Vasoft\VersionIncrement\Changelog\ScopePreservingFormatter;
use Vasoft\VersionIncrement\Changelog\Interpreter\RegexpScopeInterpreter;
use Vasoft\VersionIncrement\Changelog\Interpreter\SinglePreservedScopeInterpreter;

$config = new \Vasoft\VersionIncrement\Config();

return $config
    ->addScope('dev', 'Development')
    ->setChangelogFormatter(new ScopePreservingFormatter([
        new RegexpScopeInterpreter(
            '#^task(\d+)$#', 
            '[Task $1](https://tracker.company.com/task/$1): '
        ),
        new RegexpScopeInterpreter(
            '#^JIRA-(\w+)-(\d+)$#', 
            '[JIRA-$1-$2](https://jira.company.com/browse/JIRA-$1-$2): '
        ),
        // Фильтрация и отображение конкретного скоупа 'dev'
        // с форматированием и заменой на человеко-читаемое представление
         new SinglePreservedScopeInterpreter(['dev'], $config, '{%s}'),
        'database', // Статический скоуп для обратной совместимости
        'api'       // Статический скоуп
    ]));
```

Как это работает:

- Форматтер обрабатывает скоупы в порядке их определения в массиве
- Для каждого скоупа коммита проверяются все интерпретаторы и статические скоупы
- Если `RegexpScopeInterpreter` или `SinglePreservedScopeInterpreter` соответствует шаблону скоупа, возвращается
  преобразованная строка
- Если статическая строка точно совпадает, используется маппинг скоупов из конфигурации
- Первый совпавший интерпретатор или скоуп побеждает

Примеры преобразований:

- task123 → `[Task 123](https://tracker.company.com/task/123):`
- JIRA-FEAT-456 → `[JIRA-FEAT-456](https://jira.company.com/browse/JIRA-FEAT-456):`
- database → database: (если есть в конфиге) или Database: (с человеко-читаемым заголовком)
- dev → {Development}

#### Смешанное использование со статическими и динамическими скоупами:

Вы можете комбинировать оба подхода для максимальной гибкости:

```php
use Vasoft\VersionIncrement\Changelog\ScopePreservingFormatter;
use Vasoft\VersionIncrement\Changelog\Interpreter\RegexpScopeInterpreter;

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new ScopePreservingFormatter([
        // Динамические интерпретаторы для систем отслеживания задач
        new RegexpScopeInterpreter('#^task(\d+)$#', '[Task $1](https://tracker.com/task/$1): '),
        new RegexpScopeInterpreter('#^issue-(\d+)$#', '[Issue $1](https://issues.com/issue/$1): '),
        
        // Статические скоупы для общих модулей
        'database',
        'api',
        'ui'
    ]))
    ->addScope('database', 'Database')
    ->addScope('api', 'API')
    ->addScope('ui', 'User Interface');
```

Эта конфигурация предоставляет как динамические ссылки для трекеров задач, так и чистые человеко-читаемые заголовки для
общих скоупов.

#### Расширение возможностей с помощью пользовательских интерпретаторов скоупов

Для максимальной гибкости вы можете создавать собственные интерпретаторы скоупов, реализуя интерфейс
`Vasoft\VersionIncrement\Contract\ScopeInterpreterInterface`. Это позволяет реализовать сложную логику, выходящую за
рамки регулярных выражений, такую как API-вызовы, запросы к базам данных или пользовательские правила преобразования.

Пример пользовательского интерпретатора:

```php
use Vasoft\VersionIncrement\Contract\ScopeInterpreterInterface;

class JiraScopeInterpreter implements ScopeInterpreterInterface
{
    public function __construct(private readonly string $jiraBaseUrl) {}
    
    public function interpret(string $scope): ?string
    {
        // Сопоставление с шаблоном JIRA-тикета (например, PROJ-123, FEAT-456)
        if (preg_match('#^([A-Z]+)-(\d+)$#', $scope, $matches)) {
            $project = $matches[1];
            $ticketId = $matches[2];
            $url = "{$this->jiraBaseUrl}/browse/{$project}-{$ticketId}";
            return "[{$project}-{$ticketId}]({$url}): ";
        }
        
        return null;
    }
}
```

Использование в конфигурации:

```php
use Vasoft\VersionIncrement\Changelog\ScopePreservingFormatter;

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new ScopePreservingFormatter([
        new JiraScopeInterpreter('https://company.atlassian.net'),
    ]));
```

Преимущества пользовательских интерпретаторов:

- Сложная логика: Обработка нескольких вариантов шаблонов в одном интерпретаторе
- Внешние данные: Интеграция с внешними системами (API, базы данных)
- Бизнес-правила: Реализация специфичных для проекта правил преобразования
- Многократное использование: Совместное использование интерпретаторов в нескольких проектах
- Тестируемость: Каждый интерпретатор может быть независимо покрыт модульными тестами

Этот подход обеспечивает неограниченную расширяемость для обработки сложных требований к преобразованию скоупов в вашем
проекте.

### Использование форматера, сохраняющего несколько скоупов

На базе `ScopePreservingFormatter` библиотека предоставляет `MultipleScopePreservingFormatter` для обработки сообщений
коммитов, в которых поле скоупа содержит несколько значений, разделённых определённым символом (например,
`feat(api|db|frontend): ...`). Этот форматтер позволяет точно настроить, какие из этих нескольких скоупов будут включены
в `CHANGELOG.md`, и как они будут отформатированы в совокупности.

#### Ключевые особенности `MultipleScopePreservingFormatter`:

- **Обработка нескольких скоупов**: Разделяет строку скоупа коммита (например, `api|db|frontend`) с помощью
  настраиваемого разделителя источника (по умолчанию `|`).
- **Обработка каждого скоупа индивидуально**: Каждая отдельная часть скоупа обрабатывается относительно списка
  сохраняемых скоупов (который может включать реализации `ScopeInterpreterInterface`, такие как
  `SinglePreservedScopeInterpreter` или `RegexpScopeInterpreter`, либо строковые значения) с использованием той же
  логики, что и `ScopePreservingFormatter`.
- **Фильтрация скоупов**: Скоупы, не попадающие в список сохраняемых, отфильтровываются.
- **Объединение**: Сохранённые и обработанные отдельные скоупы объединяются обратно с помощью настраиваемого разделителя
  назначения (по умолчанию `|`).
- **Общее форматирование**: К итоговой строке объединённых скоупов применяется общий шаблон, позволяющий управлять
  префиксом/суффиксом, добавляемым ко всему блоку скоупов (например, `'%s: '` даст `api|db: `).
- **Независимость от `outputTemplate` родителя**: В отличие от `ScopePreservingFormatter`, этот форматтер *не
  использует* `outputTemplate` родительского класса. Форматирование управляется исключительно параметром
  `overallTemplate`.

#### Базовое использование:

```php
use Vasoft\VersionIncrement\Changelog\MultipleScopePreservingFormatter;

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new MultipleScopePreservingFormatter(['api', 'db']));
```

В этом примере, если коммит имеет скоуп `api|db|frontend`, в `CHANGELOG.md` будет сохранён только `api|db`. Скоуп
`frontend` будет проигнорирован. Результат будет отформатирован с использованием шаблона по умолчанию `'%s '`, что даст
`api|db ` перед описанием коммита.

#### Продвинутое использование с интерпретаторами:

Вы можете использовать интерпретаторы, такие как `SinglePreservedScopeInterpreter` или `RegexpScopeInterpreter`, внутри
`MultipleScopePreservingFormatter`:

```php
use Vasoft\VersionIncrement\Changelog\MultipleScopePreservingFormatter;
use Vasoft\VersionIncrement\Changelog\Interpreter\SinglePreservedScopeInterpreter;
use Vasoft\VersionIncrement\Changelog\Interpreter\RegexpScopeInterpreter;

$config = new \Vasoft\VersionIncrement\Config();
$config->addScope('api', 'API Module')
       ->addScope('db', 'Database Layer');

return $config
    ->setChangelogFormatter(new MultipleScopePreservingFormatter([
        'api',
        'db',
        new RegexpScopeInterpreter('#^task(\d+)$#', '[Task $1]'),
        new SinglePreservedScopeInterpreter(['dev'], $config, '{%s}'),
    ]));
```

Если коммит имеет скоуп `api|task456|dev|unknown`, и `unknown` не в списке сохраняемых, результатом (с настройками по
умолчанию) будет `API Module|[Task 456]|{dev}`.

#### Настройка разделителей и шаблона:

Вы можете настроить разделители и общий шаблон:

```php
use Vasoft\VersionIncrement\Changelog\MultipleScopePreservingFormatter;

// Используем '#' как разделитель в коммите, ',' для объединения в changelog,
// и добавляем двоеточие и пробел в конце
$formatter = new MultipleScopePreservingFormatter(
    preservedScopes: ['api', 'db'],
    srcSeparator: '#',      // Разделитель в исходном скоупе коммита
    dstSeparator: ',',      // Разделитель между скоупами в ченджлоге
    overallTemplate: '%s: ' // Общий шаблон форматирования
);

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter($formatter);
```

Если коммит `feat(api#db): ...`, результат будет `API Module,Database Layer: ...` (предполагая, что `addScope`
использовалось для отображения).

### Настройка человеко-читаемых заголовков для скоупов

Вы можете настроить человекочитаемые заголовки для скоупов, которые будут использоваться в `CHANGELOG.md`. Это позволяет
заменять технические названия скоупов на более понятные для пользователей описания.
Настройка учитывается при использовании ScopePreservingFormatter, либо вы можете использовать в своей реализации
`Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface`

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new ScopePreservingFormatter(['dev', 'deprecated']))
    ->addScope('deprecated', 'Deprecated Features');
```

Зарегистрированные скоупы так же выводятся командой `./vendor/bin/vs-version-increment --list` в дополнение к списку
типов коммитов

### Создание собственного форматера

Если стандартные форматеры не удовлетворяют вашим требованиям, вы можете создать собственный форматер. Для этого
необходимо реализовать интерфейс `Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface`.

#### Требования к собственному форматеру:

- Класс должен реализовать метод `__invoke`, который принимает два параметра:
    - `CommitCollection $commitCollection`: коллекция коммитов, сгруппированных по секциям.
    - `string $version`: номер версии, для которой генерируется changelog.
- Метод должен возвращать строку с отформатированным содержимым `CHANGELOG.md`.

Пример реализации собственного форматера:

```php
namespace MyApp\Custom;

use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface;

class CustomFormatter implements ChangelogFormatterInterface
{
    private ?Config $config = null;
    
    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    public function __invoke(CommitCollection $commitCollection, string $version): string
    {
        // Ваша логика форматирования
        return "Custom changelog for version {$version}:\n";
    }
}
```

#### Пример подключения собственного форматера:

```php
use MyApp\Custom\CustomFormatter;

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new CustomFormatter());
```

## Настройка сквошенных коммитов

В некоторых проектах возникает необходимость работы со сквошенными коммитами, например, созданными
командой `git merge --squash some-branch`. Для обработки таких коммитов предусмотрены следующие возможности:

### Объединяющий коммит по умолчанию

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

Сквошенный коммит также может быть связан с определенной группой. В этом случае коммиты, относящиеся к этой группе,
будут распознаваться как сквошенные. Настройка выполняется следующим образом:

```php
return (new Config())
    ->setAggregateSection('aggregate');
```

### Комбинированное определение сквошенного коммита

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

## Настройка парсера описания коммитов

Парсер обрабатывает строку в соответствии со стандартом [Conventional Commits](https://www.conventionalcommits.org/),
который определяет формат сообщений коммитов для удобства автоматической обработки. Вы можете изменить это поведение
установив собственный парсер имплементирующий интерфейс Vasoft\VersionIncrement\Contract\CommitParserInterface

```php
use Vasoft\VersionIncrement\Contract\CommitParserInterface;
use Vasoft\VersionIncrement\Contract\VcsExecutorInterface;

class MyParser implements CommitParserInterface
{
    
    private ?Config $config = null;
    
    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }
    
    public function process(?string $tagsFrom, string $tagsTo = ''): CommitCollection
    {
        // Ваша логика парсинга
    }
}

$config = new Config(); 
return $config
    ->setCommitParser(new MyParser());
```

## Отключение обновления версии в composer.json

В некоторых проектах управление версией может осуществляться только через Git теги, без обновления
файла `composer.json`. Для этого предусмотрена возможность отключения работы с версией в `composer.json`.

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setEnabledComposerVersioning(false);
```

При отключении данной настройки:

- Версия будет определяться исключительно на основе Git тегов.
- Файл `composer.json` не будет анализироваться и обновляться.
- Все операции с версией будут выполняться только в контексте тегов.

По умолчанию обновление версии в `composer.json` включено.

## Передача произвольных параметров конфигурации

Вы можете сохранять произвольные пары ключ-значение в конфигурации. Эти параметры вы можете использовать в своих
реализациях(например форматере CHANGELOG, парсера коммитов и т.п.).

 ```php
     return (new \Vasoft\VersionIncrement\Config())
         ->set(\MyApp\Custom\CustomFormatter::PARAM_KEY, 'customValue');
```

Далее вы можете использовать это следующим образом:

```php
namespace MyApp\Custom;

use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface;

class CustomFormatter implements ChangelogFormatterInterface
{
    public const PARAM_KEY = 'customParam';
    private ?Config $config = null;
    
    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }
    
    public function __invoke(CommitCollection $commitCollection, string $version): string
    {
        // Your custom formatting logic
        return $this->config->get(self::PARAM_KEY)." Custom changelog for version {$version}:\n";
    }
}
```

## Скрытие повторяющихся строк в CHANGELOG

Вы можете настроить, должны ли скрываться повторяющиеся записи (строки с одинаковым содержимым) в генерируемом
CHANGELOG. При включении этой функции только первое вхождение повторяющейся записи будет отображаться внутри каждой
секции. Это повышает удобочитаемость CHANGELOG, уменьшая избыточность и делая его более лаконичным.

> **Примечание:** Повторяющиеся записи скрываются только в пределах одной и той же секции. Записи из разных секций не
> затрагиваются.

Чтобы включить скрытие повторяющихся записей, используйте следующую конфигурацию:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setHideDoubles(true);
```
