<?php

declare(strict_types=1);

namespace ForgeFlow\Automation;

final class HealthCheckResolver
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
     *   requested_target:string,
     *   target_role:string,
     *   inventory:string,
     *   playbook:string,
     *   risk:string,
     *   resolved_hosts:list<string>,
     *   resolved_host_count:int,
     *   maximum_hosts:int,
     *   ansible_limit:string,
     *   expected_result_count:int
     * }
     */
    public function resolveByLabels(string $operationLabel, string $targetLabel): array
    {
        $operationId = $this->operations->idForLabel($operationLabel, 'run_health_checks');

        return $this->resolve($operationId, $targetLabel);
    }

    /**
     * @return array{
     *   resolution_version:int,
     *   operation:string,
     *   operation_label:string,
     *   category:string,
     *   requested_target:string,
     *   target_role:string,
     *   inventory:string,
     *   playbook:string,
     *   risk:string,
     *   resolved_hosts:list<string>,
     *   resolved_host_count:int,
     *   maximum_hosts:int,
     *   ansible_limit:string,
     *   expected_result_count:int
     * }
     */
    public function resolve(string $operationId, string $targetLabel): array
    {
        $operation = $this->operations->get($operationId);

        if (($operation['category'] ?? null) !== 'run_health_checks') {
            throw new \InvalidArgumentException('Requested operation is not a health check.');
        }

        $operationLabel = $operation['label'] ?? null;
        $playbook = $operation['playbook'] ?? null;
        $risk = $operation['risk'] ?? null;
        $allowedRoles = $operation['target_roles'] ?? null;
        $maximumHosts = $operation['maximum_hosts'] ?? null;
        $expectedResults = $operation['expected_results'] ?? null;

        if (
            !is_string($operationLabel)
            || !is_string($playbook)
            || !is_string($risk)
            || !is_array($allowedRoles)
            || !is_int($maximumHosts)
            || ($expectedResults !== null && !is_int($expectedResults))
        ) {
            throw new \RuntimeException('Health operation metadata is malformed.');
        }

        $target = $this->targets->health($targetLabel);
        $role = $target['role'] ?? null;
        $hosts = $target['hosts'] ?? null;

        if (!is_string($role) || !is_array($hosts)) {
            throw new \RuntimeException('Health target metadata is malformed.');
        }

        if (!in_array($role, $allowedRoles, true)) {
            throw new \InvalidArgumentException(
                "Target role {$role} is not approved for {$operationId}."
            );
        }

        $resolvedHosts = [];
        foreach ($hosts as $host) {
            if (!is_string($host) || $host === '') {
                throw new \RuntimeException('Resolved health target contains an invalid host.');
            }
            $resolvedHosts[] = $host;
        }

        if (count($resolvedHosts) < 1 || count($resolvedHosts) > $maximumHosts) {
            throw new \InvalidArgumentException('Resolved health target exceeds the approved blast radius.');
        }

        return [
            'resolution_version' => 1,
            'operation' => $operationId,
            'operation_label' => $operationLabel,
            'category' => 'run_health_checks',
            'requested_target' => $targetLabel,
            'target_role' => $role,
            'inventory' => $this->inventory,
            'playbook' => $playbook,
            'risk' => $risk,
            'resolved_hosts' => $resolvedHosts,
            'resolved_host_count' => count($resolvedHosts),
            'maximum_hosts' => $maximumHosts,
            'ansible_limit' => implode(',', $resolvedHosts),
            'expected_result_count' => $expectedResults ?? count($resolvedHosts),
        ];
    }
}
