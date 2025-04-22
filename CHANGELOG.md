# 2.1.0 (2025-04-22)

### New features
- add --no-commit option for version updates
- add UserException for custom error handling in extensions
- integrate event bus and improve error handling

### Documentation
- add EventBus documentation
- Table of error codes is changed

# 2.0.0 (2025-04-15)

### BREAKING CHANGES
- simplify SemanticVersionUpdater constructor by removing redundant parameter
- improve SectionRuleInterface for better flexibility and maintainability
- remove deprecated SectionRules\SectionRuleInterface
- update VcsExecutorInterface to use ConfigurableInterface
- update ChangelogFormatterInterface to use ConfigurableInterface
- remove deprecated GetExecutorInterface
- update CommitParserInterface to use ConfigurableInterface

### New features
- add support for human-readable scope titles in CHANGELOG
- add option to hide duplicate entries within the same section in CHANGELOG
- add methods for custom property management in Config
- add option to disable version updates in composer.json
- add tag formatting functionality with TagFormatterInterface and DefaultFormatter
- add write access checks for CHANGELOG.md and composer.json
- add BreakingRule for handling backward compatibility-breaking commits
- introduce ConfigurableInterface for unified configuration injection
- add support for custom commit parsers

### Fixes
- handle cases where Config not set in parser commit
- handle null config in ScopePreservingFormatter
- handle cases where no Git tags exist

### Documentation
- update README
- add PHPDoc for all methods in the Config class
- add PHPDoc for all contract interfaces
- Edit Key features list

### Refactoring
- simplify Config class property initialization with null coalescing assignment  t
- extract section management into a dedicated `Sections` class
- extract CommitCollection creation into a dedicated factory
- extract application logic into Application class

### Tests
- Code style test class
- Test for case without composer versioning and default version
- Small reorganisation test fixtures
- Added fixtures
- reorganize test files to comply with PSR-4
- Test for Config::setCommitParser

# 1.3.0 (2025-03-28)

### New features
- add ScopePreservingFormatter to preserve specific scopes in changelog
- added support for custom changelog formatters
- add --debug flag for previewing changes

### Refactoring
- deprecate SectionRuleInterface in favor of new interface in Contract namespace

# 1.2.0 (2025-03-24)

### New features
- Squashed and aggregate commit
- add command to list registered commit types
- add custom commit type distribution rules

### Fixes
- Hide hidden sections
- preserve existing section settings when calling setSection with partial parameters

### Documentation
- move configuration settings to a separate file
- add example configuration for Keep a Changelog

### Tests
- Test for type descriptions list

# 1.1.0 (2025-03-20)

### New features
- Configure ignoring untracked files
- Configure release commit scope

### Documentation
- Fixed a typo in the installation command

### Other changes
- Configure gitattributes
- Configure gitattributes

### Build
- Configure version increment

# 1.0.1 (2025-03-19)

### Fixes
- Change type in composer.json
- Change type in composer.json

