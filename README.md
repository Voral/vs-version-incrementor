# Semantic Version Update

[RU](https://github.com/Voral/vs-version-incrementor/blob/master/README.ru.md)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Voral/vs-version-incrementor/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Voral/vs-version-incrementor/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Voral/vs-version-incrementor/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Voral/vs-version-incrementor/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/Voral/vs-version-incrementor/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence) \
![PHP Tests](https://github.com/Voral/vs-version-incrementor/actions/workflows/php.yml/badge.svg)

This tool automates the process of version management in Composer-based projects by analyzing Git commits and generating
a `CHANGELOG`. It adheres to [semantic versioning](https://semver.org/) and supports
the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) standard.

The version follows the semantic rule `<Major>.<Minor>.<Patch>`:

- *Major*: Breaking changes that affect backward compatibility.
- *Minor*: New features added without breaking existing functionality.
- *Patch*: Bug fixes or minor improvements.

## Key Features

- **Version Management**: Automatically determines the next version based on commit analysis and
  updates `composer.json`.
- **Git Integration**: Creates Git tags for releases and handles commits according to the project's versioning strategy.
- **Customizable Commit Types**: Define custom commit types and their impact on version
  increments (`major`, `minor`, `patch`).
- **Advanced CHANGELOG Generation**:
    - Supports custom formatting for the `CHANGELOG.md` file.
    - Option to hide duplicate entries within the same section for cleaner output.
    - Configurable scope preservation for specific commit types.
- **Support for Squashed Commits**: Handles squashed commits (e.g., from `git merge --squash`) by parsing detailed
  descriptions.
- **Configurable Rules**: Implement custom rules for categorizing commits into sections.
- **Flexible Configuration**:
    - Customize the main branch name (e.g., `main` instead of `master`).
    - Configure release-related settings, such as release scope and section.
    - Ignore untracked files during version updates.
- **Semantic Versioning Compliance**: Ensures strict adherence to semantic versioning principles.
- **Optional Composer Versioning**: Disable version updates in `composer.json` if versioning is managed solely through
  Git tags.
- **Extensibility**:
    - Use custom parsers, formatters, and VCS executors for advanced workflows.
    - Extend functionality with custom properties via the `Config` class.

### Why Use This Tool?

- Simplifies version management by automating repetitive tasks.
- Improves consistency in versioning and changelog generation.
- Provides flexibility for custom workflows and project-specific requirements.
- Reduces human error by relying on automated analysis of commit messages.

## Installation

```bash
composer require --dev voral/version-increment
```

### Usage

```bash
# Automatic detection of release type
./vendor/bin/vs-version-increment

# Incrementing the major version
./vendor/bin/vs-version-increment major

# Incrementing the minor version
./vendor/bin/vs-version-increment minor

# Incrementing the patch version
./vendor/bin/vs-version-increment patch
```

Utility help command

```bash
./vendor/bin/vs-version-increment --help
```

Retrieving the list of registered commit types and registered scopes

```bash
./vendor/bin/vs-version-increment --list
```

The `--debug` flag allows you to preview the changes that will be made to the CHANGELOG and version without actually
applying them

```bash
# Automatic detection of release type
./vendor/bin/vs-version-increment --debug

# Incrementing the major version
./vendor/bin/vs-version-increment --debug major

# Incrementing the minor version
./vendor/bin/vs-version-increment --debug minor

# Incrementing the patch version
./vendor/bin/vs-version-increment --debug patch
```

To simplify usage, you can add scripts to `composer.json`:

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

Example of the output file:

```markdown
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

## Configuration

You can configure the script by placing a `.vs-version-increment.php` file in the project directory and making the
following adjustments:

- [Setting a Custom List of Change Types](docs/config.md#setting-a-custom-list-of-change-types)
- [Configuring Change Types](docs/config.md#configuring-change-types)
- [Configuring the Release Commit Type](docs/config.md#configuring-the-release-commit-type)
- [Configuring the Main Repository Branch](docs/config.md#configuring-the-main-repository-branch)
- [Configuring Types for Major Version Updates](docs/config.md#configuring-types-for-major-version-updates)
- [Configuring Types for Minor Version Updates](docs/config.md#configuring-types-for-minor-version-updates)
- [Release Scope Configuration](docs/config.md#release-scope-configuration)
- [Custom Type Distribution Rules Setup](docs/config.md#custom-type-distribution-rules-setup)
- [Ignoring Untracked Files](docs/config.md#ignoring-untracked-files)
- [Configuring CHANGELOG Formatting](docs/config.md#configuring-changelog-formatting)
    - [Using a Scope-Preserving Formatter](docs/config.md#using-a-scope-preserving-formatter)
    - [Configuring Human-Readable Titles for Scopes](docs/config.md#configuring-human-readable-titles-for-scopes)
    - [Creating a Custom Formatter](docs/config.md#creating-a-custom-formatter)
- [Configuring Squashed Commits](docs/config.md#configuring-squashed-commits)
    - [Default Squashed Commit](docs/config.md#default-squashed-commit)
    - [Defining Squashed Commits via a Group](docs/config.md#defining-squashed-commits-via-a-group)
    - [Combined Definition of a Squashed Commit](docs/config.md#combined-definition-of-a-squashed-commit)
    - [General Rules for Full Commit Descriptions](docs/config.md#general-rules-for-full-commit-descriptions)
- [Configuring the Commit Description Parser](docs/config.md#configuring-the-commit-description-parser)
- [Disabling Version Updates in composer.json](docs/config.md#disabling-version-updates-in-composerjson)
- [Sets a custom property in the configuration](docs/config.md#sets-a-custom-property-in-the-configuration)
- [Suppress Duplicate Lines in CHANGELOG](docs/config.md#suppress-duplicate-lines-in-changelog)

## Commit Descriptions

For the tool to function correctly, commit descriptions must follow this format:

```
<type>[(scope)][!]: <description>

[body]
```

- *type*: The commit type. It is recommended to use a predefined list for the project. Changes are grouped in the
  changelog by type. Unregistered types fall under the default category. The type configured as related to new
  functionality (default: `feat`) affects the minor version during automatic detection.
- *scope* (optional): The project area to which the commit applies.
- *!*: Indicates that the commit breaks backward compatibility. During automatic detection, this triggers a major
  version update.
- *description*: A short description.
- *body*: Detailed description (not used by the tool).

Examples:

```
feat(router): New endpoint
```

```
doc: Described all features
```

```
feat!: Removed old API endpoints
```

## Default Commit Types

| Type       | Purpose                                                      |
|------------|--------------------------------------------------------------|
| `feat`     | Adding new functionality                                     |
| `fix`      | Fixing bugs                                                  |
| `chore`    | Routine tasks (e.g., dependency updates)                     |
| `docs`     | Documentation changes                                        |
| `style`    | Code formatting (indentation, spaces, etc.)                  |
| `refactor` | Refactoring code without adding new features or fixing bugs  |
| `test`     | Adding or modifying tests                                    |
| `perf`     | Performance optimization                                     |
| `ci`       | Continuous integration (CI) configuration                    |
| `build`    | Changes related to project build                             |
| `other`    | All other changes that do not fall under standard categories |

## CI/CD Integration

The script can be integrated into CI/CD pipelines. In case of errors, it returns different exit codes:

| Code  | Description                           |
|-------|---------------------------------------|
| 10    | Composer configuration error          |
| 20    | Git branch is not the main branch     |
| 30    | Uncommitted changes in the repository |
| 40    | No changes in the repository          |
| 50    | Invalid configuration file            |
| 60    | Error executing a Git command         |
| 70    | Invalid version change type           |
| 80    | Changelog File Error                  |
| 90    | Unknown config property               |
| 100   | Configuration is not set              |
| 500   | Other errors                          |

You can use it in the command line, for example:

```bash
./vendor/bin/vs-version-increment && echo 'Ok' || echo 'Error'
```

Example for GitHub Actions:

```yaml
jobs:
  version-update:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v3

      - name: Run version increment script
        run: ./vendor/bin/vs-version-increment
```

## Configuration Examples

To help you get started with the library more quickly, I provide ready-to-use configuration examples for various use
cases.

### 1. Configuration for Keep a Changelog

This configuration example is designed for projects that follow the [Keep a Changelog](https://keepachangelog.com/)
standard. It organizes changes into categories (`Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, `Security`),
making the changelog easy to read.

-

*File:* [`examples/keepachangelog.php`](https://github.com/Voral/vs-version-incrementor/blob/master/examples/keepachangelog.php)

## Useful Links

- [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)
- [Semantic Versioning](https://semver.org/)
- [Project Repository](https://github.com/Voral/vs-version-incrementor)
