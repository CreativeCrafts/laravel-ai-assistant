<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts\Agents;

/**
 * Agents API (beta): managed agent sessions, events, turns, subagents, traces and artifacts.
 * Requests carry the OpenAI-Beta: agents=v1 header.
 * Resolve through Ai::agentSessions().
 */
interface AgentSessionsRepositoryContract
{
    /**
     * Start a managed agent session.
     *
     * POST /v1/agents/sessions
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array;

    /**
     * Start a managed agent session and stream its events as Server-Sent Events (stream=true).
     *
     * POST /v1/agents/sessions
     *
     * @param array<string, mixed> $payload
     * @return iterable<array<string, mixed>> Decoded Server-Sent Events
     */
    public function stream(array $payload): iterable;

    /**
     * Retrieves the current state of a managed agent session. See managing sessions.
     *
     * GET /v1/agents/sessions/{sessionId}
     */
    public function retrieve(string $sessionId): array;

    /**
     * Updates session metadata, model, reasoning effort, or service tier. Model settings apply to subsequent turns. Omitted fields are unchanged. See managing sessions.
     *
     * POST /v1/agents/sessions/{sessionId}
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $sessionId, array $payload): array;

    /**
     * Lists managed agent sessions using ID-based pagination and the requested sort order. See managing sessions.
     *
     * GET /v1/agents/sessions
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function list(array $params = []): array;

    /**
     * Removes a managed agent session from the public API and returns a deletion confirmation. If backend execution has ended, deletion can cancel a still-open public turn and abandon unpublished outputs.
     *
     * DELETE /v1/agents/sessions/{sessionId}
     */
    public function delete(string $sessionId): array;

    /**
     * Submits message, cancellation, tool-result, or computer-use approval-response events to a managed agent session.
     *
     * POST /v1/agents/sessions/{sessionId}/events
     *
     * @param array<string, mixed> $payload
     */
    public function createEvent(string $sessionId, array $payload): array;

    /**
     * Streams live events for an agent session. See session events.
     *
     * GET /v1/agents/sessions/{sessionId}/events
     *
     * @return iterable<array<string, mixed>> Decoded Server-Sent Events
     */
    public function streamEvents(string $sessionId): iterable;

    /**
     * Lists items produced by the session's root agent, including its interactions with subagents. Each subagent has its own item history. See inspecting agent output.
     *
     * GET /v1/agents/sessions/{sessionId}/items
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listItems(string $sessionId, array $params = []): array;

    /**
     * Lists published root-turn traces as OTLP JSON, ordered by turn creation time and ID. Unpublished traces are skipped. Each page returns data available when read; it does not wait for late traces.
     *
     * GET /v1/agents/sessions/{sessionId}/traces
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listTraces(string $sessionId, array $params = []): array;

    /**
     * Lists immutable artifacts published by completed hosted session turns. See session artifacts.
     *
     * GET /v1/agents/sessions/{sessionId}/artifacts
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listArtifacts(string $sessionId, array $params = []): array;

    /**
     * Retrieves immutable metadata for one durable session artifact.
     *
     * GET /v1/agents/sessions/{sessionId}/artifacts/{artifactId}
     */
    public function retrieveArtifact(string $sessionId, string $artifactId): array;

    /**
     * Deletes an immutable session artifact without deleting its live environment file or original Files API object. See session artifacts.
     *
     * DELETE /v1/agents/sessions/{sessionId}/artifacts/{artifactId}
     */
    public function deleteArtifact(string $sessionId, string $artifactId): array;

    /**
     * Downloads immutable session artifact bytes after the execution environment expires. See session artifacts.
     *
     * GET /v1/agents/sessions/{sessionId}/artifacts/{artifactId}/content
     *
     * @return array{content: string, content_type: string}
     */
    public function artifactContent(string $sessionId, string $artifactId): array;

    /**
     * Lists turns by creation time and turn ID. The after cursor is exclusive in the selected order. See session turns.
     *
     * GET /v1/agents/sessions/{sessionId}/turns
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listTurns(string $sessionId, array $params = []): array;

    /**
     * Retrieves a turn's current status, timestamps, usage, and error. Returns 404 if the turn does not belong to the session. See session turns.
     *
     * GET /v1/agents/sessions/{sessionId}/turns/{turnId}
     */
    public function retrieveTurn(string $sessionId, string $turnId): array;

    /**
     * Lists items belonging to one root-agent turn, including its interactions with subagents. See inspecting agent output.
     *
     * GET /v1/agents/sessions/{sessionId}/turns/{turnId}/items
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listTurnItems(string $sessionId, string $turnId, array $params = []): array;

    /**
     * Lists subagents in a session, including nested and closed subagents. See subagent workflows.
     *
     * GET /v1/agents/sessions/{sessionId}/subagents
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listSubagents(string $sessionId, array $params = []): array;

    /**
     * Retrieves a subagent belonging to this session. See subagent workflows.
     *
     * GET /v1/agents/sessions/{sessionId}/subagents/{subagentId}
     */
    public function retrieveSubagent(string $sessionId, string $subagentId): array;

    /**
     * Lists this subagent's own items across all of its turns. See subagent workflows.
     *
     * GET /v1/agents/sessions/{sessionId}/subagents/{subagentId}/items
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listSubagentItems(string $sessionId, string $subagentId, array $params = []): array;

    /**
     * Lists all turns of this subagent, including turns after a resume. See subagent workflows.
     *
     * GET /v1/agents/sessions/{sessionId}/subagents/{subagentId}/turns
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listSubagentTurns(string $sessionId, string $subagentId, array $params = []): array;

    /**
     * Retrieves a turn belonging to this subagent. See subagent workflows.
     *
     * GET /v1/agents/sessions/{sessionId}/subagents/{subagentId}/turns/{turnId}
     */
    public function retrieveSubagentTurn(string $sessionId, string $subagentId, string $turnId): array;

    /**
     * Lists items belonging to one turn of this subagent. See subagent workflows.
     *
     * GET /v1/agents/sessions/{sessionId}/subagents/{subagentId}/turns/{turnId}/items
     *
     * @param array<string, mixed> $params Query parameters
     */
    public function listSubagentTurnItems(string $sessionId, string $subagentId, string $turnId, array $params = []): array;
}
