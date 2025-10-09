<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Fakes;

use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;

final class FakeOpenAiRepository implements OpenAiRepositoryContract
{
    public array $calls = [];
    public function createAssistant(array $parameters): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['id' => 'asst_fake'];
    }
    public function getAssistant(string $assistantId): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['id' => $assistantId];
    }
    public function updateAssistant(string $assistantId, array $parameters): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['id' => $assistantId];
    }
    public function deleteAssistant(string $assistantId): bool
    {
    $this->calls[] = __FUNCTION__;
    return true;
    }
    public function getThread(string $threadId): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['id' => $threadId];
    }
    public function updateThread(string $threadId, array $parameters): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['id' => $threadId];
    }
    public function deleteThread(string $threadId): bool
    {
    $this->calls[] = __FUNCTION__;
    return true;
    }
    public function createMessage(string $threadId, array $parameters): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['id' => 'msg_fake'];
    }
    public function createRun(string $threadId, array $parameters): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['id' => 'run_fake'];
    }
    public function getRun(string $threadId, string $runId): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['id' => $runId,'status' => 'completed'];
    }
    public function cancelRun(string $threadId, string $runId): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['id' => $runId,'status' => 'canceled'];
    }
    public function submitToolOutputs(string $threadId, string $runId, array $toolOutputs): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['id' => $runId];
    }
    public function getThreadAndRun(string $threadId, string $runId): array
    {
    $this->calls[] = __FUNCTION__;
    return [(object)['id' => $threadId],(object)['id' => $runId]];
    }
    public function createTranscription(array $parameters): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['text' => 'hello'];
    }
    public function createTranslation(array $parameters): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['text' => 'hej'];
    }
    public function createChatCompletion(array $parameters): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['choices' => [['message' => ['content' => 'ok']]]];
    }
    public function createCompletion(array $parameters): object
    {
    $this->calls[] = __FUNCTION__;
    return (object)['choices' => [['text' => 'ok']]];
    }
}
