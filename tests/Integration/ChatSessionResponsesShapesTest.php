<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use CreativeCrafts\LaravelAiAssistant\Services\ToolRegistry;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\ResponsesFactory;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeConversationsRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeFilesRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeResponsesRepository;

// Chat helpers must send request shapes the Responses API accepts (POST /v1/responses)

beforeEach(function () {
    config()->set('ai-assistant.api_key', 'test_key_123');

    $this->responses = new FakeResponsesRepository();
    app()->instance(ResponsesRepositoryContract::class, $this->responses);
    app()->instance(ConversationsRepositoryContract::class, new FakeConversationsRepository());
    app()->instance(FilesRepositoryContract::class, new FakeFilesRepository());
});

it('sends function tools in the flat Responses shape', function () {
    Ai::chat('Where is order A-1?')
        ->includeFunctionCallTool('get_order_status', 'Look up an order', [
            'properties' => ['order_number' => ['type' => 'string']],
            'required' => ['order_number'],
        ])
        ->send();

    expect($this->responses->lastPayload['tools'])->toBe([[
        'type' => 'function',
        'name' => 'get_order_status',
        'description' => 'Look up an order',
        'parameters' => [
            'type' => 'object',
            'properties' => ['order_number' => ['type' => 'string']],
            'required' => ['order_number'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ]]);
});

it('sends a function tool without parameters as an empty object schema', function () {
    $session = Ai::chat('What time is it?');
    $session->tools()->includeFunctionCallTool('current_time', 'Current server time', []);
    $session->send();

    $tool = $this->responses->lastPayload['tools'][0];
    expect($tool['name'])->toBe('current_time')
        ->and($tool)->not->toHaveKey('function')
        ->and(json_encode($tool['parameters']))->toBe('{"type":"object","properties":{},"required":[],"additionalProperties":false}');
});

it('sends file_search with its vector store ids', function () {
    Ai::chat('What is the refund window?')->includeFileSearchTool(['vs_1'])->send();

    expect($this->responses->lastPayload['tools'])->toBe([['type' => 'file_search', 'vector_store_ids' => ['vs_1']]]);
});

it('merges vector store ids from the tools builder and the session', function () {
    $session = Ai::chat('Search both stores')->includeFileSearchTool(['vs_1']);
    $session->tools()->includeFileSearchTool(['vs_2']);
    $session->send();

    expect($this->responses->lastPayload['tools'])->toBe([['type' => 'file_search', 'vector_store_ids' => ['vs_1', 'vs_2']]]);
});

it('rejects file_search without a vector store before calling the API', function () {
    expect(fn () => Ai::chat('Search')->includeFileSearchTool()->send())
        ->toThrow(InvalidArgumentException::class, 'vector store');

    expect($this->responses->lastPayload)->toBe([]);
});

it('sends code_interpreter with an auto container and its files', function () {
    $session = Ai::chat('Plot the CSV');
    $session->tools()->includeCodeInterpreterTool(['file_csv']);
    $session->send();

    expect($this->responses->lastPayload['tools'])->toBe([[
        'type' => 'code_interpreter',
        'container' => ['type' => 'auto', 'file_ids' => ['file_csv']],
    ]]);
});

it('sends JSON schema output in the flat text.format shape', function () {
    Ai::chat('Classify: charged twice')
        ->setResponseFormatJsonSchema([
            'type' => 'object',
            'properties' => ['category' => ['type' => 'string']],
            'required' => ['category'],
            'additionalProperties' => false,
        ], 'ticket')
        ->send();

    expect($this->responses->lastPayload['text']['format'])->toBe([
        'type' => 'json_schema',
        'name' => 'ticket',
        'schema' => [
            'type' => 'object',
            'properties' => ['category' => ['type' => 'string']],
            'required' => ['category'],
            'additionalProperties' => false,
        ],
    ]);
});

it('sends text output as a format object', function () {
    Ai::chat('Hello')->setResponseFormatText()->send();

    expect($this->responses->lastPayload['text']['format'])->toBe(['type' => 'text']);
});

it('sends the session temperature', function () {
    Ai::chat('Hello')->setTemperature(0.2)->send();

    expect($this->responses->lastPayload['temperature'])->toBe(0.2);
});

it('sends attached files as input_file blocks for that turn only', function () {
    $session = Ai::chat('Summarise the contract')->attachFiles(['file_contract']);
    $session->send();

    expect($this->responses->lastPayload['input'][0]['content'])->toBe([
        ['type' => 'input_text', 'text' => 'Summarise the contract'],
        ['type' => 'input_file', 'file_id' => 'file_contract'],
    ])->and($this->responses->lastPayload)->not->toHaveKey('tools');

    $session->setUserMessage('Thanks')->send();

    expect($this->responses->lastPayload['input'][0]['content'])->toBe([
        ['type' => 'input_text', 'text' => 'Thanks'],
    ]);
});

it('runs a registered tool for a function_call and keeps the tools on the continuation', function () {
    app(ToolRegistry::class)->register('get_order_status', fn (array $args) => ['status' => 'shipped', 'order' => $args['order_number']]);

    $this->responses->pushResponse(ResponsesFactory::withToolCalls('conv_1', [
        ['id' => 'call_1', 'name' => 'get_order_status', 'arguments' => ['order_number' => 'A-1']],
    ]));
    $this->responses->pushResponse(ResponsesFactory::afterToolResultsFinal('conv_1', 'Order A-1 has shipped.'));

    $response = Ai::chat('Where is order A-1?')
        ->includeFunctionCallTool('get_order_status', 'Look up an order', [
            'properties' => ['order_number' => ['type' => 'string']],
            'required' => ['order_number'],
        ])
        ->send();

    $continuation = $this->responses->lastPayload;

    expect($response->text)->toBe('Order A-1 has shipped.')
        ->and($continuation['input'])->toBe([[
            'type' => 'function_call_output',
            'call_id' => 'call_1',
            'output' => '{"status":"shipped","order":"A-1"}',
        ]])
        ->and($continuation['tools'][0]['name'])->toBe('get_order_status');
});

it('reads function_call items as tool calls and still reads legacy tool_call items', function () {
    $this->responses->pushResponse([
        'id' => 'resp_1',
        'status' => 'completed',
        'output' => [
            ['type' => 'function_call', 'id' => 'fc_1', 'call_id' => 'call_1', 'name' => 'lookup', 'arguments' => '{"q":"a"}'],
            ['type' => 'tool_call', 'id' => 'call_2', 'name' => 'legacy', 'arguments' => ['q' => 'b']],
        ],
    ]);
    config()->set('ai-assistant.tool_calling.max_rounds', 1);
    $this->responses->pushResponse(ResponsesFactory::afterToolResultsFinal('conv_1', 'done'));

    $assistant = app(AssistantService::class);
    $assistant->sendChatMessage('conv_1', 'go');

    // Neither tool is registered, so both get an error output under their call id
    expect(array_keys(ResponsesFactory::functionCallOutputs($this->responses->lastPayload)))->toBe(['call_1', 'call_2']);
});

it('sends a specific tool choice in the flat Responses shape', function () {
    Ai::chat('Where is order A-1?')
        ->includeFunctionCallTool('get_order_status', 'Look up an order', [
            'properties' => ['order_number' => ['type' => 'string']],
            'required' => ['order_number'],
        ])
        ->setToolChoice(['type' => 'function', 'function' => ['name' => 'get_order_status']])
        ->send();

    expect($this->responses->lastPayload['tool_choice'])->toBe(['type' => 'function', 'name' => 'get_order_status']);
});

it('keeps the JSON output format on the answer that follows a tool call', function () {
    $this->responses->pushResponse(ResponsesFactory::withToolCalls('conv_1', [
        ['id' => 'call_1', 'name' => 'missing_tool', 'arguments' => []],
    ]));
    $this->responses->pushResponse(ResponsesFactory::afterToolResultsFinal('conv_1', '{"category":"billing"}'));

    Ai::chat('Classify: charged twice')
        ->includeFunctionCallTool('missing_tool', 'Not registered', [])
        ->setResponseFormatJsonSchema(['type' => 'object', 'properties' => ['category' => ['type' => 'string']]], 'ticket')
        ->send();

    expect($this->responses->lastPayload['input'][0]['type'])->toBe('function_call_output')
        ->and($this->responses->lastPayload['text']['format']['name'])->toBe('ticket');
});

it('keeps the temperature and output token limit on the answer that follows a tool call', function () {
    $this->responses->pushResponse(ResponsesFactory::withToolCalls('conv_1', [
        ['id' => 'call_1', 'name' => 'missing_tool', 'arguments' => []],
    ]));
    $this->responses->pushResponse(ResponsesFactory::afterToolResultsFinal('conv_1', 'done'));

    Ai::chat('Hello')
        ->includeFunctionCallTool('missing_tool', 'Not registered', [])
        ->setTemperature(0.2)
        ->send();

    expect($this->responses->lastPayload['input'][0]['type'])->toBe('function_call_output')
        ->and($this->responses->lastPayload['temperature'])->toBe(0.2);

    $this->responses->pushResponse(ResponsesFactory::withToolCalls('conv_2', [
        ['id' => 'call_2', 'name' => 'missing_tool', 'arguments' => []],
    ]));
    $this->responses->pushResponse(ResponsesFactory::afterToolResultsFinal('conv_2', 'done'));

    app(AssistantService::class)->sendTurn('conv_2', null, 'gpt-test', [], [], maxCompletionTokens: 64);

    expect($this->responses->lastPayload['input'][0]['type'])->toBe('function_call_output')
        ->and($this->responses->lastPayload['max_output_tokens'])->toBe(64);
});

it('keeps the session temperature when tool results are sent manually', function () {
    $session = Ai::chat('Hello')->setTemperature(0.4);
    $session->send();

    $session->continueWithToolResults([['tool_call_id' => 'call_9', 'output' => 'ok']]);

    expect($this->responses->lastPayload['input'][0]['call_id'])->toBe('call_9')
        ->and($this->responses->lastPayload['temperature'])->toBe(0.4);
});
