<?php

declare(strict_types=1);

namespace Phpcq\RepositoryBuilder\File;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;

class XmlFile {
    private ?DOMDocument $dom = null;

    private DOMXPath $xPath;

    private string $filename;

    private string $namespace;

    private string $rootElementName;

    public function __construct(string $filename, string $namespace, string $root)
    {
        $this->filename        = $filename;
        $this->namespace       = $namespace;
        $this->rootElementName = $root;
    }

    public function createElement(string $name, string $text = ''): DOMElement
    {
        return $this->getDom()->createElementNS($this->namespace, $name, $text);
    }

    public function query(string $xpath, DOMNode $ctx = null): DOMNodeList
    {
        if ($ctx === null) {
            $ctx = $this->getDom()->documentElement;
        }

        return $this->getXPath()->query($xpath, $ctx);
    }

    public function addElement(DOMNode $node): void
    {
        $this->getDom()->documentElement->appendChild($node);
    }

    public function save(): void
    {
        $this->getDom()->save($this->filename);
    }

    public function getDom(): DOMDocument
    {
        $this->init();

        return $this->dom;
    }

    private function init(): void
    {
        if ($this->dom instanceof DOMDocument) {
            return;
        }

        $this->dom                     = new DOMDocument('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput       = true;

        if ($this->filename) {
            $this->dom->load($this->filename);
        } else {
            $this->dom->appendChild($this->dom->createElementNS($this->namespace, $this->rootElementName));
        }
        $this->xPath = new DOMXPath($this->dom);
        $this->xPath->registerNamespace('rootNs', $this->namespace);
    }

    private function getXPath(): DOMXPath
    {
        $this->init();

        return $this->xPath;
    }
}