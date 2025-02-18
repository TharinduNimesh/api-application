import { codeToHtml } from 'shiki';
import { ref } from 'vue';

export function useCodeHighlighter() {
    const highlightCode = async (code, lang = 'json') => {
        try {
            return await codeToHtml(code, {
                lang,
                theme: 'min-light'
            });
        } catch (error) {
            console.error('Syntax highlighting failed:', error);
            // Fallback to plain text if highlighting fails
            return `<pre><code>${code}</code></pre>`;
        }
    };

    return {
        highlightCode
    };
}
