<?php
/*
 * This file is part of the <package> package.
 *
 * (c) Daniel Leech <daniel@dantleech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DTL\PhpcrMigrations;

use PHPCR\SessionInterface;

class VersionStorage
{
    private $session;
    private $storageNodeName;
    private $initialized = false;

    public function __construct(SessionInterface $session, $storageNodeName = 'phpcrMigrations:versions')
    {
        $this->session = $session;
        $this->storageNodeName = $storageNodeName;
    }

    public function init()
    {
        if ($this->initialized) {
            return;
        }

        $this->workspace = $this->session->getWorkspace();
        $nodeTypeManager = $this->workspace->getNodeTypeManager();

        if (!$nodeTypeManager->hasNodeType('phpcrMigration:version')) {
            $nodeTypeManager->registerNodeTypesCnd(<<<EOT
<phpcrMigrations = 'http://www.danteech.com/phpcr-migrations'>
[phpcrMigrations:version] > nt:base, mix:created

[phpcrMigrations:versions] > nt:base
+* (phpcrMigrations:version)
EOT
            , true);
        }

        $rootNode = $this->session->getRootNode();

        if ($rootNode->hasNode($this->storageNodeName)) {
            $storageNode = $rootNode->getNode($this->storageNodeName);
        } else {
            $storageNode = $rootNode->addNode($this->storageNodeName, 'phpcrMigrations:versions');
        }

        $this->storageNode = $storageNode;
    }

    public function getPersistedVersions()
    {
        $this->init();

        $versions = $this->storageNode->getNodeNames();
        return $versions;
    }

    public function hasVersioningNode()
    {
        return $this->session->nodeExists('/' . $this->storageNodeName);
    }

    public function getCurrentVersion()
    {
        $this->init();

        $versions = (array) $this->storageNode->getNodeNames();

        if (!$versions) {
            return null;
        }
        
        asort($versions);

        return end($versions);
    }

    public function remove($timestamp)
    {
        $this->init();

        $this->storageNode->getNode($timestamp)->remove();
    }

    public function add($timestamp)
    {
        $this->init();

        $node = $this->storageNode->addNode($timestamp, 'phpcrMigrations:version');
    }
}
