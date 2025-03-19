# Semantic Version Update

[RU](https://github.com/Voral/BxBackupTools/blob/master/README.ru.md)

This tool automates the process of updating versions in Composer projects based on Git commit analysis and CHANGELOG generation. It helps adhere to semantic versioning and the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) standard.

The version is built according to the semantic rule `<Major>.<Minor>.<Patch>`:

- *Major*: Changes for major updates, breaking backward compatibility, etc.
- *Minor*: Adding new features without changing existing ones and without breaking backward compatibility.
- *Patch*: Minor changes or bug fixes.

## Key Features

- Analyzing the current version from `composer.json`.
- Determining the type of change (`major`, `minor`, `patch`) based on commits.
- Updating the `composer.json` file with the new version.
- Creating Git tags for releases and commits.
- Supporting custom configurations for commit types.

## Installation

```bash
composer require dev voral/version-increment
```

### Usage

For automatic selection of the release type:

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

To simplify usage, you can add scripts to `composer.json`:

```json
{
  "scripts": {
    "vinc:major": "php ./vendor/bin/vs-version-increment major",
    "vinc:minor": "php ./vendor/bin/vs-version-increment minor",
    "vinc:patch": "php ./vendor/bin/vs-version-increment patch",
    "vinc:auto": "php ./vendor/bin/vs-version-increment"
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

You can configure the script by placing a `.vs-version-increment.php` file in the project directory and making the following adjustments:

### Setting a Custom List of Change Types

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
- If the type corresponding to new functionality (default `feat`) is missing, the minor version will not change during automatic type detection.

### Configuring Change Types

You can modify existing types or add new ones:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('feat', 'Features') // Modify only the title
    ->setSection('fix', 'Fixes', 1)  // Modify the title and sorting
    ->setSection('ci', 'CI', hidden: true) // Hide from CHANGELOG
    ->setSection('custom3', 'My custom type', 400, false); // Add a new type that is hidden from CHANGELOG
```

### Configuring the Release Commit Type

By default, the release commit type is `chore`. You can customize this behavior:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('release', 'Releases', hidden: false)
    ->setReleaseSection('release');
```

### Configuring the Main Repository Branch

By default, the main branch is considered to be `master`. However, you can change this behavior:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setMasterBranch('main');
```

### Configuring Types for Major Version Updates

By default, the major version is incremented only when the `!` flag is present. However, you can configure specific commit types to trigger a major version update:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setSection('global', 'Global Changes')
    ->setMajorTypes(['global']);
```

### Configuring Types for Minor Version Updates

By default, the minor version is incremented only when the `feat` type is present among commits. You can configure other types to trigger a minor version update:

```php
return (new \Vasoft\VersionIncrement\Config())
    ->setMajorTypes(['feat', 'fix']);
```

## Commit Descriptions

For the tool to function correctly, commit descriptions must follow this format:

```
<type>[(scope)][!]: <description>

[body]
```

- *type*: The commit type. It is recommended to use a predefined list for the project. Changes are grouped in the changelog by type. Unregistered types fall under the default category. The type configured as related to new functionality (default: `feat`) affects the minor version during automatic detection.
- *scope* (optional): The project area to which the commit applies.
- *!*: Indicates that the commit breaks backward compatibility. During automatic detection, this triggers a major version update.
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

| Type       | Purpose                                                           |
|------------|-------------------------------------------------------------------|
| `feat`     | Adding new functionality                                          |
| `fix`      | Fixing bugs                                                       |
| `chore`    | Routine tasks (e.g., dependency updates)                          |
| `docs`     | Documentation changes                                             |
| `style`    | Code formatting (indentation, spaces, etc.)                       |
| `refactor` | Refactoring code without adding new features or fixing bugs       |
| `test`     | Adding or modifying tests                                         |
| `perf`     | Performance optimization                                          |
| `ci`       | Continuous integration (CI) configuration                         |
| `build`    | Changes related to project build                                  |
| `other`    | All other changes that do not fall under standard categories      |

## CI/CD Integration

The script can be integrated into CI/CD pipelines. In case of errors, it returns different exit codes:

| Code | Description                                |
|------|--------------------------------------------|
| 10   | Composer configuration error               |
| 20   | Git branch is not the main branch          |
| 30   | Uncommitted changes in the repository      |
| 40   | No changes in the repository               |
| 50   | Invalid configuration file                 |
| 60   | Error executing a Git command              |
| 70   | Invalid version change type                |
| 500  | Other errors                               |

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

## Useful Links

- [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)
- [Semantic Versioning](https://semver.org/)
- [Project Repository](https://github.com/Voral/vs-version-incrementor)
