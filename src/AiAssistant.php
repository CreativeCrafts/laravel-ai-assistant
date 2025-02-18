<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant;

use CreativeCrafts\LaravelAiAssistant\Contracts\AiAssistantContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatMessageData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CustomFunctionData;
use CreativeCrafts\LaravelAiAssistant\Services\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;

class AiAssistant implements AiAssistantContract
{
    protected AssistantService $client;

    protected array $textGeneratorConfig = [];

    protected array $chatTextGeneratorConfig = [];

    protected array $editTextGeneratorConfig = [];

    protected array $audioToTextGeneratorConfig = [];

    /**
     * Constructs a new AiAssistant instance.
     */
    public function __construct(
        protected string $prompt = ''
    ) {
        $this->textGeneratorConfig = AppConfig::textGeneratorConfig();
        $this->chatTextGeneratorConfig = AppConfig::chatTextGeneratorConfig();
        $this->editTextGeneratorConfig = AppConfig::editTextGeneratorConfig();
        $this->audioToTextGeneratorConfig = AppConfig::audioToTextGeneratorConfig();
    }

    /**
     * Accepts a prompt and returns a new instance of the AiAssistant class.
     *
     * This method is used to create a new AiAssistant instance with a given prompt.
     * It is a static method, allowing for a fluent interface when initializing the AiAssistant class.
     */
    public static function acceptPrompt(string $prompt): self
    {
        return new self($prompt);
    }

    public static function init(?AssistantService $client = null): Assistant
    {
        $client = $client ?? new AssistantService();
        return Assistant::new()->client($client);
    }

