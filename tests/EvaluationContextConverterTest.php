<?php

declare(strict_types=1);

namespace LaunchDarkly\Tests;

use LaunchDarkly\OpenFeature\EvaluationContextConverter;
use OpenFeature\implementation\flags\Attributes;
use OpenFeature\implementation\flags\EvaluationContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class EvaluationContextConverterTest extends TestCase
{
    private EvaluationContextConverter $contextConverter;

    public function setUp(): void
    {
        $this->contextConverter = new EvaluationContextConverter(new NullLogger());
    }

    public function testCreateContextWithTargetingKeyOnly(): void
    {
        $context = new EvaluationContext("user-key", null);

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertTrue($ldContext->isValid());
        $this->assertEquals("user", $ldContext->getKind());
        $this->assertEquals("user-key", $ldContext->getKey());
    }

    public function testCreateContextWithKeyOnly(): void
    {
        $attributes = new Attributes(["key" => "user-key"]);
        $context = new EvaluationContext(null, $attributes);

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertTrue($ldContext->isValid());
        $this->assertEquals("user", $ldContext->getKind());
        $this->assertEquals("user-key", $ldContext->getKey());
    }

    public function testTargetingKeyTakesPrecedenceOverAttributeKey(): void
    {
        $attributes = new Attributes(["kind" => "org", "key" => "do-not-use"]);
        $context = new EvaluationContext("should-use", $attributes);

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertTrue($ldContext->isValid());
        $this->assertEquals("org", $ldContext->getKind());
        $this->assertEquals("should-use", $ldContext->getKey());
    }

    public function testCreateContextWithKeyAndKind(): void
    {
        $attributes = new Attributes(["kind" => "org"]);
        $context = new EvaluationContext("org-key", $attributes);

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertTrue($ldContext->isValid());
        $this->assertEquals("org", $ldContext->getKind());
        $this->assertEquals("org-key", $ldContext->getKey());
    }

    public function testInvalidKindResetsToUser(): void
    {
        $attributes = new Attributes(["kind" => false]);
        $context = new EvaluationContext("org-key", $attributes);

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertTrue($ldContext->isValid());
        $this->assertEquals("user", $ldContext->getKind());
        $this->assertEquals("org-key", $ldContext->getKey());
    }

    public function testAttributesAreReferencedCorrectly(): void
    {
        $attributes = new Attributes(["kind" => "user", "anonymous" => true, "name" => "Sandy", "lastName" => "Beaches"]);
        $context = new EvaluationContext("user-key", $attributes);

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertTrue($ldContext->isValid());
        $this->assertEquals("user", $ldContext->getKind());
        $this->assertEquals("user-key", $ldContext->getKey());
        $this->assertTrue($ldContext->isAnonymous());
        $this->assertEquals("Sandy", $ldContext->getName());

        $contextJson = $ldContext->jsonSerialize();
        $this->assertEquals("Beaches", $contextJson["lastName"]);
    }

    public function testInvalidAttributeTypesAreIgnored(): void
    {
        $attributes = new Attributes(["kind" => "user", "anonymous" => "true", "name" => 30, "privateAttributes" => "testing"]);
        $context = new EvaluationContext("user-key", $attributes);

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertTrue($ldContext->isValid());
        $this->assertEquals("user", $ldContext->getKind());
        $this->assertEquals("user-key", $ldContext->getKey());
        $this->assertFalse($ldContext->isAnonymous());
        $this->assertEquals("", $ldContext->getName());
        $this->assertNull($ldContext->getPrivateAttributes());
    }

    public function testPrivateAttributesAreProcessedCorrectly(): void
    {
        $attributes = new Attributes(["kind" => "user", "address" => ["street" => "123 Easy St.", "city" => "Anytown"], "name" => "Sandy", "privateAttributes" => ["name", "/address/city"]]);
        $context = new EvaluationContext("user-key", $attributes);

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertTrue($ldContext->isValid());
        $this->assertEquals("user", $ldContext->getKind());
        $this->assertEquals("user-key", $ldContext->getKey());

        /** @var array<\LaunchDarkly\Types\AttributeReference> */
        $privateAttributes = $ldContext->getPrivateAttributes();

        $this->assertCount(2, $privateAttributes);
        $this->assertEquals("name", $privateAttributes[0]->getPath());
        $this->assertEquals("/address/city", $privateAttributes[1]->getPath());
    }

    public function testCanCreateMultiKindContext(): void
    {
        $attributes = [
            'kind' => 'multi',
            'user' => ['key' => 'user-key', 'name' => 'User name'],
            'org' => ['key' => 'org-key', 'name' => 'Org name']
        ];
        $context = new EvaluationContext(null, new Attributes($attributes));

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertTrue($ldContext->isValid());
        $this->assertEquals(2, $ldContext->getIndividualContextCount());

        /** @var \LaunchDarkly\LDContext */
        $userContext = $ldContext->getIndividualContext("user");
        $this->assertEquals("user", $userContext->getKind());
        $this->assertEquals("user-key", $userContext->getKey());
        $this->assertEquals("User name", $userContext->getName());

        /** @var \LaunchDarkly\LDContext */
        $orgContext = $ldContext->getIndividualContext("org");
        $this->assertEquals("org", $orgContext->getKind());
        $this->assertEquals("org-key", $orgContext->getKey());
        $this->assertEquals("Org name", $orgContext->getName());
    }

    public function testMultiContextDiscardsInvalidSingleKind(): void
    {
        $attributes = [
            'kind' => 'multi',
            'user' => false,
            'org' => ['key' => 'org-key', 'name' => 'Org name']
        ];
        $context = new EvaluationContext(null, new Attributes($attributes));

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertTrue($ldContext->isValid());
        $this->assertEquals(1, $ldContext->getIndividualContextCount());

        /** @var \LaunchDarkly\LDContext */
        $orgContext = $ldContext->getIndividualContext("org");
        $this->assertEquals("org", $orgContext->getKind());
        $this->assertEquals("org-key", $orgContext->getKey());
        $this->assertEquals("Org name", $orgContext->getName());
    }

    public function testHandlesInvalidNestedContexts(): void
    {
        $attributes = [
            'kind' => 'multi',
            'user' => "invalid format",
            'org' => false,
        ];
        $context = new EvaluationContext(null, new Attributes($attributes));

        $ldContext = $this->contextConverter->toLdContext($context);

        $this->assertFalse($ldContext->isValid());
        $this->assertEquals(0, $ldContext->getIndividualContextCount());
    }
}
