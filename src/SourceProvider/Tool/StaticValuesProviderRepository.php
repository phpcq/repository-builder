<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\SourceProvider\Tool;

use Generator;
use Phpcq\RepositoryDefinition\Tool\ToolVersion;

use function array_values;

final readonly class StaticValuesProviderRepository implements ToolVersionProvidingRepositoryInterface
{
    /** @var list<ToolVersion> */
    private array $tools;

    public function __construct(ToolVersion ...$tools)
    {
        $this->tools = array_values($tools);
    }

    public function isFresh(): bool
    {
        return true;
    }

    public function refresh(): void
    {
        // No op.
    }

    public function getToolIterator(): Generator
    {
        foreach ($this->tools as $tool) {
            yield $tool;
        }
    }
}
