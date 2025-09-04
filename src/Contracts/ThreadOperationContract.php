<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

use CreativeCrafts\LaravelAiAssistant\Exceptions\MaxRetryAttemptsExceededException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ThreadExecutionTimeoutException;

/**
 * Contract for thread operation management.
 *
 * This interface defines methods for creating threads, managing messages,
 * and running thread operations.
 */
interface ThreadOperationContract
{
    /**
     * Create a new thread with the specified parameters.
     *
     * This function creates a new thread using the OpenAI API client.
     *
     * @param array $parameters An array of parameters for creating the thread.
     *                          This may include properties such as messages or metadata.
     *
     * @return \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse The response object containing details of the created thread.
     */
    public function createThread(array $parameters): \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\ThreadResponse;

    /**
     * Write a new message to a specific thread.
     *
     * This function creates a new message within an existing thread using the OpenAI API client.
     *
     * @param string $threadId    The unique identifier of the thread to which the message will be added.
     * @param array  $messageData An array containing the message data. This may include properties such as
     *                            'role' (e.g., 'user' or 'assistant') and 'content' (the message text).
     *
     * @return \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse The response object containing details of the created message.
     */
    public function writeMessage(string $threadId, array $messageData): \CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Threads\Messages\ThreadMessageResponse;

    /**
     * Run a message thread and wait for its completion.
     *
     * This function creates a new run for a specified thread, then continuously checks
     * the run's status until it is completed. It uses a robust polling mechanism with
     * exponential backoff, timeout handling, and maximum retry attempts.
     *
     * @param string $threadId           The unique identifier of the thread to run.
     * @param array  $runThreadParameter An array of parameters for running the thread.
     *                                   This may include properties such as assistant_id,
     *                                   model, instructions, or tools.
     * @param int    $timeoutSeconds     Maximum time to wait for completion (default: 300 seconds).
     * @param int    $maxRetryAttempts   Maximum number of retry attempts (default: 60).
     * @param float  $initialDelay       Initial delay between checks in seconds (default: 1.0).
     * @param float  $backoffMultiplier  Multiplier for exponential backoff (default: 1.5).
     * @param float  $maxDelay           Maximum delay between checks in seconds (default: 30.0).
     *
     * @return bool Returns true when the run is completed successfully.
     *
     * @throws ThreadExecutionTimeoutException When the execution exceeds the timeout limit.
     * @throws MaxRetryAttemptsExceededException When maximum retry attempts are exceeded.
     */
    public function runMessageThread(
        string $threadId,
        array $runThreadParameter,
        int $timeoutSeconds = 300,
        int $maxRetryAttempts = 60,
        float $initialDelay = 1.0,
        float $backoffMultiplier = 1.5,
        float $maxDelay = 30.0
    ): bool;

    /**
     * List messages for a specific thread and return the content of the first message.
     *
     * This function retrieves all messages for a given thread using the OpenAI API client,
     * and returns the text content of the first message in the list.
     *
     * @param string $threadId The unique identifier of the thread whose messages are to be listed.
     *
     * @return string The text content of the first message in the thread, or an empty string if no messages are found.
     */
    public function listMessages(string $threadId): string;
}
