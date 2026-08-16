<?php

declare(strict_types=1);

namespace ForgeFlow\Automation;

final class TargetCatalog
{
    /** @var array<string, array<string, mixed>> */
    private array $healthTargets;

    /** @var array<string, array<string, mixed>> */
    private array $comparisonTargets;

    public function __construct(string $path)
    {
        $document = JsonFile::read($path);
        if (($document['catalog_version'] ?? null) !== 1) {
            throw new \RuntimeException('Unsupported target catalogue version.');
        }

        $this->healthTargets = $this->normalizeSection($document['health_targets'] ?? null, 'health_targets');
        $this->comparisonTargets = $this->normalizeSection(
            $document['comparison_targets'] ?? null,
            'comparison_targets'
        );
    }


    /**
     * @return list<string>
     */
    public function healthLabels(): array
    {
        return array_keys($this->healthTargets);
    }

    /**
     * @return list<string>
     */
    public function comparisonLabels(): array
    {
        return array_keys($this->comparisonTargets);
    }

    /**
     * @return array<string, mixed>
     */
    public function health(string $label): array
    {
        $this->rejectHeading($label);
        if (!isset($this->healthTargets[$label])) {
            throw new \InvalidArgumentException("Health target is not approved: {$label}");
        }
        return $this->healthTargets[$label];
    }

    /**
     * @return array<string, mixed>
     */
    public function comparison(string $label): array
    {
        $this->rejectHeading($label);
        if (!isset($this->comparisonTargets[$label])) {
            throw new \InvalidArgumentException("Comparison target is not approved: {$label}");
        }
        return $this->comparisonTargets[$label];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function normalizeSection(mixed $section, string $name): array
    {
        if (!is_array($section)) {
            throw new \RuntimeException("Target catalogue section {$name} is missing.");
        }

        $result = [];
        foreach ($section as $label => $target) {
            if (!is_string($label) || !is_array($target)) {
                throw new \RuntimeException("Target catalogue section {$name} contains an invalid entry.");
            }
            /** @var array<string, mixed> $target */
            $result[$label] = $target;
        }
        return $result;
    }

    private function rejectHeading(string $label): void
    {
        if (str_starts_with(trim($label), '----')) {
            throw new \InvalidArgumentException('Section headings cannot be selected.');
        }
    }
}
