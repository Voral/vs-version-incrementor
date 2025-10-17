# Configuration

You can configure the script by placing a `.vs-version-increment.php` file in the project directory and making the
following adjustments:

## Setting a Custom List of Change Types

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

Each type is described by three optional parameters:

- *title*: The group title in the CHANGELOG file.
- *order*: The sorting order of the group in the CHANGELOG file.
- *hidden*: If `true`, the group will be hidden from the CHANGELOG file.

Also, note the following:

- If the `other` type is missing, it will be added automatically.
- If the type corresponding to new functionality (default `feat`) is missing, the minor version will not change during
  automatic type detection.

## Configuring Change Types

You can modify existing types or add new ones:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('feat', 'Features') // Modify only the title
    ->setSection('fix', 'Fixes', 1)  // Modify the title and sorting
    ->setSection('ci', 'CI', hidden: true) // Hide from CHANGELOG
    ->setSection('custom3', 'My custom type', 400, false); // Add a new type that is hidden from CHANGELOG
```

## Configuring the Release Commit Type

By default, the release commit type is `chore`. You can customize this behavior:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('release', 'Releases', hidden: false)
    ->setReleaseSection('release');
```

## Configuring the Main Repository Branch

By default, the main branch is considered to be `master`. However, you can change this behavior:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setMasterBranch('main');
```

## Configuring Types for Major Version Updates

By default, the major version is incremented only when the `!` flag is present. However, you can configure specific
commit types to trigger a major version update:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('global', 'Global Changes')
    ->setMajorTypes(['global']);
```

## Configuring Types for Minor Version Updates

By default, the minor version is incremented only when the `feat` type is present among commits. You can configure other
types to trigger a minor version update:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setMajorTypes(['feat', 'fix']);
```

## Release Scope Configuration

When creating a release, a commit is generated, and by default, the scope `release` is displayed in the commit message.

You can customize it as follows:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setReleaseScope('rel');
```

In this case, the commit message will look like this:

```
chore(rel): v3.0.0
```

Alternatively, you can remove the scope entirely:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setReleaseScope('');
```

In this case, the commit message will look like this:

```
chore: v3.0.0
```

## Custom Type Distribution Rules Setup

Sometimes, there is a need to configure custom rules for distributing commits by type. For this purpose, a rule system
has been implemented. To achieve this, create your own rules by implementing
the [Vasoft\VersionIncrement\Contract\SectionRuleInterface](https://github.com/Voral/vs-version-incrementor/blob/master/src/Contract/SectionRuleInterface.php)
interface and assign them to the corresponding commit types.

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

Rule classes in the package (located in the `\Vasoft\VersionIncrement\SectionRules` namespace):

- `DefaultRule` - Default section rule that applies to all commit types. It is executed if the commit type matches the
  section code. Added automatically to each section.
- `BreakingRule` - Rule for commits that break backward compatibility. Commits with a breaking change indicator (
  e.g., `!`) are added to the section if they were not distributed to other sections according to sorting rules.
  Example:

```php
use \Vasoft\VersionIncrement\SectionRules;
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('breaking', 'BREAKING CHANGES', 0);
    ->addSectionRule('breaking', new SectionRules\BreakingRule());
```

> Note: Ensure that the 'breaking' section is in the correct sort order for the rule to work properly.

## Ignoring Untracked Files

When running the utility, all changes must be committed, and by default, there should be no untracked files. To ignore
the presence of untracked files, you need to apply the following configuration:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setIgnoreUntrackedFiles(true);
```

## Configuring CHANGELOG Formatting

By default, *scopes from commits are not preserved* in `CHANGELOG.md`. However, if you need to change this behavior, you
can use one of the following approaches:

### Using a Scope-Preserving Formatter

If you want to preserve specific scopes in `CHANGELOG.md`, use the `ScopePreservingFormatter` class from
the `Vasoft\VersionIncrement\Changelog` namespace.

#### How `ScopePreservingFormatter` Works:

- The formatter accepts an array of scopes or scope interpreters in its constructor.
- If the array of scopes is empty, *all scopes* will be preserved.
- Otherwise, only the scopes that match the specified patterns or interpreters will be included.

#### Basic Usage with Static Scopes:

```php
use Vasoft\VersionIncrement\Changelog\ScopePreservingFormatter;

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new ScopePreservingFormatter(['dev', 'deprecated']));
```

