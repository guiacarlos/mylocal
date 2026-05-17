import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { useSynaxisClient } from '@mylocal/sdk';
import { Loader2 } from 'lucide-react';

/**
 * El subdominio (via seed) es la fuente de verdad para el contexto legal,
 * no la sesión. Así:
 *   mylocal.es/legal          → seed='mylocal'  → políticas de empresa GestasAI
 *   lacocinadeana.mylocal.es/legal → seed=slug  → políticas del hostelero
 * Fallback a sessionStorage solo si el seed no responde.
 */
async function resolveLocalId(): Promise<string> {
  try {
    const r = await fetch('/seed/bootstrap.json', { cache: 'no-store' });
    const j = await r.json() as { local_id?: string };
    if (j.local_id) return j.local_id;
  } catch { /* */ }
  try { const s = sessionStorage.getItem('mylocal_localId'); if (s) return s; } catch { /* */ }
  return '';
}

function renderMarkdown(md: string): string {
  return md
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/^### (.+)$/gm, '<h3 class="text-base font-semibold mt-6 mb-2">$1</h3>')
    .replace(/^## (.+)$/gm, '<h2 class="text-lg font-bold mt-8 mb-3 text-black">$1</h2>')
    .replace(/^# (.+)$/gm, '<h1 class="text-2xl font-bold mb-1 text-black">$1</h1>')
    .replace(/^---$/gm, '<hr class="my-6 border-gray-100" />')
    .replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold text-black">$1</strong>')
    .replace(/\*(.+?)\*/g, '<em>$1</em>')
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-black underline hover:text-gray-600 transition-colors" target="_blank" rel="noopener noreferrer">$1</a>')
    .replace(/^\| (.+) \|$/gm, (_, row) => {
      const cells = row.split(' | ').map((c: string) =>
        `<td class="border border-gray-100 px-3 py-2 text-[12px] align-top">${c}</td>`
      ).join('');
      return `<tr class="even:bg-gray-50">${cells}</tr>`;
    })
    .replace(/(<tr[^>]*>.*?<\/tr>\s*)+/gs, t => `<table class="w-full border-collapse my-4 text-[12px]">${t}</table>`)
    .replace(/^\|[-| ]+\|$/gm, '')
    .replace(/^- (.+)$/gm, '<li class="ml-5 list-disc text-[13px] leading-relaxed">$1</li>')
    .replace(/(<li[^>]*>.*?<\/li>\s*)+/gs, t => `<ul class="my-3 space-y-1.5">${t}</ul>`)
    .replace(/\n\n/g, '</p><p class="text-[13px] leading-relaxed text-gray-700 my-2">')
    .replace(/^(?!<)(.+)$/gm, '<p class="text-[13px] leading-relaxed text-gray-700 my-2">$1</p>');
}

const COMPANY_DOCS: Record<string, string> = {
  aviso:             'Aviso Legal y Condiciones de Contratación',
  privacidad:        'Política de Privacidad',
  cookies:           'Política de Cookies',
  reembolsos:        'Política de Cancelación y Reembolsos',
  'canal-denuncias': 'Canal de Denuncias',
};

const LOCAL_DOCS: Record<string, string> = {
  privacidad: 'Política de Privacidad',
  aviso:      'Aviso Legal',
  cookies:    'Política de Cookies',
};

export default function LegalPage() {
  const { doc = 'privacidad' } = useParams<{ doc: string }>();
  const client = useSynaxisClient();

  const [html,      setHtml]      = useState('');
  const [titulo,    setTitulo]    = useState('');
  const [isCompany, setIsCompany] = useState(false);
  const [loading,   setLoading]   = useState(true);
  const [error,     setError]     = useState(false);

  useEffect(() => {
    (async () => {
      setLoading(true);
      setError(false);

      const localId = await resolveLocalId();
      // Sin sesión activa o desde la landing → documentos de empresa (local_id vacío)
      const fetchId = localId || '';

      try {
        const r = await client.execute<{ contenido: string; titulo: string; tipo?: string }>({
          action: 'get_legal',
          data:   { local_id: fetchId, doc },
        });
        if (r.success && r.data?.contenido) {
          setHtml(renderMarkdown(r.data.contenido));
          setTitulo(r.data.titulo ?? COMPANY_DOCS[doc] ?? 'Legal');
          setIsCompany(r.data.tipo === 'company' || fetchId === '');
        } else {
          setError(true);
        }
      } catch { setError(true); }
      setLoading(false);
    })();
  }, [client, doc]);

  const docLinks = isCompany ? COMPANY_DOCS : LOCAL_DOCS;

  if (loading) return (
    <div className="min-h-screen bg-[#F9F9F7] flex items-center justify-center">
      <Loader2 className="w-5 h-5 animate-spin text-gray-400" />
    </div>
  );

  if (error) return (
    <div className="min-h-screen bg-[#F9F9F7] flex items-center justify-center px-6">
      <p className="text-sm text-gray-400">Documento no disponible.</p>
    </div>
  );

  return (
    <div className="min-h-screen bg-[#F9F9F7]">
      {/* Nav mínima */}
      <div className="border-b border-gray-100 bg-white/90 backdrop-blur-sm sticky top-0 z-10">
        <div className="max-w-2xl mx-auto px-6 h-14 flex items-center justify-between">
          <a href="/" className="text-sm font-display font-bold tracking-tighter">My Local</a>
          <span className="text-[10px] font-mono text-gray-400 uppercase tracking-widest hidden sm:block">
            {titulo}
          </span>
        </div>
      </div>

      <div className="max-w-2xl mx-auto px-6 py-12">
        {isCompany && (
          <div className="mb-6 p-4 bg-white rounded-2xl border border-gray-100">
            <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-0.5">Titular</p>
            <p className="text-[13px] font-medium text-gray-700">GESTASAI TECNOLOGY SL — CIF E23950967</p>
            <p className="text-[12px] text-gray-500">C/ Farmacéutico José María López Leal, 7 — 30820 Alcantarilla (Murcia)</p>
          </div>
        )}

        <div
          className="prose prose-sm max-w-none text-gray-800"
          dangerouslySetInnerHTML={{ __html: html }}
        />

        {/* Navegación entre documentos */}
        <div className="mt-12 pt-6 border-t border-gray-100">
          <p className="text-[10px] font-mono text-gray-400 uppercase tracking-widest mb-4">
            {isCompany ? 'Documentos legales de MyLocal' : 'Documentos del local'}
          </p>
          <div className="flex flex-wrap gap-3">
            {Object.entries(docLinks).map(([slug, label]) => (
              <a key={slug} href={`/legal/${slug}`}
                className={`text-[11px] font-mono px-3 py-1.5 rounded-lg transition-all ${
                  slug === doc
                    ? 'bg-black text-white'
                    : 'bg-white border border-gray-200 text-gray-500 hover:border-gray-400 hover:text-black'
                }`}>
                {label}
              </a>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
