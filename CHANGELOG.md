# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [2.0.0] - 2026-04-11

### Added
- `Ds\Seq` as the unified sequence type, replacing `Ds\Vector` and `Ds\Deque`.
- `Ds\Heap` with configurable comparator: `__construct(iterable $values = [], ?callable $comparator = null)`, max-heap by default.
- `Ds\Key` interface for custom key equality (`hash(): mixed`, `equals(mixed $other): bool`), replacing `Ds\Hashable`.
- `Ds\Pair` is now a `readonly` class.
- Functional constructors: `\Ds\seq()`, `\Ds\map()`, `\Ds\set()`, `\Ds\heap()`.

### Changed
- Requires PHP >= 8.2.
- Provides `ext-ds 2.0.0`.
- `Map::values()` now returns `Seq` (was `Vector`).
- `Map::pairs()` now returns `Seq` (was `Vector`).
- `Seq::find()` now returns `int|false` (was `?int`).

### Removed
- `Ds\Vector`, `Ds\Deque`, `Ds\Stack`, `Ds\Queue`, `Ds\PriorityQueue` classes.
- `Ds\Hashable`, `Ds\Collection`, `Ds\Sequence` interfaces.
- `Pair::copy()` method.
- `Traits/GenericSequence.php` (replaced by `Seq` directly).

## [1.4.1] - 2022-03-09

## [1.4.0] - 2021-11-17

## [1.3.0] - 2020-10-13
### Changed
- Implement ArrayAccess consistently
### Fixed
- Return types were incorrectly nullable in some cases
- Deque capacity was inconsistent with the extension

## [1.2.0] - 2017-08-03
### Changed
- Minor capacity updates

## [1.1.1] - 2016-08-09
### Fixed
- `Stack` and `Queue` array access should throw `OutOfBoundsException`, not `Error`.

### Improved
- Added a lot of docblock comments that were missing.

## [1.1.0] - 2016-08-04
### Added
- `Pair::copy`

## [1.0.3] - 2016-08-01
### Added
- `Set::merge`

## [1.0.2] - 2016-07-31
### Added
- `Map::putAll`
