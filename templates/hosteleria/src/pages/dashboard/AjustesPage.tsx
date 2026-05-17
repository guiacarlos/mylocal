import { useState, useEffect, useRef } from 'react';
import { Loader2, Camera, Plus, X } from 'lucide-react';
import { useSynaxisClient } from '@mylocal/sdk';
import GoogleCalendarCard from '../../components/GoogleCalendarCard';

function getSession(k: string) { try { return sessionStorage.getItem(k) ?? ''; } catch { return ''; } }

type Dir = { calle?: string; numero?: string; ciudad?: string; cp?: string; provincia?: string };
type HorarioDia = { dia: string; cerrado: boolean; abre: string; cierra: string };
type LocalData = {
  id: string; nombre: string; descripcion: string; telefono: string;
  imagen_hero?: string; web_template?: string;
  direccion?: Dir | string;
  horario?: HorarioDia[];
  precio_medio?: string;
  tipo_cocina?: string[];
  acepta_reservas?: boolean;
  url_maps?: string;
};

const DIAS = ['Lu','Ma','Mi','Ju','Vi','Sa','Do'];
const DIA_LABEL: Record<string, string> = { Lu:'Lun',Ma:'Mar',Mi:'Mié',Ju:'Jue',Vi:'Vie',Sa:'Sáb',Do:'Dom' };
function initHorario(h?: HorarioDia[]): HorarioDia[] {
  return DIAS.map(d => h?.find(x => x.dia === d) ?? { dia: d, cerrado: false, abre: '12:00', cierra: '23:00' });
}

