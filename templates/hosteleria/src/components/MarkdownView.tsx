import { useMemo } from 'react';

/**
 * MarkdownView - renderer markdown minimalista para legales y wiki.
 *
 * Soporta: h1-h3, parrafos, listas no ordenadas, tablas pipe, **bold**,
 * `inline code` y enlaces [texto](url). Ignora tags HTML por seguridad.
 *
 * Sin dependencias externas. Suficiente para el contenido de
 * /public/legal y /public/wiki que escribimos nosotros.
 */

type Block =
    | { type: 'h1' | 'h2' | 'h3' | 'p'; text: string }
    | { type: 'ul'; items: string[] }
    | { type: 'ol'; items: string[] }
    | { type: 'pre'; text: string }
    | { type: 'table'; head: string[]; rows: string[][] }
    | { type: 'hr' };

function escape(s: string): string {
    return s.replace(/[<>&]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' } as Record<string, string>)[c]);
}

function inline(s: string): string {
    let out = escape(s);
    out = out.replace(/`([^`]+)`/g, '<code>$1</code>');
    out = out.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    out = out.replace(/\*([^*]+)\*/g, '<em>$1</em>');
    out = out.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_m, txt: string, url: string) => {
        const safe = url.replace(/"/g, '%22');
        const isHttp = /^https?:\/\//i.test(safe);
        const target = isHttp ? ' target="_blank" rel="noopener noreferrer"' : '';
        return `<a href="${safe}"${target}>${txt}</a>`;
    });
    return out;
}

function parse(md: string): Block[] {
    const cleaned = md.replace(/^---\s*[\s\S]*?---\s*/m, '');
    const lines = cleaned.split(/\r?\n/);
    const out: Block[] = [];
    let i = 0;
    while (i < lines.length) {
        const line = lines[i];
        if (!line.trim()) { i++; continue; }

        if (/^---+\s*$/.test(line)) { out.push({ type: 'hr' }); i++; continue; }

        if (line.startsWith('### ')) { out.push({ type: 'h3', text: line.slice(4).trim() }); i++; continue; }
        if (line.startsWith('## '))  { out.push({ type: 'h2', text: line.slice(3).trim() }); i++; continue; }
        if (line.startsWith('# '))   { out.push({ type: 'h1', text: line.slice(2).trim() }); i++; continue; }

        if (line.startsWith('```')) {
            i++;
            const buf: string[] = [];
            while (i < lines.length && !lines[i].startsWith('```')) { buf.push(lines[i]); i++; }
            i++;
            out.push({ type: 'pre', text: buf.join('\n') });
            continue;
        }

        if (/^\s*[-*]\s+/.test(line)) {
            const items: string[] = [];
            while (i < lines.length && /^\s*[-*]\s+/.test(lines[i])) {
                items.push(lines[i].replace(/^\s*[-*]\s+/, '').trim());
                i++;
            }
            out.push({ type: 'ul', items });
            continue;
        }

        if (/^\s*\d+\.\s+/.test(line)) {
            const items: string[] = [];
            while (i < lines.length && /^\s*\d+\.\s+/.test(lines[i])) {
                items.push(lines[i].replace(/^\s*\d+\.\s+/, '').trim());
                i++;
            }
            out.push({ type: 'ol', items });
            continue;
        }

        if (/^\|.+\|$/.test(line) && i + 1 < lines.length && /^\|[\s\-:|]+\|$/.test(lines[i + 1])) {
            const head = line.split('|').slice(1, -1).map((c) => c.trim());
            i += 2;
            const rows: string[][] = [];
            while (i < lines.length && /^\|.+\|$/.test(lines[i])) {
                rows.push(lines[i].split('|').slice(1, -1).map((c) => c.trim()));
                i++;
            }
            out.push({ type: 'table', head, rows });
            continue;
        }

        const buf: string[] = [line];
        i++;
        while (i < lines.length && lines[i].trim() && !/^(#|\s*[-*]|\s*\d+\.|\||```|---)/.test(lines[i])) {
            buf.push(lines[i]); i++;
        }
        out.push({ type: 'p', text: buf.join(' ') });
    }
    return out;
}

export function MarkdownView({ source }: { source: string }) {
    const blocks = useMemo(() => parse(source || ''), [source]);
    return (
        <div className="md-view">
            {blocks.map((b, idx) => {
                switch (b.type) {
                    case 'h1': return <h1 key={idx} dangerouslySetInnerHTML={{ __html: inline(b.text) }} />;
                    case 'h2': return <h2 key={idx} dangerouslySetInnerHTML={{ __html: inline(b.text) }} />;
                    case 'h3': return <h3 key={idx} dangerouslySetInnerHTML={{ __html: inline(b.text) }} />;
                    case 'p':  return <p  key={idx} dangerouslySetInnerHTML={{ __html: inline(b.text) }} />;
                    case 'hr': return <hr key={idx} />;
                    case 'pre': return <pre key={idx}><code>{b.text}</code></pre>;
                    case 'ul': return (
                        <ul key={idx}>
                            {b.items.map((it, j) => <li key={j} dangerouslySetInnerHTML={{ __html: inline(it) }} />)}
                        </ul>
                    );
                    case 'ol': return (
                        <ol key={idx}>
                            {b.items.map((it, j) => <li key={j} dangerouslySetInnerHTML={{ __html: inline(it) }} />)}
                        </ol>
                    );
                    case 'table': return (
                        <table key={idx} className="md-table">
                            <thead><tr>{b.head.map((c, j) => <th key={j} dangerouslySetInnerHTML={{ __html: inline(c) }} />)}</tr></thead>
                            <tbody>
                                {b.rows.map((row, j) => (
                                    <tr key={j}>{row.map((c, k) => <td key={k} dangerouslySetInnerHTML={{ __html: inline(c) }} />)}</tr>
                                ))}
                            </tbody>
                        </table>
                    );
                    default: return null;
                }
            })}
        </div>
    );
}
