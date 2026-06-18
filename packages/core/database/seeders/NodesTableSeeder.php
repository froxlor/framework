<?php

namespace Froxlor\Core\Database\Seeders;

use Exception;
use Froxlor\Core\Models\Node;
use Illuminate\Database\Seeder;

class NodesTableSeeder extends Seeder
{
    /**
     * Seed the default node type settings and the initial root node.
     *
     * The root node is baseline data because environments need at least one node target.
     * Local installations use the in-process adapter, while remote development setups can
     * opt into the remote adapter via `dev.node=remote`.
     *
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        // global node settings
        Node::addTypeSetting('node.basedir', '/var/environments', '/var/environments');
        Node::addTypeSetting('node.username_prefix', 'usr', 'usr');
        Node::addTypeSetting('node.adminmail', 'root@localhost', 'root@localhost', 'email');

        // set node based on environment
        if (config('dev.node') === 'remote') {
            $rootNode = $this->remoteNode();
        } else {
            $rootNode = $this->localNode();
        }

        // settings overwrites
        $rootNode->addSetting('node.basedir', '/srv/environments', Node::getTypeSetting('node.basedir'));
        $rootNode->addSetting('node.last_username_number', 0, null, 'integer', ['visible' => false]);
        $rootNode->addSetting('node.last_guid_number', 9999, null, 'integer', ['visible' => false]);
    }

    /**
     * Create the default local node used by production bootstrap and local tests.
     */
    private function localNode(): Node
    {
        return Node::query()->create([
            'adapter' => 'Froxlor\\Core\\Services\\Node\\Adapter\\Local',
            'name' => 'Local',
            'hostname' => 'localhost',
            'username' => 'root',
            'sudo' => true
        ]);
    }

    /**
     * Create a remote-adapter node for development stacks that exercise remote provisioning.
     */
    private function remoteNode(): Node
    {
        return Node::query()->create([
            'adapter' => 'Froxlor\\Adapter\\Remote\\Adapters\\Remote',
            'name' => 'Remote',
            'hostname' => 'node',
            'username' => 'frxlocal',
            'sudo' => true,
            'properties->ssh_key' => <<<EOK
                -----BEGIN OPENSSH PRIVATE KEY-----
                b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
                QyNTUxOQAAACAXIZDHG8Ew+ikxyXCAsHzfKktCZQxnMsgwiz+FQT69uwAAAJjC1h7vwtYe
                7wAAAAtzc2gtZWQyNTUxOQAAACAXIZDHG8Ew+ikxyXCAsHzfKktCZQxnMsgwiz+FQT69uw
                AAAEBLkfchbuNYy54VKWuaHqYLmdqsx/dRj2dcmdektiSLLBchkMcbwTD6KTHJcICwfN8q
                S0JlDGcyyDCLP4VBPr27AAAAEXJvb3RANzE2OTcwOTc3YTBmAQIDBA==
                -----END OPENSSH PRIVATE KEY-----
                EOK
        ]);
    }
}
