# Change Log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

### [Unreleased]

  * Added: `Composer\Semver\Comparator`, various methods to compare versions.
  * Added: various documents such as README.md, LICENSE, etc.
  * Added: configuration files for Git, Travis, php-cs-fixer, phpunit.
  * Break: the following namespaces were renamed:
    - Namespace: `Composer\Package\Version` -> `Composer\Semver`
    - Namespace: `Composer\Package\LinkConstraint` -> `Composer\Semver\Constraint`
    - Namespace: `Composer\Test\Package\Version` -> `Composer\Test\Semver`
    - Namespace: `Composer\Test\Package\LinkConstraint` -> `Composer\Test\Semver\Constraint`
  * Changed: code style using php-cs-fixer.