In this example, only comments with the dev and deprecated scopes will be preserved in CHANGELOG.md. All other scopes
will be ignored.

#### Advanced Usage with Dynamic Scope Interpretation:

For more complex scenarios, you can use `RegexpScopeInterpreter` to dynamically transform scopes based on regular
expressions:

```php
use Vasoft\VersionIncrement\Changelog\ScopePreservingFormatter;
use Vasoft\VersionIncrement\Changelog\Interpreter\RegexpScopeInterpreter;

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new ScopePreservingFormatter([
        new RegexpScopeInterpreter(
            '#^task(\d+)$#', 
            '[Task $1](https://tracker.company.com/task/$1): '
        ),
        new RegexpScopeInterpreter(
            '#^JIRA-(\w+)-(\d+)$#', 
            '[JIRA-$1-$2](https://jira.company.com/browse/JIRA-$1-$2): '
        ),
        'database', // Static scope for backward compatibility
        'api'       // Static scope
    ]));
```

How it works:

- The formatter processes scopes in the order they are defined in the array
- For each commit scope, it checks against all interpreters and static scopes
- If a `RegexpScopeInterpreter` matches the scope pattern, it returns the transformed string
- If a static string matches exactly, it uses the scope mapping from configuration
- The first matching interpreter or scope wins

Example transformations:

- task123 → `[Task 123](https://tracker.company.com/task/123):`
- JIRA-FEAT-456 → `[JIRA-FEAT-456](https://jira.company.com/browse/JIRA-FEAT-456):`
- database → database: (if mapped in config) or Database: (with human-readable title)

#### Mixed Usage with Static and Dynamic Scopes:

You can combine both approaches for maximum flexibility:

```php
use Vasoft\VersionIncrement\Changelog\ScopePreservingFormatter;
use Vasoft\VersionIncrement\Changelog\Interpreter\RegexpScopeInterpreter;

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new ScopePreservingFormatter([
        // Dynamic interpreters for task tracking systems
        new RegexpScopeInterpreter('#^task(\d+)$#', '[Task $1](https://tracker.com/task/$1): '),
        new RegexpScopeInterpreter('#^issue-(\d+)$#', '[Issue $1](https://issues.com/issue/$1): '),
        
        // Static scopes for common modules
        'database',
        'api',
        'ui'
    ]))
    ->addScope('database', 'Database')
    ->addScope('api', 'API')
    ->addScope('ui', 'User Interface');
```

This configuration provides both dynamic linking for issue trackers and clean human-readable titles for common scopes.

#### Extending with Custom Scope Interpreters

For maximum flexibility, you can create your own scope interpreters by implementing the
`Vasoft\VersionIncrement\Contract\ScopeInterpreterInterface`. This allows you to implement complex logic beyond regular
expressions, such as API calls, database lookups, or custom transformation rules.

Example Custom Interpreter:

```php
use Vasoft\VersionIncrement\Contract\ScopeInterpreterInterface;

class JiraScopeInterpreter implements ScopeInterpreterInterface
{
    public function __construct(private readonly string $jiraBaseUrl) {}
    
    public function interpret(string $scope): ?string
    {
        // Match JIRA ticket pattern (e.g., PROJ-123, FEAT-456)
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

Usage in Configuration:

```php
use Vasoft\VersionIncrement\Changelog\ScopePreservingFormatter;

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new ScopePreservingFormatter([
        new JiraScopeInterpreter('https://company.atlassian.net'),
    ]));
```

Benefits of Custom Interpreters:

- Complex Logic: Handle multiple pattern variations in one interpreter
- External Data: Integrate with external systems (APIs, databases)
- Business Rules: Implement project-specific transformation rules
- Reusability: Share interpreters across multiple projects
- Testability: Each interpreter can be unit tested independently

This approach provides unlimited extensibility for handling complex scope transformation requirements in your project.

### Configuring Human-Readable Titles for Scopes

You can configure human-readable titles for scopes that will be used in `CHANGELOG.md`. This allows you to replace
technical scope names with more user-friendly descriptions.
The configuration is taken into account when using `ScopePreservingFormatter`, or you can incorporate it into your own
implementation of `Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface`.

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new ScopePreservingFormatter(['dev', 'deprecated']))
    ->addScope('deprecated', 'Deprecated Features');
```

Registered scopes are also displayed by the command ./vendor/bin/vs-version-increment --list in addition to the list of
commit types.

### Creating a Custom Formatter

