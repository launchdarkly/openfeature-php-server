# Contributing to the LaunchDarkly OpenFeature provider for the Server-Side SDK for PHP

LaunchDarkly has published an [SDK contributor's guide](https://docs.launchdarkly.com/sdk/concepts/contributors-guide) that provides a detailed explanation of how our SDKs work. See below for additional information on how to contribute to this SDK.

## Submitting bug reports and feature requests

The LaunchDarkly SDK team monitors the [issue tracker](https://github.com/launchdarkly/openfeature-php-server/issues) in the provider repository. Bug reports and feature requests specific to this provider should be filed in this issue tracker. The SDK team will respond to all newly filed issues within two business days.

## Submitting pull requests

We encourage pull requests and other contributions from the community. Before submitting pull requests, ensure that all temporary or unintended code is removed. Don't worry about adding reviewers to the pull request; the LaunchDarkly SDK team will add themselves. The SDK team will acknowledge all pull requests within two business days.

## Build instructions

This project makes use of `make` as a way of automating common steps. Run `make help` to see a list of valid targets. For example,

```shell
$ make help
Usage: make [target] ...

Targets:
help    Show this help message
check   Run all quality control checks
lint    Run formatters, linters, and other quality control tools
test    Run all automated tests
```
