# Conversations

A conversation is a thread of messages stored by OpenAI. Send each new turn with the conversation id and the
model sees everything that came before, so you don't have to resend the history yourself.

## Start a conversation and send turns

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

// 1. Create it (optionally with metadata) and keep the id, e.g. on your own Chat model
$conversationId = Ai::conversations()->start(['user_id' => (string) auth()->id(), 'topic' => 'billing']);

// 2. Send a turn
$first = Ai::responses()
    ->inConversation($conversationId)
    ->model('gpt-5-mini')
    ->instructions('You are a helpful billing assistant. Keep answers short.')
    ->input()
    ->message('My name is Ada. Why was I charged twice?')
    ->send();

// 3. Later turns remember the earlier ones
$second = Ai::responses()
    ->inConversation($conversationId)
    ->model('gpt-5-mini')
    ->input()
    ->message('What is my name?')
    ->send();

echo $second->text; // "Your name is Ada."
```

Metadata values must be strings (OpenAI limits metadata to 16 keys).

### A conversation per chat in your app

```php
// app/Models/Chat.php has a nullable string `openai_conversation_id` column
public function ask(string $message): string
{
    $this->openai_conversation_id ??= Ai::conversations()->start(['chat_id' => (string) $this->id]);
    $this->save();

    return Ai::responses()
        ->inConversation($this->openai_conversation_id)
        ->model('gpt-5-mini')
        ->input()
        ->message($message)
        ->send()
        ->text;
}
```

## Working with an existing conversation

`Ai::conversations()` returns a fresh builder each time; call `use($id)` to point it at a conversation:

```php
$conversation = Ai::conversations()->use($conversationId);

$conversation->retrieve();                       // conversation object
$conversation->update(['topic' => 'refunds']);   // replaces the metadata
$conversation->delete();                         // returns bool
```

### Items (the messages inside a conversation)

```php
$conversation = Ai::conversations()->use($conversationId);

// List, newest last
$page = $conversation->items(['limit' => 50, 'order' => 'asc']);

foreach ($page['data'] as $item) {
    $role = $item['role'] ?? $item['type'];
    $text = collect($item['content'] ?? [])->pluck('text')->filter()->implode("\n");
    echo "{$role}: {$text}\n";
}

// Retrieve / delete one item
$conversation->item('msg_123');
$conversation->deleteItem('msg_123');

// Add items without generating a response (e.g. seed context or import history)
$conversation->addItems([
    ['type' => 'message', 'role' => 'user', 'content' => 'I prefer answers in French.'],
    ['type' => 'message', 'role' => 'assistant', 'content' => 'Compris, je répondrai en français.'],
]);
```

### Sending through the conversations builder

The builder can also send turns itself, using input items:

```php
$builder = Ai::conversations();
$builder->start();
$builder->input()->appendUserText('Hello!');
$response = $builder->send();             // ChatResponseDto

// Or get a ResponsesBuilder already bound to the active conversation
$response = $builder->responses()->model('gpt-5-mini')->input()->message('And goodbye!')->send();
```

## Chat sessions use conversations too

`Ai::chat()` creates a conversation on its first `send()` and reuses it for later turns on the same session
object (`$response->conversationId` tells you which one). See [Chat sessions](chat-sessions-and-tools.md).

## Low-level repository

All Conversations API endpoints are also available directly:

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;

$repo = app(ConversationsRepositoryContract::class);

$conversation = $repo->createConversation([
    'metadata' => ['topic' => 'onboarding'],
    'items' => [['type' => 'message', 'role' => 'user', 'content' => 'Hi!']],
]);

$repo->getConversation($conversation['id']);
$repo->updateConversation($conversation['id'], ['metadata' => ['topic' => 'support']]);
$repo->listItems($conversation['id'], ['limit' => 20]);
$repo->createItems($conversation['id'], [['type' => 'message', 'role' => 'user', 'content' => 'More context']]);
$repo->getItem($conversation['id'], 'msg_123');
$repo->deleteItem($conversation['id'], 'msg_123');
$repo->deleteConversation($conversation['id']);
```

## Keeping long conversations small

When a conversation grows large, count and compact it (see [Responses](responses.md#count-tokens-before-sending)):

```php
$tokens = Ai::responses()->countInputTokens(['model' => 'gpt-5', 'conversation' => $conversationId]);
```
