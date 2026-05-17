import { useState, useEffect } from 'react';
import { FileText, RefreshCw, Loader2, ExternalLink } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

type DocMeta = { slug_doc: string; titulo: string; updated_at: string };

function getSession(k: string) { try { return sessionStorage.getItem(k) ?? ''; } catch { return ''; } }

const DOC_SLUGS: Record<string, string> = {
  privacidad: '/legal/privacidad',
  aviso:      '/legal/aviso',
  cookies:    '/legal/cookies',
};

export default function LegalesPage() {
  const client  = useSynaxisClient();
  const localId = getSession('mylocal_localId');

  const [docs,        setDocs]        = useState<DocMeta[]>([]);
  const [loading,     setLoading]     = useState(true);
  const [regenerating,setRegenerating] = useState(false);
  const [done,        setDone]        = useState(false);

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const r = await client.execute<{ items: DocMeta[] }>({
          action: 'list_legales',
          data:   { local_id: localId },
        });
        if (r.success && r.data) setDocs(r.data.items ?? []);
      } catch { /* silenciar */ }
      setLoading(false);
    })();
  }, [client, localId]);

  async function regenerar() {
    setRegenerating(true);
    setDone(false);
    try {
      await client.execute({ action: 'regenerate_legales', data: { local_id: localId } });
      // Recarga la lista
      const r = await client.execute<{ items: DocMeta[] }>({
        action: 'list_legales',
        data:   { local_id: localId },
      });
      if (r.success && r.data) setDocs(r.data.items ?? []);
      setDone(true);
      setTimeout(() => setDone(false), 3000);
    } catch { /* silenciar */ }
    setRegenerating(false);
  }

  function formatDate(iso: string) {
    try { return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }); }
    catch { return iso; }
  }

  return (
    <div className="p-6 lg:p-10 max-w-2xl">
      <div className="mb-8">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Cumplimiento</p>
        <h1 className="text-3xl font-display font-bold tracking-tighter">Documentos Legales</h1>
        <p className="text-[13px] text-gray-500 mt-1">
          Política de Privacidad, Aviso Legal y Cookies — generados automáticamente según los datos de tu local.
        </p>
      </div>

      {/* Acción: regenerar */}
      <div className="bg-white rounded-2xl border border-gray-100 p-5 mb-6 flex items-center gap-4">
        <FileText className="w-4 h-4 text-gray-400 flex-shrink-0" />
        <div className="flex-1 min-w-0">
          <p className="text-[13px] font-medium text-gray-800">Actualizar documentos</p>
          <p className="text-[12px] text-gray-500">
            Si cambiaste el nombre, dirección o email del local, regenera los documentos para que reflejen los datos actuales.
          </p>
        </div>
        <button onClick={() => void regenerar()} disabled={regenerating}
          className="flex-shrink-0 flex items-center gap-1.5 px-4 py-2 bg-black text-white rounded-xl text-[12px] font-medium hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-40">
          {regenerating
            ? <Loader2 className="w-3 h-3 animate-spin" />
            : <RefreshCw className="w-3 h-3" />}
          {done ? 'Actualizados' : 'Regenerar'}
        </button>
      </div>

      {/* Lista de documentos */}
      {loading ? (
        <div className="flex items-center gap-2 text-gray-400 text-sm">
          <Loader2 className="w-4 h-4 animate-spin" />Cargando…
        </div>
      ) : docs.length === 0 ? (
        <div className="bg-white rounded-2xl border border-gray-100 p-8 text-center">
          <FileText className="w-6 h-6 text-gray-200 mx-auto mb-2" />
          <p className="text-sm text-gray-400">Los documentos se generan al completar el registro.</p>
          <button onClick={() => void regenerar()} disabled={regenerating}
            className="mt-4 px-4 py-2 bg-black text-white rounded-xl text-[12px] font-medium disabled:opacity-40">
            Generar ahora
          </button>
        </div>
      ) : (
        <div className="flex flex-col gap-3">
          {docs.map(doc => (
            <div key={doc.slug_doc} className="bg-white rounded-2xl border border-gray-100 p-5 flex items-center gap-4">
              <FileText className="w-4 h-4 text-gray-400 flex-shrink-0" />
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-800">{doc.titulo}</p>
                <p className="text-[10px] font-mono text-gray-400 mt-0.5">
                  Actualizado: {formatDate(doc.updated_at)}
                </p>
              </div>
              <a href={DOC_SLUGS[doc.slug_doc] ?? `/legal/${doc.slug_doc}`} target="_blank" rel="noreferrer"
                className="flex-shrink-0 flex items-center gap-1 text-[12px] text-gray-500 hover:text-black transition-colors">
                <ExternalLink className="w-3 h-3" />
                Ver
              </a>
            </div>
          ))}
        </div>
      )}

      <p className="text-[11px] text-gray-400 mt-6 leading-relaxed">
        Estos documentos cumplen con el RGPD (UE) 2016/679 y la LSSI-CE. Revísalos antes del lanzamiento
        y adapta los datos fiscales si es necesario.
      </p>
    </div>
  );
}
