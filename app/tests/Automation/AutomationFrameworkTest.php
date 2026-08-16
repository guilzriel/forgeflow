<?php

declare(strict_types=1);

namespace ForgeFlow\Tests\Automation;

use ForgeFlow\Automation\ComparisonResolver;
use ForgeFlow\Automation\ConfigComparison;
use ForgeFlow\Automation\ExecutionManifest;
use ForgeFlow\Automation\HealthCheckResolver;
use ForgeFlow\Automation\HealthResultSummary;
use ForgeFlow\Automation\OperationCatalog;
use ForgeFlow\Automation\ResolutionAuthorizer;
use ForgeFlow\Automation\TargetCatalog;
use PHPUnit\Framework\TestCase;

final class AutomationFrameworkTest extends TestCase
{
    private string $root;
    private OperationCatalog $operations;
    private TargetCatalog $targets;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 3);
        $this->operations = new OperationCatalog($this->root . '/automation/catalog/operations.json');
        $this->targets = new TargetCatalog($this->root . '/automation/catalog/targets.json');
    }

    public function testCatalogueContainsHealthAndValidationOperations(): void
    {
        $operations = $this->operations->all();
        self::assertArrayHasKey('health.full_environment', $operations);
        self::assertArrayHasKey('validate.apache_config_compare', $operations);
        self::assertArrayHasKey('validate.php_config_compare', $operations);
        self::assertArrayHasKey('validate.mysql_config_compare', $operations);
    }

    public function testWorkflowChoicesContainEveryCatalogueLabel(): void
    {
        $healthWorkflow = file_get_contents($this->root . '/.github/workflows/run-health-checks.yml');
        $validateWorkflow = file_get_contents($this->root . '/.github/workflows/validate-changes.yml');

        self::assertIsString($healthWorkflow);
        self::assertIsString($validateWorkflow);

        foreach ($this->operations->labelsForCategory('run_health_checks') as $label) {
            self::assertStringContainsString($label, $healthWorkflow);
        }
        foreach ($this->operations->labelsForCategory('validate_changes') as $label) {
            self::assertStringContainsString($label, $validateWorkflow);
        }
        foreach ($this->targets->healthLabels() as $label) {
            self::assertStringContainsString($label, $healthWorkflow);
        }
        foreach ($this->targets->comparisonLabels() as $label) {
            self::assertStringContainsString($label, $validateWorkflow);
        }
    }

    public function testEveryCataloguePlaybookExists(): void
    {
        foreach ($this->operations->all() as $operation) {
            self::assertFileExists($this->root . '/' . $operation['playbook']);
        }
    }

    public function testHealthRoleMismatchFailsClosed(): void
    {
        $resolver = new HealthCheckResolver($this->operations, $this->targets);
        $this->expectException(\InvalidArgumentException::class);
        $resolver->resolve('health.apache', '[DEMO-DB] DB-A');
    }

    public function testSectionHeadingFailsClosed(): void
    {
        $resolver = new HealthCheckResolver($this->operations, $this->targets);
        $this->expectException(\InvalidArgumentException::class);
        $resolver->resolve('health.connectivity', '---- WEB ----');
    }

    public function testApacheComparisonRejectsDatabasePair(): void
    {
        $resolver = new ComparisonResolver($this->operations, $this->targets);
        $this->expectException(\InvalidArgumentException::class);
        $resolver->resolve('validate.apache_config_compare', '[DEMO-DB] DB-A vs DB-B');
    }

    public function testMysqlComparisonRejectsWebPair(): void
    {
        $resolver = new ComparisonResolver($this->operations, $this->targets);
        $this->expectException(\InvalidArgumentException::class);
        $resolver->resolve('validate.mysql_config_compare', '[DEMO-WEB] WEB-A vs WEB-B');
    }

    public function testResolvedHealthBlastRadiusIsExplicit(): void
    {
        $resolver = new HealthCheckResolver($this->operations, $this->targets);
        $resolution = $resolver->resolve('health.apache', '[DEMO-WEB] All web nodes');

        self::assertSame(['WEB-A', 'WEB-B'], $resolution['resolved_hosts']);
        self::assertSame(2, $resolution['resolved_host_count']);
        self::assertSame('WEB-A,WEB-B', $resolution['ansible_limit']);
    }

    public function testTamperedHealthResolutionIsRejected(): void
    {
        $resolver = new HealthCheckResolver($this->operations, $this->targets);
        $resolution = $resolver->resolve('health.apache', '[DEMO-WEB] WEB-A');
        $resolution['playbook'] = 'ansible/playbooks/unapproved.yml';

        $this->expectException(\RuntimeException::class);
        ResolutionAuthorizer::authorizeHealth($resolution, $resolver);
    }

    public function testManifestRejectsSecretBearingKeys(): void
    {
        $this->expectException(\RuntimeException::class);
        ExecutionManifest::assertSecretFree(['nested' => ['database_password' => 'redacted']]);
    }

    public function testUnknownOperationFailsClosed(): void
    {
        $resolver = new HealthCheckResolver($this->operations, $this->targets);
        $this->expectException(\InvalidArgumentException::class);
        $resolver->resolve('health.unapproved', '[DEMO-WEB] WEB-A');
    }

    public function testApacheComparisonShowsOnlyIntentionalTimeoutDifference(): void
    {
        $left = [
            'host' => 'WEB-A',
            'component' => 'apache',
            'data' => [
                'version' => ['Apache/2.4'],
                'directives_tsv' => 'Timeout 60',
            ],
        ];
        $right = [
            'host' => 'WEB-B',
            'component' => 'apache',
            'data' => [
                'version' => ['Apache/2.4'],
                'directives_tsv' => 'Timeout 90',
            ],
        ];

        $differences = ConfigComparison::differences($left, $right);
        self::assertSame(['settings.Timeout'], array_keys($differences));
    }

    public function testMysqlComparisonShowsOnlyIntentionalConnectionDifference(): void
    {
        $left = [
            'host' => 'DB-A',
            'component' => 'mysql',
            'data' => ['variables_tsv' => "max_connections\t100\nsql_mode\tSTRICT"],
        ];
        $right = [
            'host' => 'DB-B',
            'component' => 'mysql',
            'data' => ['variables_tsv' => "max_connections\t150\nsql_mode\tSTRICT"],
        ];

        $differences = ConfigComparison::differences($left, $right);
        self::assertSame(['settings.max_connections'], array_keys($differences));
    }

    public function testConfigComparisonOmitsIdenticalValues(): void
    {
        $left = [
            'host' => 'WEB-A',
            'component' => 'php',
            'data' => ['settings' => ['memory_limit' => '128M', 'display_errors' => '0']],
        ];
        $right = [
            'host' => 'WEB-B',
            'component' => 'php',
            'data' => ['settings' => ['memory_limit' => '256M', 'display_errors' => '0']],
        ];

        $differences = ConfigComparison::differences($left, $right);
        self::assertArrayHasKey('settings.memory_limit', $differences);
        self::assertArrayNotHasKey('settings.display_errors', $differences);
    }

    public function testMissingHealthEvidenceForcesFrameworkFailure(): void
    {
        $results = [
            [
                'check' => 'health.apache',
                'component' => 'apache',
                'host' => 'WEB-A',
                'status' => 'PASS',
                'message' => 'ok',
            ],
        ];
        $enforced = HealthResultSummary::enforceExpectedCount($results, 2);
        self::assertTrue(HealthResultSummary::hasFailure($enforced));
        self::assertSame('framework', $enforced[1]['component']);
    }

    public function testHealthSummaryShowsFinalResultFirst(): void
    {
        $results = [
            [
                'check' => 'health.apache',
                'component' => 'apache',
                'host' => 'WEB-A',
                'status' => 'PASS',
                'message' => 'ok',
            ],
            [
                'check' => 'health.php_fpm',
                'component' => 'php-fpm',
                'host' => 'WEB-A',
                'status' => 'FAIL',
                'message' => 'failed',
            ],
        ];

        $markdown = HealthResultSummary::renderMarkdown($results, 'Test Health');
        self::assertStringStartsWith("# Test Health\n\n**Final result: FAIL**", $markdown);
    }
}
