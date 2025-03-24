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

## Игнорирование не отслеживаемых файлов

При запуске утилиты необходимо чтобы были приняты все изменения, а так же, по умолчанию, отсутствовали не отслеживаемые
файлы. Чтобы игнорировать наличие не отслеживаемых файлов, необходимо выполнить следующую настройку.

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setIgnoreUntrackedFiles(true);
```

## Настройка сквошенных коммитов

В некоторых проектах возникает необходимость работы со сквошенными коммитами, например, созданными командой `git merge --squash some-branch`. Для обработки таких коммитов предусмотрены следующие возможности:

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

Сквошенный коммит также может быть связан с определенной группой. В этом случае коммиты, относящиеся к этой группе, будут распознаваться как сквошенные. Настройка выполняется следующим образом:

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
