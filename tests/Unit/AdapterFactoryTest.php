<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\AdapterFactory;
use CreativeCrafts\LaravelAiAssistant\Adapters\AudioSpeechAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\AudioTranscriptionAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\AudioTranslationAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\ChatCompletionAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\EndpointAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\ImageEditAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\ImageGenerationAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\ImageVariationAdapter;
use CreativeCrafts\LaravelAiAssistant\Adapters\ResponseApiAdapter;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;

beforeEach(function () {
    $this->factory = new AdapterFactory();
});

it('creates AudioTranscriptionAdapter for AudioTranscription endpoint', function () {
    $adapter = $this->factory->make(OpenAiEndpoint::AudioTranscription);

    expect($adapter)->toBeInstanceOf(AudioTranscriptionAdapter::class);
    expect($adapter)->toBeInstanceOf(EndpointAdapter::class);
});

it('creates AudioTranslationAdapter for AudioTranslation endpoint', function () {
    $adapter = $this->factory->make(OpenAiEndpoint::AudioTranslation);

    expect($adapter)->toBeInstanceOf(AudioTranslationAdapter::class);
    expect($adapter)->toBeInstanceOf(EndpointAdapter::class);
});

it('creates AudioSpeechAdapter for AudioSpeech endpoint', function () {
    $adapter = $this->factory->make(OpenAiEndpoint::AudioSpeech);

    expect($adapter)->toBeInstanceOf(AudioSpeechAdapter::class);
    expect($adapter)->toBeInstanceOf(EndpointAdapter::class);
});

it('creates ImageGenerationAdapter for ImageGeneration endpoint', function () {
    $adapter = $this->factory->make(OpenAiEndpoint::ImageGeneration);

    expect($adapter)->toBeInstanceOf(ImageGenerationAdapter::class);
    expect($adapter)->toBeInstanceOf(EndpointAdapter::class);
});

it('creates ImageEditAdapter for ImageEdit endpoint', function () {
    $adapter = $this->factory->make(OpenAiEndpoint::ImageEdit);

    expect($adapter)->toBeInstanceOf(ImageEditAdapter::class);
    expect($adapter)->toBeInstanceOf(EndpointAdapter::class);
});

it('creates ImageVariationAdapter for ImageVariation endpoint', function () {
    $adapter = $this->factory->make(OpenAiEndpoint::ImageVariation);

    expect($adapter)->toBeInstanceOf(ImageVariationAdapter::class);
    expect($adapter)->toBeInstanceOf(EndpointAdapter::class);
});

it('creates ChatCompletionAdapter for ChatCompletion endpoint', function () {
    $adapter = $this->factory->make(OpenAiEndpoint::ChatCompletion);

    expect($adapter)->toBeInstanceOf(ChatCompletionAdapter::class);
    expect($adapter)->toBeInstanceOf(EndpointAdapter::class);
});

it('creates ResponseApiAdapter for ResponseApi endpoint', function () {
    $adapter = $this->factory->make(OpenAiEndpoint::ResponseApi);

    expect($adapter)->toBeInstanceOf(ResponseApiAdapter::class);
    expect($adapter)->toBeInstanceOf(EndpointAdapter::class);
});

it('caches and reuses adapter instances for the same endpoint', function () {
    $adapter1 = $this->factory->make(OpenAiEndpoint::AudioTranscription);
    $adapter2 = $this->factory->make(OpenAiEndpoint::AudioTranscription);

    expect($adapter1)->toBe($adapter2);
    expect($adapter1)->toBeInstanceOf(AudioTranscriptionAdapter::class);
    expect($adapter2)->toBeInstanceOf(AudioTranscriptionAdapter::class);
});

it('all adapters implement EndpointAdapter interface', function () {
    $endpoints = [
        OpenAiEndpoint::AudioTranscription,
        OpenAiEndpoint::AudioTranslation,
        OpenAiEndpoint::AudioSpeech,
        OpenAiEndpoint::ImageGeneration,
        OpenAiEndpoint::ImageEdit,
        OpenAiEndpoint::ImageVariation,
        OpenAiEndpoint::ChatCompletion,
        OpenAiEndpoint::ResponseApi,
    ];

    foreach ($endpoints as $endpoint) {
        $adapter = $this->factory->make($endpoint);
        expect($adapter)->toBeInstanceOf(EndpointAdapter::class);
    }
});

it('all adapters have transformRequest method', function () {
    $endpoints = [
        OpenAiEndpoint::AudioTranscription,
        OpenAiEndpoint::AudioTranslation,
        OpenAiEndpoint::AudioSpeech,
        OpenAiEndpoint::ImageGeneration,
        OpenAiEndpoint::ImageEdit,
        OpenAiEndpoint::ImageVariation,
        OpenAiEndpoint::ChatCompletion,
        OpenAiEndpoint::ResponseApi,
    ];

    foreach ($endpoints as $endpoint) {
        $adapter = $this->factory->make($endpoint);
        expect(method_exists($adapter, 'transformRequest'))->toBeTrue();
    }
});

it('all adapters have transformResponse method', function () {
    $endpoints = [
        OpenAiEndpoint::AudioTranscription,
        OpenAiEndpoint::AudioTranslation,
        OpenAiEndpoint::AudioSpeech,
        OpenAiEndpoint::ImageGeneration,
        OpenAiEndpoint::ImageEdit,
        OpenAiEndpoint::ImageVariation,
        OpenAiEndpoint::ChatCompletion,
        OpenAiEndpoint::ResponseApi,
    ];

    foreach ($endpoints as $endpoint) {
        $adapter = $this->factory->make($endpoint);
        expect(method_exists($adapter, 'transformResponse'))->toBeTrue();
    }
});
