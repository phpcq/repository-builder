<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider;

final class ToolVersionFilterRegistry
{
    /** @var array<string, ToolVersionFilter> */
    private array $filters = [];

    /** @param list<ToolVersionFilter> $filters */
    public function __construct(array $filters)
    {
        foreach ($filters as $filter) {
            $this->filters[$filter->getToolName()] = $filter;
        }
    }

    public function getFilterForTool(string $name): ToolVersionFilter
    {
        return $this->filters[$name] ?? new ToolVersionFilter($name, ToolVersionFilter::NEVER_MATCHING_CONSTRAINT);
    }
}
