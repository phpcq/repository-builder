<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository\Tool;

use Phpcq\RepositoryBuilder\Repository\VersionRequirement;
use Phpcq\RepositoryBuilder\Repository\VersionRequirementList;

class ToolVersion
{
    private string $name;
    private string $version;
    private ?string $pharUrl;
    private ?string $signatureUrl;
    private ?ToolHash $hash;
    private ToolRequirements $requirements;

    public function __construct(
        string $name,
        string $version,
        ?string $pharUrl,
        ?ToolRequirements $requirements,
        ?ToolHash $hash,
        ?string $signatureUrl
    ) {
        $this->name         = $name;
        $this->version      = $version;
        $this->pharUrl      = $pharUrl;
        $this->hash         = $hash;
        $this->signatureUrl = $signatureUrl;
        $this->requirements = $requirements ?? new ToolRequirements();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getPharUrl(): ?string
    {
        return $this->pharUrl;
    }

    public function setPharUrl(string $pharUrl): self
    {
        $this->pharUrl = $pharUrl;

        return $this;
    }

    public function getHash(): ?ToolHash
    {
        return $this->hash;
    }

    public function setHash(ToolHash $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getSignatureUrl(): ?string
    {
        return $this->signatureUrl;
    }

    public function setSignatureUrl(string $signatureUrl): self
    {
        $this->signatureUrl = $signatureUrl;

        return $this;
    }

    public function getRequirements(): ToolRequirements
    {
        return $this->requirements;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function merge(ToolVersion $other): void
    {
        if (null !== ($data = $other->getPharUrl()) && $data !== $this->pharUrl) {
            $this->setPharUrl($data);
        }
        if (null !== ($data = $other->getSignatureUrl()) && $data !== $this->signatureUrl) {
            $this->setSignatureUrl($data);
        }
        if (null !== ($data = $other->getHash()) && $data !== $this->hash) {
            if (
                null === $this->hash
                || $data->getType() !== $this->hash->getType()
                || $data->getValue() !== $this->hash->getValue()
            ) {
                $this->setHash(ToolHash::create($data->getType(), $data->getValue()));
            }
        }

        $otherRequirements = $other->getRequirements();
        foreach (
            [
                [$this->requirements->getPhpRequirements(), $otherRequirements->getPhpRequirements()],
                [$this->requirements->getComposerRequirements(), $otherRequirements->getComposerRequirements()],
            ] as $lists
        ) {
            /** @var VersionRequirementList[] $lists */
            $target = $lists[0];
            $source = $lists[1];
            foreach ($source as $requirement) {
                if (!$target->has($requirement->getName())) {
                    $target->add(new VersionRequirement(
                        $requirement->getName(),
                        $requirement->getConstraint()
                    ));
                }
            }
        }
    }
}
