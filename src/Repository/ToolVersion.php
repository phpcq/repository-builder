<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\Repository;

class ToolVersion
{
    private string $name;
    private string $version;
    private ?string $pharUrl;
    private ?string $signatureUrl;
    private ?ToolHash $hash;
    private VersionRequirementList $requirements;
    private ?BootstrapInterface $bootstrap;

    public function __construct(
        string $name,
        string $version,
        ?string $pharUrl,
        ?array $requirements,
        ?ToolHash $hash,
        ?string $signatureUrl,
        ?BootstrapInterface $bootstrap
    ) {
        $this->name         = $name;
        $this->version      = $version;
        $this->pharUrl      = $pharUrl;
        $this->hash         = $hash;
        $this->signatureUrl = $signatureUrl;
        $this->requirements = new VersionRequirementList($requirements ?? []);
        $this->bootstrap    = $bootstrap;
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

    public function getRequirements(): VersionRequirementList
    {
        return $this->requirements;
    }

    /**
     * Retrieve bootstrap.
     *
     * @return BootstrapInterface|null
     */
    public function getBootstrap(): ?BootstrapInterface
    {
        return $this->bootstrap;
    }

    /**
     * Set bootstrap.
     *
     * @param BootstrapInterface|null $bootstrap The new value.
     *
     * @return ToolVersion
     */
    public function setBootstrap(?BootstrapInterface $bootstrap): self
    {
        $this->bootstrap = $bootstrap;

        return $this;
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
                $this->setHash(new ToolHash($data->getType(), $data->getValue()));
            }
        }
        foreach ($other->getRequirements() as $requirement) {
            if (!$this->requirements->has($requirement->getName())) {
                $this->requirements->add(new VersionRequirement(
                    $requirement->getName(),
                    $requirement->getConstraint()
                ));
            }
        }
        if (null !== ($data = $other->getBootstrap()) && $data !== $this->bootstrap) {
            $this->setBootstrap($data);
        }
    }
}
