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
}
