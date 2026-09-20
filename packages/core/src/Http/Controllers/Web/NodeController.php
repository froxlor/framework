<?php

namespace Froxlor\Core\Http\Controllers\Web;

use Froxlor\Core\Http\Controllers\Controller;
use Froxlor\Core\Models\Node;
use Froxlor\Core\Resources\Nodes\NodeResource;
use Froxlor\Core\Services\Node\Setup\NodeSetupService;
use Illuminate\Http\RedirectResponse;
use Froxlor\UI\Support\UI;

class NodeController extends Controller
{
    public function index()
    {
        return UI::render(NodeResource::class, 'index');
    }

    public function create()
    {
        return UI::render(NodeResource::class, 'create');
    }

    public function show(Node $node)
    {
        return UI::render(NodeResource::class, 'show', [
            'node' => $node
        ]);
    }

    public function edit(Node $node)
    {
        return UI::render(NodeResource::class, 'edit', [
            'node' => $node
        ]);
    }

    public function setup(Node $node, NodeSetupService $setups): RedirectResponse
    {
        $setups->request($node, request()->user());

        return redirect()
            ->route('resources.nodes.show', ['node' => $node])
            ->with('message', trans('froxlor-core::generic.node_setup_queued'));
    }
}
