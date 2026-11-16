# Change log

All notable changes to the LaunchDarkly OpenFeature provider for the Server-Side SDK for PHP will be documented in this file. This project adheres to [Semantic Versioning](http://semver.org).


## [2.1.1](https://github.com/launchdarkly/openfeature-php-server/compare/2.1.0...2.1.1) (2026-11-12)


### Bug Fixes

* Report a type mismatch for non-numeric flag values ([#29](https://github.com/launchdarkly/openfeature-php-server/issues/29)) ([a7d94cb](https://github.com/launchdarkly/openfeature-php-server/commit/a7d94cbe1425945a86ca4f01cd6006ca36af2397))

## [2.1.0](https://github.com/launchdarkly/openfeature-php-server/compare/2.0.0...2.1.0) (2026-11-06)


### Features

* Populate OpenFeature flag metadata from the LaunchDarkly evaluation reason ([#33](https://github.com/launchdarkly/openfeature-php-server/issues/33)) ([4f6c7c8](https://github.com/launchdarkly/openfeature-php-server/commit/4f6c7c897f8f21a5a24d783c90c2734a52143b25))


### Bug Fixes

* Convert date attributes to ISO-8601 strings in UTC ([#34](https://github.com/launchdarkly/openfeature-php-server/issues/34)) ([05d840f](https://github.com/launchdarkly/openfeature-php-server/commit/05d840f8bd4d56b712ac36bf1aeb5a235a9e3615))

## [2.0.0](https://github.com/launchdarkly/openfeature-php-server/compare/1.0.0...2.0.0) (2026-10-21)


### ⚠ BREAKING CHANGES

* Do not add the targeting key as a custom attribute ([#28](https://github.com/launchdarkly/openfeature-php-server/issues/28))

### Features

* Require PHP 8.2 or later ([#32](https://github.com/launchdarkly/openfeature-php-server/issues/32)) ([50c99d1](https://github.com/launchdarkly/openfeature-php-server/commit/50c99d1151be6af79648a74205d18d14e8ada4e2))


### Bug Fixes

* Do not add the targeting key as a custom attribute ([#28](https://github.com/launchdarkly/openfeature-php-server/issues/28)) ([2fb07d1](https://github.com/launchdarkly/openfeature-php-server/commit/2fb07d1ba4ce8140615aa92f4888e1968a0d7549))
* Map the WRONG_TYPE error kind to TYPE_MISMATCH ([#27](https://github.com/launchdarkly/openfeature-php-server/issues/27)) ([481eb3c](https://github.com/launchdarkly/openfeature-php-server/commit/481eb3cd17de6a4abfabb451b992796e54010266))

## [1.0.0](https://github.com/launchdarkly/openfeature-php-server/compare/0.1.0...1.0.0) (2024-06-13)


### Features

* **deps:** Require PHP 8.1+ ([#16](https://github.com/launchdarkly/openfeature-php-server/issues/16)) ([4556af2](https://github.com/launchdarkly/openfeature-php-server/commit/4556af25028828845bb1a5c7e4bb70d61917af00))
* Provider constructor takes LDClient options instead of created client ([#14](https://github.com/launchdarkly/openfeature-php-server/issues/14)) ([924a9c1](https://github.com/launchdarkly/openfeature-php-server/commit/924a9c1b43c6b477201e077452b00e438e439947))


### Miscellaneous Chores

* Fix release please config file syntax ([#17](https://github.com/launchdarkly/openfeature-php-server/issues/17)) ([2fd7c06](https://github.com/launchdarkly/openfeature-php-server/commit/2fd7c0655e191fe85686ccab68b844672047bc52))
* Set wrapper name and version on wrapped LDClient ([#15](https://github.com/launchdarkly/openfeature-php-server/issues/15)) ([597dbbc](https://github.com/launchdarkly/openfeature-php-server/commit/597dbbcb44bb73ea0f38f27c0c986879f3868457))
* Update CODEOWNERS ([#9](https://github.com/launchdarkly/openfeature-php-server/issues/9)) ([ad3a465](https://github.com/launchdarkly/openfeature-php-server/commit/ad3a465be23cb68c0541ea7b8156bdc570013540))

## [0.1.0] - 2023-04-21
Initial beta release of the LaunchDarkly OpenFeature provider for the Server-Side SDK for PHP.
