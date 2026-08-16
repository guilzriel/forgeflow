<?php

declare(strict_types=1);

namespace ForgeFlow\Automation;

final class OperationCatalog
{
    /** @var array<string, array<string, mixed>> */
    private array $operations;

    public function __construct(string $path)
    {
        $document = JsonFile::read($path);
        $version = $document['catalog_version'] ?? null;
        $operations = $document['operations'] ?? null;

        if ($version !== 1 || !is_array($operations)) {
            throw new \RuntimeException('Unsupported or malformed operation catalogue.');
        }

        $this->operations = [];
        foreach ($operations as $id => $operation) {
            if (!is_string($id) || !is_array($operation)) {
                throw new \RuntimeException('Operation catalogue contains an invalid entry.');
            }
            /** @var array<string, mixed> $operation */
            $this->operations[$id] = $operation;
        }

        $this->validate();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->operations;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        if (!isset($this->operations[$id])) {
            throw new \InvalidArgumentException("Operation is not approved: {$id}");
        }

        return $this->operations[$id];
    }


    /**
     * @return list<string>
     */
    public function labelsForCategory(string $category): array
    {
        $labels = [];
        foreach ($this->operations as $operation) {
            if (($operation['category'] ?? null) !== $category) {
                continue;
            }
            $label = $operation['label'] ?? null;
            if (!is_string($label)) {
                throw new \RuntimeException('Operation label is malformed.');
            }
            $labels[] = $label;
        }
        return $labels;
    }

    public function idForLabel(string $label, string $category): string
    {
        foreach ($this->operations as $id => $operation) {
            if (($operation['category'] ?? null) === $category && ($operation['label'] ?? null) === $label) {
                return $id;
            }
        }

        throw new \InvalidArgumentException("Operation label is not approved: {$label}");
    }

    private function validate(): void
    {
        $required = ['label', 'category', 'target_roles', 'playbook', 'maximum_hosts', 'risk'];
        $allowedCategories = ['run_health_checks', 'validate_changes'];
        $seenLabels = [];

        foreach ($this->operations as $id => $operation) {
            if (!preg_match('/^(health|validate)\.[a-z0-9_]+$/', $id)) {
                throw new \RuntimeException("Invalid operation id: {$id}");
            }

            foreach ($required as $field) {
                if (!array_key_exists($field, $operation)) {
                    throw new \RuntimeException("Operation {$id} is missing {$field}.");
                }
            }

            $label = $operation['label'];
            if (!is_string($label) || trim($label) === '' || isset($seenLabels[$label])) {
                throw new \RuntimeException("Operation {$id} has an invalid or duplicate label.");
            }
            $seenLabels[$label] = true;

            if (!in_array($operation['category'], $allowedCategories, true)) {
                throw new \RuntimeException("Operation {$id} has an invalid category.");
            }

            if (!is_array($operation['target_roles']) || $operation['target_roles'] === []) {
                throw new \RuntimeException("Operation {$id} must allow at least one target role.");
            }

            if (!is_int($operation['maximum_hosts']) || $operation['maximum_hosts'] < 1) {
                throw new \RuntimeException("Operation {$id} has an invalid maximum host count.");
            }

            $playbook = $operation['playbook'];
            if (
                !is_string($playbook)
                || !str_starts_with($playbook, 'ansible/playbooks/')
                || str_contains($playbook, '..')
                || !str_ends_with($playbook, '.yml')
            ) {
                throw new \RuntimeException("Operation {$id} has an unsafe playbook path.");
            }

            if ($operation['category'] === 'validate_changes') {
                $component = $operation['component'] ?? null;
                if (!is_string($component) || !in_array($component, ['apache', 'php', 'mysql'], true)) {
                    throw new \RuntimeException("Operation {$id} has an invalid comparison component.");
                }
            }

            $expectedResults = $operation['expected_results'] ?? null;
            if ($expectedResults !== null && (!is_int($expectedResults) || $expectedResults < 1)) {
                throw new \RuntimeException("Operation {$id} has an invalid expected result count.");
            }
        }
    }
}
