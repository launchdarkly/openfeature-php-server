# LaunchDarkly OpenFeature provider for the Server-Side SDK for PHP

[![Run CI](https://github.com/launchdarkly/openfeature-php-server/actions/workflows/ci.yml/badge.svg)](https://github.com/launchdarkly/openfeature-php-server/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/launchdarkly/openfeature-server.svg?style=flat-square)](https://packagist.org/packages/launchdarkly/openfeature-server)
[![Documentation](https://img.shields.io/static/v1?label=GitHub+Pages&message=API+reference&color=00add8)](https://launchdarkly.github.io/openfeature-php-server)

This provider allows for using LaunchDarkly with the OpenFeature SDK for PHP.

This provider is designed primarily for use in multi-user systems such as web servers and applications. It follows the server-side LaunchDarkly model for multi-user contexts. It is not intended for use in desktop and embedded systems applications.

# LaunchDarkly overview

[LaunchDarkly](https://www.launchdarkly.com) is a feature management platform that serves over 100 billion feature flags daily to help teams build better software, faster. [Get started](https://docs.launchdarkly.com/home/getting-started) using LaunchDarkly today!

[![Twitter Follow](https://img.shields.io/twitter/follow/launchdarkly.svg?style=social&label=Follow&maxAge=2592000)](https://twitter.com/intent/follow?screen_name=launchdarkly)

## Supported PHP versions

This version of the LaunchDarkly provider works with PHP 8.1 and above.

## Feature matrix

This matrix mirrors the [feature matrix of the OpenFeature SDK for PHP](https://github.com/open-feature/php-sdk#-features) and describes what this provider supports. Rows which are not supported state whether the limitation comes from the OpenFeature PHP SDK or from the provider.

| Status | Feature                         | Notes                                                                                                                                                                                                            |
|--------|---------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| ✅      | Providers                       | Evaluates boolean, string, integer, float, and object flags through the LaunchDarkly PHP SDK.                                                                                                                     |
| ✅      | Targeting                       | The `EvaluationContext` is converted to a LaunchDarkly single or multi-context. See [OpenFeature Specific Considerations](#openfeature-specific-considerations).                                                  |
| ✅      | Hooks                           | Hooks are registered on the OpenFeature API and client; the provider requires no additional support and its results are visible to hooks. The provider contributes no hooks of its own.                            |
| ✅      | Logging                         | The provider accepts a PSR-3 logger through the `logger` option, and `setLogger` replaces the logger used for context conversion. The logger of the underlying LaunchDarkly client is set through the `logger` option. |
| ✅      | MultiProvider                   | The provider can be one of the providers given to the OpenFeature SDK's `MultiProvider`; no provider support is required.                                                                                          |
| ✅      | Extending                       | The provider is constructed with an SDK key and the options of the underlying LaunchDarkly client, so LaunchDarkly features outside OpenFeature remain available through a separately constructed `LDClient`.     |
| ❌      | Named clients                   | Not supported — not available in the OpenFeature PHP SDK, which holds a single provider for the whole API ([php-sdk#93](https://github.com/open-feature/php-sdk/issues/93)).                                       |
| ❌      | Eventing                        | Not supported — not available in the OpenFeature PHP SDK ([php-sdk#93](https://github.com/open-feature/php-sdk/issues/93)). The PHP provider is also request-scoped and holds no streaming data source to report on. |
| ❌      | Initialization                  | Not supported — the OpenFeature PHP SDK provider interface has no initialization method, so there is nothing for the provider to implement or to bound with a timeout.                                             |
| ❌      | Shutdown                        | Not supported — not available in the OpenFeature PHP SDK ([php-sdk#93](https://github.com/open-feature/php-sdk/issues/93)).                                                                                       |
| ❌      | Tracking                        | Not supported — the OpenFeature PHP SDK provider interface has no `track` method. LaunchDarkly custom events can be sent through an `LDClient` directly.                                                          |
| ❌      | Transaction Context Propagation | Not supported — not available in the OpenFeature PHP SDK, which has no transaction context.                                                                                                                       |
| ❌      | Flag metadata                   | Not supported — not available in the OpenFeature PHP SDK, whose `EvaluationDetails` and `ResolutionDetails` carry no flag metadata, so LaunchDarkly evaluation reason details cannot be surfaced.                 |

<sub>Supported: ✅ | Partially supported: ⚠️ | Not supported: ❌</sub>

## Getting started

### Requisites

Example composer dependencies:

```json
{
    "require": {
        "php": ">=8.1",
        "launchdarkly/openfeature-server": "^1.0"
    }
}
```

### Usage

```php
use OpenFeature\OpenFeatureAPI;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;

$provider = new LaunchDarkly\OpenFeature\Provider("my-sdk-key");

$api = OpenFeatureAPI::getInstance();
$api->setProvider($provider);

// Refer to OpenFeature documentation for getting a client and performing evaluations.
```

Refer to the [SDK reference guide](https://docs.launchdarkly.com/sdk/server-side/php) for instructions on getting started with using the SDK.

For information on using the OpenFeature client please refer to the [OpenFeature Documentation](https://docs.openfeature.dev/docs/reference/concepts/evaluation-api/).

## OpenFeature Specific Considerations

LaunchDarkly evaluates contexts, and it can either evaluate a single-context, or a multi-context. When using OpenFeature both single and multi-contexts must be encoded into a single `EvaluationContext`. This is accomplished by looking for an attribute named `kind` in the `EvaluationContext`.

There are 4 different scenarios related to the `kind`:
1. There is no `kind` attribute. In this case the provider will treat the context as a single context containing a "user" kind.
2. There is a `kind` attribute, and the value of that attribute is "multi". This will indicate to the provider that the context is a multi-context.
3. There is a `kind` attribute, and the value of that attribute is a string other than "multi". This will indicate to the provider a single context of the kind specified.
4. There is a `kind` attribute, and the attribute is not a string. In this case the value of the attribute will be discarded, and the context will be treated as a "user". An error message will be logged.

The `kind` attribute should be a string containing only contain ASCII letters, numbers, `.`, `_` or `-`.

The OpenFeature specification allows for an optional targeting key, but LaunchDarkly requires a key for evaluation. A targeting key must be specified for each context being evaluated. It may be specified using either `targetingKey`, as it is in the OpenFeature specification, or `key`, which is the typical LaunchDarkly identifier for the targeting key. If a `targetingKey` and a `key` are specified, then the `targetingKey` will take precedence.

There are several other attributes which have special functionality within a single or multi-context.
- A key of `privateAttributes`. Must be an array of string values. [Equivalent to the 'private' builder method in the SDK.](https://launchdarkly.github.io/php-server-sdk/classes/LaunchDarkly-LDContextBuilder.html#method_private)
- A key of `anonymous`. Must be a boolean value.  [Equivalent to the 'anonymous' builder method in the SDK.](https://launchdarkly.github.io/php-server-sdk/classes/LaunchDarkly-LDContextBuilder.html#method_anonymous)
- A key of `name`. Must be a string. [Equivalent to the 'name' builder method in the SDK.](https://launchdarkly.github.io/php-server-sdk/classes/LaunchDarkly-LDContextBuilder.html#method_name)

### Examples

#### A single user context

```php
$context = new EvaluationContext("the-key");
```

#### A single context of kind "organization"

```php
$attributes = new Attributes(["kind" => "organization"]);
$context = new EvaluationContext("org-key", $attributes);
```

#### A multi-context containing a "user" and an "organization"

```php
$attributes = [
    "kind" => "multi",
    "organization" => [
        "name" => "the-org-name",
        "targetingKey", "my-org-key",
        "myCustomAttribute", "myAttributeValue"
    ],
    "user" => [
        "key" => "my-user-key",
        "anonymous", true
    ]
];
$context = new EvaluationContext(null, new Attributes($attributes));
```

#### Setting private attributes in a single context

```php
$attributes = [
    "kind" => "organization",
    "myCustomAttribute" => "myAttributeValue",
    "privateAttributes" => ["myCustomAttribute"]
];

$context = new EvaluationContext("org-key", new Attributes($attributes));
```

#### Setting private attributes in a multi-context

```php
$attributes = [
    "kind" => "organization",
    "organization" => [
        "name" => "the-org-name",
        "targetingKey" => "my-org-key",
        // This will ONLY apply to the "organization" attributes.
        "privateAttributes" => ["myCustomAttribute"],
        // This attribute will be private.
        "myCustomAttribute" => "myAttributeValue",
    ],
    "user" => [
        "key" => "my-user-key",
        "anonymous" = > true,
        // This attribute will not be private.
        "myCustomAttribute" => "myAttributeValue",
    ]
];

$context = new EvaluationContext(null, new Attributes($attributes));
```

## Learn more

Check out our [documentation](http://docs.launchdarkly.com) for in-depth instructions on configuring and using LaunchDarkly. You can also head straight to the [complete reference guide for this SDK](https://docs.launchdarkly.com/sdk/server-side/php).

The authoritative description of all properties and methods is in the [php documentation](https://launchdarkly.github.io/php-server-sdk/).

## Contributing

We encourage pull requests and other contributions from the community. Check out our [contributing guidelines](CONTRIBUTING.md) for instructions on how to contribute to this SDK.

## About LaunchDarkly

* LaunchDarkly is a continuous delivery platform that provides feature flags as a service and allows developers to iterate quickly and safely. We allow you to easily flag your features and manage them from the LaunchDarkly dashboard.  With LaunchDarkly, you can:
    * Roll out a new feature to a subset of your users (like a group of users who opt-in to a beta tester group), gathering feedback and bug reports from real-world use cases.
    * Gradually roll out a feature to an increasing percentage of users, and track the effect that the feature has on key metrics (for instance, how likely is a user to complete a purchase if they have feature A versus feature B?).
    * Turn off a feature that you realize is causing performance problems in production, without needing to re-deploy, or even restart the application with a changed configuration file.
    * Grant access to certain features based on user attributes, like payment plan (eg: users on the ‘gold’ plan get access to more features than users in the ‘silver’ plan). Disable parts of your application to facilitate maintenance, without taking everything offline.
* LaunchDarkly provides feature flag SDKs for a wide variety of languages and technologies. Check out [our documentation](https://docs.launchdarkly.com/sdk) for a complete list.
* Explore LaunchDarkly
    * [launchdarkly.com](https://www.launchdarkly.com/ "LaunchDarkly Main Website") for more information
    * [docs.launchdarkly.com](https://docs.launchdarkly.com/  "LaunchDarkly Documentation") for our documentation and SDK reference guides
    * [apidocs.launchdarkly.com](https://apidocs.launchdarkly.com/  "LaunchDarkly API Documentation") for our API documentation
    * [blog.launchdarkly.com](https://blog.launchdarkly.com/  "LaunchDarkly Blog Documentation") for the latest product updates
