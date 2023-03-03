<?php

namespace App\Http\Controllers\Api;

use App\Models\Terminal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TerminalResource;
use App\Http\Resources\TerminalCollection;
use App\Http\Requests\TerminalStoreRequest;
use App\Http\Requests\TerminalUpdateRequest;
use Illuminate\Support\Str;

class TerminalController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', Terminal::class);

        $search = $request->get('search', '');

        $terminals = Terminal::search($search)
            ->latest()
            ->get();

        return new TerminalCollection($terminals);
    }

    /**
     * @param \App\Http\Requests\TerminalStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(TerminalStoreRequest $request)
    {
        $this->authorize('create', Terminal::class);

        $validated = $request->validated();
        $validated["authorization_key"] = (Str::uuid())->toString();
        $terminal = Terminal::create($validated);

        return new TerminalResource($terminal);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Terminal $terminal
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Terminal $terminal)
    {
        $this->authorize('view', $terminal);

        return new TerminalResource($terminal);
    }

    /**
     * @param \App\Http\Requests\TerminalUpdateRequest $request
     * @param \App\Models\Terminal $terminal
     * @return \Illuminate\Http\Response
     */
    public function update(TerminalUpdateRequest $request, Terminal $terminal)
    {
        $this->authorize('update', $terminal);

        $validated = $request->validated();

        $terminal->update($validated);

        return new TerminalResource($terminal);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Terminal $terminal
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Terminal $terminal)
    {
        $this->authorize('delete', $terminal);

        $terminal->delete();

        return response()->noContent();
    }
}