    /**
     * Sets the AssistantService client for making API requests.
     */
    public function client(AssistantService $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Generates a draft based on the provided prompt.
     *
     * This method sets the prompt in the text generator configuration and calls the processTextCompletion method to generate the draft.
     */
    public function draft(): string
    {
        $this->textGeneratorConfig['prompt'] = $this->prompt;
        return $this->processTextCompletion();
    }

    /**
     * Translates the current prompt to the specified language.
     *
     * This method appends a translation instruction to the current prompt and calls the processTextCompletion method to generate the translated text.
     */
    public function translateTo(string $language): string
    {
        $this->textGeneratorConfig['prompt'] = 'translate this' . ". $this->prompt . " . 'to' . $language;
        return $this->processTextCompletion();
    }

    /**
     * Initiates a chat response based on the current prompt.
     *
     * This method prepares the chat text generator configuration with the current prompt,
     * then calls the processChatTextCompletion method to generate the chat response.
     */
    public function andRespond(): array
    {
        $this->chatTextGeneratorConfig['messages'] = (new ChatMessageData($this->prompt))->messages();
        return $this->processChatTextCompletion();
    }

    /**
     * Adds a custom function to the chat text generator configuration and processes the chat completion.
     *
     * This method takes a CustomFunctionData object as a parameter, extracts the messages from the ChatMessageData instance,
     * and appends the custom function data to the chat text generator configuration. It then calls the processChatTextCompletion method
     * to generate the chat response with the custom function.
     */
    public function withCustomFunction(CustomFunctionData $customFunctionData): array
    {
        $this->chatTextGeneratorConfig['messages'] = (new ChatMessageData($this->prompt))->messages();
        $this->chatTextGeneratorConfig['functions'] = $customFunctionData->toArray();
        return $this->processChatTextCompletion();
    }

    /**
     * Performs spelling and grammar correction on the current prompt.
     *
     * This method appends a specific instruction to the prompt, indicating that the AI assistant should fix the spelling and grammar errors.
     * It then calls the processTextEditCompletion method to generate the corrected text.
     */
    public function spellingAndGrammarCorrection(): string
    {
        $instructions = 'Fix the spelling and grammar errors in the following text.';
        return $this->processTextEditCompletion($instructions);
    }

    /**
     * Improves the readability of the current prompt by generating an edited version.
     *
     * This method appends a specific instruction to the prompt, indicating that the AI assistant should edit the text to make it more readable.
     * It then calls the processTextEditCompletion method to generate the improved text.
     */
    public function improveWriting(): string
    {
        $instructions = 'Edit the following text to make it more readable.';
        return $this->processTextEditCompletion($instructions);
    }

    /**
     * Transcribes an audio file to text using the specified language.
     *
     * This method opens the audio file specified by the prompt, sets the language for transcription,
     * and optionally provides an optional text prompt for the transcription process.
     * It then calls the transcribeTo method of the AssistantService client to perform the transcription.
     */
    public function transcribeTo(string $language, ?string $optionalText = ''): string
    {
        trigger_error('The transcribeTo method is deprecated. Use the transcribeTo method of the AssistantService client instead.', E_USER_DEPRECATED);

        $this->audioToTextGeneratorConfig['file'] = fopen($this->prompt, 'rb');
        if ($optionalText !== '') {
            $this->audioToTextGeneratorConfig['prompt'] = $optionalText;
        }
        $this->audioToTextGeneratorConfig['language'] = $language;
        return $this->client->transcribeTo($this->audioToTextGeneratorConfig);
    }

    /**
     * Translates an audio file to text using the specified language.
     *
     * This function opens the audio file specified by the prompt, sets the language for transcription,
     * and then calls the translateTo method of the AssistantService client to perform the translation.
     */
    public function translateAudioTo(): string
    {
        $this->audioToTextGeneratorConfig['file'] = fopen($this->prompt, 'rb');
        return $this->client->translateTo($this->audioToTextGeneratorConfig);
    }

    /**
     * Processes the text completion request using the provided configuration.
     *
     * This method checks if the 'stream' option is set in the text generator configuration.
     * If it is, it calls the 'streamedCompletion' method of the AssistantService client.
     * Otherwise, it calls the 'textCompletion' method of the AssistantService client.
     */
    protected function processTextCompletion(): string
    {
        trigger_error('The processTextCompletion method is deprecated. Use the processChatTextCompletion method of the AssistantService client instead.', E_USER_DEPRECATED);
        if (isset($this->textGeneratorConfig['stream']) && $this->textGeneratorConfig['stream']) {
            return $this->client->streamedCompletion($this->textGeneratorConfig);
        }
        return $this->client->textCompletion($this->textGeneratorConfig);
    }

    /**
     * Processes the chat text completion request using the provided configuration.
     *
     * This method checks if the 'stream' option is set in the chat text generator configuration.
     * If it is, it calls the 'streamedChat' method of the AssistantService client.
     * Otherwise, it calls the 'chatTextCompletion' method of the AssistantService client.
     * After processing the request, it caches the conversation using the ChatMessageData instance.
     */
    protected function processChatTextCompletion(): array
    {
        trigger_error('The processChatTextCompletion method is deprecated. Use the processChatTextCompletion method of the AssistantService client instead.', E_USER_DEPRECATED);
        if (isset($this->chatTextGeneratorConfig['stream']) && $this->chatTextGeneratorConfig['stream']) {
            $response = $this->client->streamedChat($this->chatTextGeneratorConfig);
        } else {
            $response = $this->client->chatTextCompletion($this->chatTextGeneratorConfig);
        }
        (new ChatMessageData($this->prompt))->cacheConversation($response);
        return $response;
    }

    /**
     * Processes the text edit completion request using the provided instructions.
     *
     * This function checks the model specified in the edit text generator configuration.
     * If the model is 'gpt-3.5-turbo' or starts with 'gpt-4', it prepares a chat text completion request
     * by setting assistant instructions and calling the processChatTextCompletion method.
     * The function then returns the content of the first message in the response.
     *
     * If the model is not 'gpt-3.5-turbo' or does not start with 'gpt-4', it prepares a text completion request
     * by appending the instructions and the prompt to the text generator configuration,
     * and then calls the processTextCompletion method.
     */
    protected function processTextEditCompletion(string $instructions): string
    {
        if ($this->editTextGeneratorConfig['model'] === 'gpt-3.5-turbo' || str_starts_with($this->editTextGeneratorConfig['model'], 'gpt-4')) {
            $this->chatTextGeneratorConfig['messages'] = (new ChatMessageData($this->prompt))
                ->setAssistantInstructions($instructions);
            $response = $this->processChatTextCompletion();
            return $response['choices'][0]['message']['content'];
        }
        // @pest-mutate-ignore
        $this->textGeneratorConfig['prompt'] = $instructions . "\n\nText to edit: " . $this->prompt;
        return $this->processTextCompletion();
    }
}
