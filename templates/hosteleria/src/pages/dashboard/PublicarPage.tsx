import { useState, useEffect, useRef } from 'react';
import { Plus, Trash2, Loader2, Image, Upload } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';

type Post = { id: string; tipo: string; titulo: string; descripcion: string; media_url: string; publicado_at: string };

function getSession(k: string) { try { return sessionStorage.getItem(k) ?? ''; } catch { return ''; } }

export default function PublicarPage() {
  const client  = useSynaxisClient();
  const localId = getSession('mylocal_localId');
  const token   = getSession('mylocal_token');
  const fileRef = useRef<HTMLInputElement>(null);

  const [posts,       setPosts]       = useState<Post[]>([]);
  const [loading,     setLoading]     = useState(true);
  const [titulo,      setTitulo]      = useState('');
  const [descripcion, setDescripcion] = useState('');
  const [mediaUrl,    setMediaUrl]    = useState('');
  const [mediaPreview,setMediaPreview]= useState('');
  const [uploading,   setUploading]   = useState(false);
  const [publishing,  setPublishing]  = useState(false);
  const [deleting,    setDeleting]    = useState<string | null>(null);

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const r = await client.execute<{ items: Post[] }>({ action: 'list_posts', data: { local_id: localId } });
        if (r.success && r.data) setPosts(r.data.items ?? []);
      } catch { /* silenciar */ }
      setLoading(false);
    })();
  }, [client, localId]);

  async function uploadMedia(file: File) {
    setUploading(true);
    try {
      const fd = new FormData();
      fd.append('action', 'upload_timeline_media');
      fd.append('local_id', localId);
      fd.append('file', file);
      const res  = await fetch('/acide/index.php', {
        method: 'POST',
        headers: token ? { Authorization: `Bearer ${token}` } : {},
        body: fd,
      });
      const json = await res.json() as { success: boolean; data?: { media_url?: string } };
      if (json.success && json.data?.media_url) {
        setMediaUrl(json.data.media_url);
        setMediaPreview(URL.createObjectURL(file));
      }
    } catch { /* silenciar */ }
    setUploading(false);
  }

  async function handlePublicar() {
    if (!titulo.trim()) return;
    setPublishing(true);
    try {
      const r = await client.execute<Post>({
        action: 'create_post',
        data: {
          local_id:    localId,
          tipo:        mediaUrl ? 'foto' : 'texto',
          titulo:      titulo.trim(),
          descripcion: descripcion.trim(),
          media_url:   mediaUrl,
        },
      });
      if (r.success && r.data) {
        setPosts(prev => [r.data!, ...prev]);
        setTitulo(''); setDescripcion(''); setMediaUrl(''); setMediaPreview('');
      }
    } catch { /* silenciar */ }
    setPublishing(false);
  }

  async function deletePost(id: string) {
    setDeleting(id);
    try {
      await client.execute({ action: 'delete_post', data: { id } });
      setPosts(prev => prev.filter(p => p.id !== id));
    } catch { /* silenciar */ }
    setDeleting(null);
  }

  function formatDate(iso: string) {
    try { return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }); }
    catch { return iso; }
  }

  return (
    <div className="p-6 lg:p-10 max-w-2xl">
      <div className="mb-8">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Publicar</p>
        <h1 className="text-3xl font-display font-bold tracking-tighter">Local Vivo</h1>
        <p className="text-[13px] text-gray-500 mt-1">
          Publica fotos y novedades. Aparecen en tu carta pública.
        </p>
      </div>

      {/* Form */}
      <div className="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-4">Nueva publicación</p>

        <div className="mb-4">
          {mediaPreview ? (
            <div className="relative rounded-xl overflow-hidden h-40 bg-gray-50">
              <img src={mediaPreview} alt="Preview" className="w-full h-full object-cover" />
              <button onClick={() => { setMediaUrl(''); setMediaPreview(''); }}
                className="absolute top-2 right-2 bg-white/90 rounded-lg p-1.5 text-gray-500 hover:text-red-500 transition-colors">
                <Trash2 className="w-3.5 h-3.5" />
              </button>
            </div>
          ) : (
            <button onClick={() => fileRef.current?.click()} disabled={uploading}
              className="w-full h-32 rounded-xl border-2 border-dashed border-gray-200 hover:border-gray-300 flex flex-col items-center justify-center gap-2 text-gray-400 hover:text-gray-600 transition-all disabled:opacity-40">
              {uploading ? <Loader2 className="w-5 h-5 animate-spin" /> : <Image className="w-5 h-5" />}
              <span className="text-[12px]">{uploading ? 'Subiendo…' : 'Añadir foto (opcional)'}</span>
            </button>
          )}
          <input ref={fileRef} type="file" accept="image/jpeg,image/png,image/webp" className="hidden"
            onChange={e => { const f = e.target.files?.[0]; if (f) void uploadMedia(f); }} />
        </div>

        <input value={titulo} onChange={e => setTitulo(e.target.value)} required
          placeholder="Título de la publicación"
          className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm mb-3" />

        <textarea value={descripcion} onChange={e => setDescripcion(e.target.value)} rows={2}
          placeholder="Descripción (opcional)"
          className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black text-sm resize-none mb-4" />

        <button onClick={() => void handlePublicar()} disabled={publishing || !titulo.trim()}
          className="flex items-center gap-2 px-5 py-2.5 bg-black text-white rounded-xl text-sm font-medium hover:bg-gray-800 transition-all active:scale-95 disabled:opacity-40">
          {publishing ? <Loader2 className="w-4 h-4 animate-spin" /> : <Upload className="w-4 h-4" />}
          {publishing ? 'Publicando…' : 'Publicar'}
        </button>
      </div>

      {/* Lista */}
      {loading ? (
        <div className="flex items-center gap-2 text-gray-400 text-sm"><Loader2 className="w-4 h-4 animate-spin" />Cargando…</div>
      ) : posts.length === 0 ? (
        <div className="bg-white rounded-2xl border border-gray-100 p-8 text-center">
          <Plus className="w-6 h-6 text-gray-200 mx-auto mb-2" />
          <p className="text-sm text-gray-400">Nada publicado aún.</p>
        </div>
      ) : (
        <div className="flex flex-col gap-3">
          {posts.map(post => (
            <div key={post.id} className="bg-white rounded-2xl border border-gray-100 overflow-hidden">
              {post.media_url && <img src={post.media_url} alt={post.titulo} className="w-full h-32 object-cover" />}
              <div className="flex items-start gap-3 p-4">
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-800">{post.titulo}</p>
                  {post.descripcion && <p className="text-[12px] text-gray-500 mt-0.5">{post.descripcion}</p>}
                  <p className="text-[10px] font-mono text-gray-400 mt-1">{formatDate(post.publicado_at)}</p>
                </div>
                <button onClick={() => deletePost(post.id)} disabled={deleting === post.id}
                  className="flex-shrink-0 p-1.5 text-gray-300 hover:text-red-500 transition-colors">
                  {deleting === post.id ? <Loader2 className="w-4 h-4 animate-spin" /> : <Trash2 className="w-4 h-4" />}
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
