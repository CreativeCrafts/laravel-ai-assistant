# Realtime & Live

The Realtime API powers low-latency speech-to-speech apps (voice agents, phone bots, live translation). The
browser talks to OpenAI directly over WebRTC; your Laravel app's job is to **mint short-lived credentials**
and, for phone calls, to **control SIP calls**.

## Voice agent in the browser (WebRTC)

### 1. Mint an ephemeral client secret

Never ship your API key to the browser. Create a short-lived secret per session instead:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

Route::post('/realtime/session', function () {
    $secret = Ai::realtime()->createClientSecret([
        'expires_after' => ['anchor' => 'created_at', 'seconds' => 600],
        'session' => [
            'type' => 'realtime',
            'model' => 'gpt-realtime',
            'instructions' => 'You are a friendly assistant for Acme. Keep answers short.',
            'audio' => ['output' => ['voice' => 'marin']],
        ],
    ]);

    return ['client_secret' => $secret['value'], 'expires_at' => $secret['expires_at']];
})->middleware(['auth', 'throttle:10,1']);
```

### 2. Connect from React

```tsx
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';

export function VoiceAgent() {
    const pc = useRef<RTCPeerConnection | null>(null);
    const [connected, setConnected] = useState(false);

    async function start() {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content;
        const { client_secret } = await fetch('/realtime/session', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        }).then((r) => r.json());

        const peer = new RTCPeerConnection();
        const audio = new Audio();
        audio.autoplay = true;
        peer.ontrack = (e) => (audio.srcObject = e.streams[0]);

        const mic = await navigator.mediaDevices.getUserMedia({ audio: true });
        peer.addTrack(mic.getTracks()[0]);

        const events = peer.createDataChannel('oai-events');
        events.onmessage = (e) => console.log(JSON.parse(e.data)); // transcripts, tool calls, ...

        const offer = await peer.createOffer();
        await peer.setLocalDescription(offer);

        const answer = await fetch('https://api.openai.com/v1/realtime/calls', {
            method: 'POST',
            body: offer.sdp,
            headers: { Authorization: `Bearer ${client_secret}`, 'Content-Type': 'application/sdp' },
        });

        await peer.setRemoteDescription({ type: 'answer', sdp: await answer.text() });
        pc.current = peer;
        setConnected(true);
    }

    function stop() {
        pc.current?.close();
        setConnected(false);
    }

    return connected ? <Button onClick={stop}>Hang up</Button> : <Button onClick={start}>Talk</Button>;
}
```

### Alternative: create the call server-side

If you'd rather keep the SDP exchange on your server (for example to attach server-side tools or logging),
forward the browser's SDP offer and return OpenAI's answer:

```php
Route::post('/realtime/call', function (Request $request) {
    $call = Ai::realtime()->createCall($request->getContent(), [
        'type' => 'realtime',
        'model' => 'gpt-realtime',
        'instructions' => 'You are a helpful assistant.',
    ]);

    // $call['call_id'] lets you control the call later (hang up, refer, ...)
    return response($call['sdp'], 201, ['Content-Type' => 'application/sdp']);
})->middleware('auth');
```

## Phone calls (SIP)

Point your SIP trunk at OpenAI, then handle the `realtime.call.incoming` webhook (see [Webhooks](webhooks.md)):

```php
use CreativeCrafts\LaravelAiAssistant\Events\OpenAiWebhookReceived;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

Event::listen(function (OpenAiWebhookReceived $event) {
    if ($event->type !== 'realtime.call.incoming') {
        return;
    }

    $callId = $event->data['call_id'];

    if (! BusinessHours::open()) {
        Ai::realtime()->rejectCall($callId, ['status_code' => 486]);   // Busy Here
        return;
    }

    Ai::realtime()->acceptCall($callId, [
        'type' => 'realtime',
        'model' => 'gpt-realtime',
        'instructions' => 'You are the Acme phone assistant. Greet the caller and ask how you can help.',
    ]);
});

// Later: transfer to a human, or end the call
Ai::realtime()->referCall($callId, ['target_uri' => 'tel:+2348000000000']);
Ai::realtime()->hangupCall($callId);
```

## Live transcription and translation

```php
// Ephemeral secret for a transcription-only session
$session = Ai::realtime()->createTranscriptionSession([
    'input_audio_transcription' => ['model' => 'gpt-4o-transcribe', 'language' => 'en'],
]);

// Ephemeral secret for live speech translation
$secret = Ai::realtime()->createTranslationClientSecret([/* session configuration */]);
```

## Live API

`Ai::live()` covers OpenAI's Live sessions (WebRTC sessions, SIP call control, forking and recordings):

```php
$session = Ai::live()->create([/* type, model, startup configuration */]);

Ai::live()->accept($sessionId, ['session' => ['type' => 'live', 'model' => 'gpt-realtime']]);
Ai::live()->reject($sessionId, ['status_code' => 603]);
Ai::live()->refer($sessionId, ['target_uri' => 'sip:support@example.com']);
Ai::live()->fork($sessionId, [/* new connection */]);
Ai::live()->hangup($sessionId);

$recording = Ai::live()->downloadRecording($sessionId);   // ['content' => ..., 'content_type' => ...]
Storage::put("recordings/{$sessionId}.wav", $recording['content']);
```

## Legacy realtime sessions

`Ai::realtimeSessions()->create($payload)` calls the older `POST /v1/realtime/sessions` endpoint. Prefer
`Ai::realtime()->createClientSecret()` for new code.

## Methods

| Method | Endpoint |
|---|---|
| `Ai::realtime()->createClientSecret(array $payload = [])` | `POST /v1/realtime/client_secrets` |
| `Ai::realtime()->createCall(string $sdp, array $session = [])` | `POST /v1/realtime/calls` → `['sdp', 'call_id']` |
| `Ai::realtime()->acceptCall(string $callId, array $payload)` | `POST /v1/realtime/calls/{id}/accept` |
| `Ai::realtime()->rejectCall(string $callId, array $payload = [])` | `POST /v1/realtime/calls/{id}/reject` |
| `Ai::realtime()->referCall(string $callId, array $payload)` | `POST /v1/realtime/calls/{id}/refer` |
| `Ai::realtime()->hangupCall(string $callId)` | `POST /v1/realtime/calls/{id}/hangup` |
| `Ai::realtime()->createTranscriptionSession(array $payload = [])` | `POST /v1/realtime/transcription_sessions` |
| `Ai::realtime()->createTranslationClientSecret(array $payload = [])` | `POST /v1/realtime/translations/client_secrets` |
| `Ai::live()->create / accept / reject / refer / hangup / fork / downloadRecording` | `/v1/live/sessions/...` |
| `Ai::realtimeSessions()->create(array $payload)` | `POST /v1/realtime/sessions` (legacy) |
