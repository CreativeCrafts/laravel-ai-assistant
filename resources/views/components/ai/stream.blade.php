<div x-data="{ lines: [] }" x-init="
    const src = $el.dataset.src || '/ai/stream';
    const es = new EventSource(src);
    es.addEventListener('message', (e) => { lines.push(e.data); });
    es.addEventListener('done', () => es.close());
">
    <pre class='whitespace-pre-wrap' x-text="lines.join('')"></pre>
</div>