If the standard formatters do not meet your requirements, you can create your own custom formatter. To do so, implement
the `Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface`.

#### Requirements for a Custom Formatter:

- The class must implement the `__invoke` method, which takes two parameters:
    - `CommitCollection $commitCollection`: A collection of commits grouped into sections.
    - `string $version`: The version number for which the changelog is generated.
- The method must return a string containing the formatted content of `CHANGELOG.md`.

Example of a Custom Formatter Implementation:

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
        // Your custom formatting logic
        return "Custom changelog for version {$version}:\n";
    }
}
```

Example of Connecting a Custom Formatter:

```php
use MyApp\Custom\CustomFormatter;

return (new \Vasoft\VersionIncrement\Config())
    ->setChangelogFormatter(new CustomFormatter());
```

## Configuring Squashed Commits

In some projects, there may be a need to work with squashed commits, such as those created by
the `git merge --squash some-branch` command. The following options are available for handling such commits:

### Default Squashed Commit

If the description of the squashed commit is not modified, it will have the following format:

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

To process such commits, enable the corresponding setting (disabled by default):

```php
return (new Config())
    ->setProcessDefaultSquashedCommit(true);
```

If the first line of the commit differs from the default (`Squashed commit of the following:`), it can be customized:

```php
return (new Config())
    ->setSquashedCommitMessage('Squashed commit:')
    ->setProcessDefaultSquashedCommit(true);
```

### Defining Squashed Commits via a Group

A squashed commit can also be associated with a specific group. In this case, commits belonging to that group will be
recognized as squashed. This is configured as follows:

```php
return (new Config())
    ->setAggregateSection('aggregate');
```

### Combined Definition of a Squashed Commit

You can combine both options.

### General Rules for Full Commit Descriptions

In both cases, the detailed description of the commits must include a list of changes in the Conventional Commits
format. Specifically:

- Only lines matching this format are considered.
- Leading spaces, tab characters, `-`, and `*` are allowed at the beginning of such lines.
- Breaking change indicators can be applied.

For example:

```text
commit 2bf0dc5a010f17abc35d15c0f816c636d81cbfd2
Author: Author: Name Lastname <devemail@email.com>
Date:   Sun Mar 23 15:20:02 2025 +0300

   docs: update README with configuration examples 1
   
 -  docs: update README with configuration examples 2
 *  docs: update README with configuration examples 3
 -  docs!: update README with configuration examples 4
```

As a result, the major version will be incremented, and the following entries will be added to the Documentation section
of the CHANGELOG:

```text
### Documentation
 - update README with configuration examples 1
 - update README with configuration examples 2
 - update README with configuration examples 3
 - update README with configuration examples 4
```

## Configuring the Commit Description Parser

The parser processes the string in accordance with the [Conventional Commits](https://www.conventionalcommits.org/)
standard, which defines a commit message format for ease of automated processing. You can change this behavior by
setting a custom parser that implements the `Vasoft\VersionIncrement\Contract\CommitParserInterface` interface.

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
        // Your parsing logic
    }
}

$config = new Config(); 
return $config
    ->setCommitParser(new MyParser());
```

## Disabling Version Updates in composer.json

In some projects, version management may be handled exclusively through Git tags without updating the `composer.json`
file. To support this, an option has been added to disable version management in `composer.json`.

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setEnabledComposerVersioning(false);
```

When this setting is disabled:

- The version will be determined solely based on Git tags.
- The `composer.json` file will not be analyzed or updated.
- All version-related operations will be performed only within the context of tags.

By default, version updates in `composer.json` are enabled.

## Sets a custom property in the configuration

You can to store custom key-value pairs in the configuration. These properties can be used to pass
additional parameters required by custom implementations (e.g., formatters, VCS executors, parsers, etc.).

 ```php
     return (new \Vasoft\VersionIncrement\Config())
         ->set(\MyApp\Custom\CustomFormatter::PARAM_KEY, 'customValue');
```

And you can use it:

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

## Suppress Duplicate Lines in CHANGELOG

You can configure whether duplicate entries (lines with identical content) should be hidden in the generated CHANGELOG.
When enabled, only the first occurrence of a duplicate entry will be displayed within each section. This improves the
readability of the CHANGELOG by reducing redundancy and making it more concise.

> **Note:** Duplicate entries are hidden only within the same section. Entries in different sections are not affected.

To enable hiding of duplicate entries, use the following configuration:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setHideDoubles(true);
```