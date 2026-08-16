<?php

declare(strict_types=1);

namespace ForgeFlow\Automation;

final class ComparisonResolver
{
    public function __construct(
        private readonly OperationCatalog $operations,
        private readonly TargetCatalog $targets,
        private readonly string $inventory = 'ansible/inventories/demo/hosts.yml'
    ) {
    }

    /**
     * @return array{
     *   resolution_version:int,
     *   operation:string,
     *   operation_label:string,
     *   category:string,
     *   component:string,
     *   requested_target:string,
     *   target_role:string,
     *   inventory:string,
     *   playbook:string,
     *   risk:string,
     *   left_host:string,
     *   right_host:string,
     *   resolved_hosts:list<string>,
     *   resolved_host_count:int,
     *   maximum_hosts:int,
     *   ansible_limit:string
     * }
     */
    public function resolveByLabels(string $operationLabel, string $targetLabel): array
    {
        $operationId = $this->operations->idForLabel($operationLabel, 'validate_changes');

        return $this->resolve($operationId, $targetLabel);
    }

    /**
     * @return array{
     *   resolution_version:int,
     *   operation:string,
     *   operation_label:string,
     *   category:string,
     *   component:string,
     *   requested_target:string,
     *   target_role:string,
     *   inventory:string,
     *   playbook:string,
     *   risk:string,
     *   left_host:string,
     *   right_host:string,
     *   resolved_hosts:list<string>,
     *   resolved_host_count:int,
     *   maximum_hosts:int,
     *   ansible_limit:string
     * }
     */
    public function resolve(string $operationId, string $targetLabel): array
    {
        $operation = $this->operations->get($operationId);

        if (($operation['category'] ?? null) !== 'validate_changes') {
            throw new \InvalidArgumentException('Requested operation is not a configuration comparison.');
        }

        $operationLabel = $operation['label'] ?? null;
        $component = $operation['component'] ?? null;
        $playbook = $operation['playbook'] ?? null;
        $risk = $operation['risk'] ?? null;
        $allowedRoles = $operation['target_roles'] ?? null;
        $maximumHosts = $operation['maximum_hosts'] ?? null;

        if (
            !is_string($operationLabel)
            || !is_string($component)
            || !is_string($playbook)
            || !is_string($risk)
            || !is_array($allowedRoles)
            || !is_int($maximumHosts)
        ) {
            throw new \RuntimeException('Comparison operation metadata is malformed.');
        }

        $target = $this->targets->comparison($targetLabel);
        $role = $target['role'] ?? null;
        $left = $target['left'] ?? null;
        $right = $target['right'] ?? null;

        if (!is_string($role) || !is_string($left) || !is_string($right)) {
            throw new \RuntimeException('Comparison target metadata is malformed.');
        }

        if (!in_array($role, $allowedRoles, true)) {
            throw new \InvalidArgumentException(
                "Comparison role {$role} is not approved for {$operationId}."
            );
        }

        if ($left === $right) {
            throw new \InvalidArgumentException('A host cannot be compared with itself.');
        }

        if ($maximumHosts < 2) {
            throw new \InvalidArgumentException('Comparison exceeds the approved blast radius.');
        }

        return [
            'resolution_version' => 1,
            'operation' => $operationId,
            'operation_label' => $operationLabel,
            'category' => 'validate_changes',
            'component' => $component,
            'requested_target' => $targetLabel,
            'target_role' => $role,
            'inventory' => $this->inventory,
            'playbook' => $playbook,
            'risk' => $risk,
            'left_host' => $left,
            'right_host' => $right,
            'resolved_hosts' => [$left, $right],
            'resolved_host_count' => 2,
            'maximum_hosts' => $maximumHosts,
            'ansible_limit' => $left . ',' . $right,
        ];
    }
}