export default function AjustesPage() {
  const client  = useSynaxisClient();
  const localId = getSession('mylocal_localId');
  const logoRef = useRef<HTMLInputElement>(null);

  const [local,       setLocal]       = useState<LocalData | null>(null);
  const [loading,     setLoading]     = useState(true);
  const [saving,      setSaving]      = useState(false);
  const [uploading,   setUploading]   = useState(false);
  const [saved,       setSaved]       = useState(false);
  const [nombre,      setNombre]      = useState('');
  const [descripcion, setDescripcion] = useState('');
  const [telefono,    setTelefono]    = useState('');
  const [dir,         setDir]         = useState<Dir>({});
  const [horario,     setHorario]     = useState<HorarioDia[]>(initHorario());
  const [precioMedio, setPrecioMedio] = useState('');
  const [tipoCocina,  setTipoCocina]  = useState<string[]>([]);
  const [cocinaInput, setCocinaInput] = useState('');
  const [aceptaRes,   setAceptaRes]   = useState(false);
  const [urlMaps,     setUrlMaps]     = useState('');

  useEffect(() => {
    (async () => {
      setLoading(true);
      try {
        const r = await client.execute<LocalData>({ action: 'get_local', data: { id: localId } });
        if (r.success && r.data) {
          const d = r.data;
          setLocal(d);
          setNombre(d.nombre ?? '');
          setDescripcion(d.descripcion ?? '');
          setTelefono(d.telefono ?? '');
          setDir(typeof d.direccion === 'object' ? (d.direccion as Dir) : {});
          setHorario(initHorario(d.horario));
          setPrecioMedio(d.precio_medio ?? '');
          setTipoCocina(d.tipo_cocina ?? []);
          setAceptaRes(d.acepta_reservas ?? false);
          setUrlMaps(d.url_maps ?? '');
        }
      } catch { /* silenciar */ }
      setLoading(false);
    })();
  }, [client, localId]);

  async function handleSave() {
    setSaving(true); setSaved(false);
    try {
      const r = await client.execute<LocalData>({
        action: 'update_local',
        data: { id: localId, nombre, descripcion, telefono,
                direccion: dir, horario, precio_medio: precioMedio,
                tipo_cocina: tipoCocina, acepta_reservas: aceptaRes, url_maps: urlMaps },
      });
      if (r.success && r.data) { setLocal(r.data); setSaved(true); setTimeout(() => setSaved(false), 3000); }
    } catch { /* silenciar */ }
    setSaving(false);
  }

  async function handleLogoUpload(file: File) {
    setUploading(true);
    try {
      const fd = new FormData();
      fd.append('action', 'upload_local_image'); fd.append('local_id', localId); fd.append('file', file);
      const token = getSession('mylocal_token');
      const res  = await fetch('/acide/index.php', { method: 'POST', headers: token ? { Authorization: `Bearer ${token}` } : {}, body: fd });
      const json = await res.json() as { success: boolean; data?: { url?: string } };
      if (json.success && json.data?.url) setLocal(prev => prev ? { ...prev, imagen_hero: json.data!.url } : prev);
    } catch { /* silenciar */ }
    setUploading(false);
  }

  function addCocina() {
    const v = cocinaInput.trim();
    if (v && !tipoCocina.includes(v)) setTipoCocina(prev => [...prev, v]);
    setCocinaInput('');
  }
  function updHorario(idx: number, field: keyof HorarioDia, val: string | boolean) {
    setHorario(prev => prev.map((d, i) => i === idx ? { ...d, [field]: val } : d));
  }
  const inp = 'px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-black text-sm w-full';

  if (loading) return <div className="p-6 flex items-center gap-2 text-gray-400 text-sm"><Loader2 className="w-4 h-4 animate-spin" /> Cargando…</div>;

  return (
    <div className="p-6 lg:p-10 max-w-2xl">
      <div className="mb-8">
        <p className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-1">Ajustes</p>
        <h1 className="text-3xl font-display font-bold tracking-tighter">Ajustes del local</h1>
        <p className="text-[13px] text-gray-500 mt-1">Información SEO: aparece en Google, Maps y IA.</p>
      </div>

      <div className="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-50">

        {/* Logo */}
        <div className="p-5 flex items-center gap-4">
          <div className="relative w-14 h-14 rounded-xl bg-gray-50 border border-gray-100 overflow-hidden flex items-center justify-center flex-shrink-0">
            {local?.imagen_hero ? <img src={local.imagen_hero} alt="Logo" className="w-full h-full object-cover" /> : <Camera className="w-4 h-4 text-gray-300" />}
            {uploading && <div className="absolute inset-0 bg-white/70 flex items-center justify-center"><Loader2 className="w-3 h-3 animate-spin" /></div>}
          </div>
          <input ref={logoRef} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={e => { const f = e.target.files?.[0]; if (f) void handleLogoUpload(f); }} />
          <button onClick={() => logoRef.current?.click()} disabled={uploading} className="text-sm text-gray-500 hover:text-gray-800 border border-gray-200 px-4 py-2 rounded-xl disabled:opacity-40">
            {uploading ? 'Subiendo…' : 'Cambiar logo'}
          </button>
        </div>

        {/* Nombre + Descripción */}
        <div className="p-5 grid gap-4">
          <div>
            <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1.5">Nombre del local</label>
            <input value={nombre} onChange={e => setNombre(e.target.value)} placeholder="El Rincón de Ana" className={inp} />
          </div>
          <div>
            <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1.5">Descripción corta</label>
            <textarea value={descripcion} onChange={e => setDescripcion(e.target.value)} rows={2} placeholder="Bar de tapas en el centro…"
              className={`${inp} resize-none`} />
          </div>
        </div>

        {/* Teléfono + Maps */}
        <div className="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1.5">Teléfono</label>
            <input type="tel" value={telefono} onChange={e => setTelefono(e.target.value)} placeholder="+34 600 000 000" className={inp} />
          </div>
          <div>
            <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-1.5">Google Maps (URL)</label>
            <input type="url" value={urlMaps} onChange={e => setUrlMaps(e.target.value)} placeholder="https://maps.google.com/…" className={inp} />
          </div>
        </div>

        {/* Dirección estructurada */}
        <div className="p-5">
          <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-2.5">Dirección</label>
          <div className="grid grid-cols-[1fr_5rem] gap-2 mb-2">
            <input value={dir.calle ?? ''} onChange={e => setDir(p => ({ ...p, calle: e.target.value }))} placeholder="Calle Mayor" className={inp} />
            <input value={dir.numero ?? ''} onChange={e => setDir(p => ({ ...p, numero: e.target.value }))} placeholder="Nº" className={inp} />
          </div>
          <div className="grid grid-cols-[5rem_1fr] gap-2">
            <input value={dir.cp ?? ''} onChange={e => setDir(p => ({ ...p, cp: e.target.value }))} placeholder="CP" className={inp} />
            <input value={dir.ciudad ?? ''} onChange={e => setDir(p => ({ ...p, ciudad: e.target.value }))} placeholder="Ciudad" className={inp} />
          </div>
        </div>

        {/* Horario */}
        <div className="p-5">
          <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-2.5">Horario</label>
          <div className="flex flex-col gap-2">
            {horario.map((d, i) => (
              <div key={d.dia} className="flex items-center gap-2 text-sm">
                <span className="w-9 shrink-0 text-[11px] font-mono text-gray-400">{DIA_LABEL[d.dia]}</span>
                <input type="checkbox" checked={!d.cerrado} onChange={e => updHorario(i, 'cerrado', !e.target.checked)} className="rounded shrink-0" />
                {d.cerrado
                  ? <span className="text-[12px] text-gray-400 italic">Cerrado</span>
                  : <>
                      <input type="time" value={d.abre}   onChange={e => updHorario(i, 'abre',   e.target.value)} className="px-2 py-1 rounded-lg border border-gray-200 text-sm focus:outline-none focus:border-black w-[6.5rem] shrink-0" />
                      <span className="text-gray-400 shrink-0">–</span>
                      <input type="time" value={d.cierra} onChange={e => updHorario(i, 'cierra', e.target.value)} className="px-2 py-1 rounded-lg border border-gray-200 text-sm focus:outline-none focus:border-black w-[6.5rem] shrink-0" />
                    </>
                }
              </div>
            ))}
          </div>
        </div>

        {/* Precio + Reservas */}
        <div className="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-2">Precio medio</label>
            <div className="flex gap-2">
              {(['€','€€','€€€'] as const).map(p => (
                <button key={p} type="button" onClick={() => setPrecioMedio(precioMedio === p ? '' : p)}
                  className={`flex-1 py-2 rounded-xl border text-sm font-medium transition-all ${precioMedio === p ? 'border-black bg-black text-white' : 'border-gray-200 text-gray-600 hover:border-gray-300'}`}>
                  {p}
                </button>
              ))}
            </div>
          </div>
          <div className="flex items-center gap-3 pt-6">
            <input type="checkbox" id="acepta" checked={aceptaRes} onChange={e => setAceptaRes(e.target.checked)} className="w-4 h-4 rounded" />
            <label htmlFor="acepta" className="text-sm text-gray-700 cursor-pointer">Acepta reservas</label>
          </div>
        </div>

        {/* Tipo cocina */}
        <div className="p-5">
          <label className="block text-[11px] font-mono text-gray-400 uppercase tracking-widest mb-2">Tipo de cocina</label>
          {tipoCocina.length > 0 && (
            <div className="flex flex-wrap gap-1.5 mb-2">
              {tipoCocina.map(c => (
                <span key={c} className="flex items-center gap-1 px-3 py-1 bg-gray-100 rounded-full text-[12px]">
                  {c} <button onClick={() => setTipoCocina(prev => prev.filter(x => x !== c))}><X className="w-3 h-3" /></button>
                </span>
              ))}
            </div>
          )}
          <div className="flex gap-2">
            <input value={cocinaInput} onChange={e => setCocinaInput(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && (e.preventDefault(), addCocina())}
              placeholder="ej. Mediterránea" className="flex-1 px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-black" />
            <button onClick={addCocina} className="px-3 py-2 rounded-xl border border-gray-200 hover:border-gray-300"><Plus className="w-4 h-4 text-gray-500" /></button>
          </div>
        </div>

        {/* Guardar */}
        <div className="p-5 flex items-center justify-between">
          {saved && <span className="text-[13px] text-green-600">Cambios guardados</span>}
          <div className="ml-auto">
            <button onClick={() => void handleSave()} disabled={saving}
              className="flex items-center gap-2 bg-black text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-800 disabled:opacity-40">
              {saving && <Loader2 className="w-4 h-4 animate-spin" />}
              Guardar ajustes
            </button>
          </div>
        </div>
      </div>

      <GoogleCalendarCard />
    </div>
  );
}
