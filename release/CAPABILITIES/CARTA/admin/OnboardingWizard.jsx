import React, { useState } from 'react';
import { ChevronRight, ChevronLeft, Check, QrCode, ExternalLink } from 'lucide-react';

const EP = '/axidb/api/axi.php';

function api(action, data = {}) {
    return fetch(EP, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action, data })
    }).then(r => r.json());
}

const TOTAL_STEPS = 10;

export default function OnboardingWizard() {
    const [step, setStep] = useState(1);
    const [data, setData] = useState({
        nombre: '', email: '', password: '', local_nombre: '', slug: '',
        logo_url: '', categoria_nombre: '', producto_nombre: '', producto_precio: '',
        zona_nombre: 'Terraza', mesa_numero: '1', mesa_capacidad: '4'
    });
    const [localId, setLocalId] = useState(null);
    const [catId, setCatId] = useState(null);
    const [qrUrl, setQrUrl] = useState('');
    const [error, setError] = useState('');

    const set = (k, v) => setData({ ...data, [k]: v });
    const next = () => { setError(''); setStep(s => Math.min(s + 1, TOTAL_STEPS)); };
    const prev = () => { setError(''); setStep(s => Math.max(s - 1, 1)); };

    const generateSlug = (nombre) => {
        return nombre.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    };

    const handleCreateLocal = async () => {
        if (!data.local_nombre) { setError('Nombre del local obligatorio'); return; }
        const slug = data.slug || generateSlug(data.local_nombre);
        const res = await api('create_local', { nombre: data.local_nombre, slug });
        if (res.success) { setLocalId(slug); set('slug', slug); next(); }
        else setError(res.error || 'Error al crear local');
    };

    const handleCreateCategoria = async () => {
        if (!data.categoria_nombre) { setError('Nombre de categoria obligatorio'); return; }
        const res = await api('create_categoria', { local_id: localId, nombre: data.categoria_nombre });
        if (res.success) { setCatId(res.data?.id || res.id); next(); }
        else setError(res.error || 'Error al crear categoria');
    };

    const handleCreateProducto = async () => {
        if (!data.producto_nombre || !data.producto_precio) { setError('Nombre y precio obligatorios'); return; }
        const res = await api('create_producto', {
            local_id: localId, categoria_id: catId,
            nombre: data.producto_nombre, precio: parseFloat(data.producto_precio)
        });
        if (res.success) next();
        else setError(res.error || 'Error al crear producto');
    };

    const handleCreateMesa = async () => {
        const res = await api('create_mesa', {
            local_id: localId, zona_nombre: data.zona_nombre,
            numero: parseInt(data.mesa_numero), capacidad: parseInt(data.mesa_capacidad)
        });
        if (res.success) next();
        else setError(res.error || 'Error al crear mesa');
    };

    const handleGenerateQR = async () => {
        const res = await api('generate_qr_image', {
            local_slug: localId, zona: data.zona_nombre, numero: parseInt(data.mesa_numero)
        });
        if (res.success) { setQrUrl(res.data); next(); }
        else next();
    };

    const progress = Math.round((step / TOTAL_STEPS) * 100);

    return (
        <div className="onboarding-wizard">
            <div className="wizard-progress">
                <div className="wizard-bar" style={{ width: progress + '%' }} />
                <span>Paso {step} de {TOTAL_STEPS}</span>
            </div>

            {error && <div className="wizard-error">{error}</div>}

            <div className="wizard-step">
                {step === 1 && (
                    <div>
                        <h2>Registro</h2>
                        <label>Nombre</label>
                        <input value={data.nombre} onChange={e => set('nombre', e.target.value)} />
                        <label>Email</label>
                        <input type="email" value={data.email} onChange={e => set('email', e.target.value)} />
                        <label>Contrasena</label>
                        <input type="password" value={data.password} onChange={e => set('password', e.target.value)} />
                        <label>Nombre del local</label>
                        <input value={data.local_nombre} onChange={e => set('local_nombre', e.target.value)} />
                        <button className="btn-primary" onClick={handleCreateLocal}>Continuar <ChevronRight size={16} /></button>
                    </div>
                )}
                {step === 2 && (
                    <div>
                        <h2>URL de tu carta</h2>
                        <p>Tu carta sera accesible en:</p>
                        <code>/carta/{data.slug || generateSlug(data.local_nombre)}</code>
                        <label>Slug (editable)</label>
                        <input value={data.slug} onChange={e => set('slug', e.target.value)} />
                        <button className="btn-primary" onClick={next}>Continuar <ChevronRight size={16} /></button>
                    </div>
                )}
                {step === 3 && (
                    <div>
                        <h2>Logo del local (opcional)</h2>
                        <p>Puedes subir el logo ahora o hacerlo mas tarde desde el panel.</p>
                        <button className="btn-secondary" onClick={next}>Saltar</button>
                    </div>
                )}
                {step === 4 && (
                    <div>
                        <h2>Primera categoria</h2>
                        <label>Nombre de la categoria</label>
                        <input value={data.categoria_nombre} placeholder="Entrantes, Bebidas, Postres..."
                            onChange={e => set('categoria_nombre', e.target.value)} />
                        <button className="btn-primary" onClick={handleCreateCategoria}>Crear categoria <ChevronRight size={16} /></button>
                    </div>
                )}
                {step === 5 && (
                    <div>
                        <h2>Primer producto</h2>
                        <label>Nombre</label>
                        <input value={data.producto_nombre} onChange={e => set('producto_nombre', e.target.value)} />
                        <label>Precio (EUR)</label>
                        <input type="number" step="0.01" value={data.producto_precio}
                            onChange={e => set('producto_precio', e.target.value)} />
                        <button className="btn-primary" onClick={handleCreateProducto}>Crear producto <ChevronRight size={16} /></button>
                    </div>
                )}
                {step === 6 && (
                    <div>
                        <h2>Vista previa</h2>
                        <p>Tu carta ya esta disponible. Abrela en el movil para verla tal como la veran tus clientes.</p>
                        <a href={'/carta/' + localId} target="_blank" rel="noopener noreferrer" className="btn-primary">
                            Ver carta <ExternalLink size={16} />
                        </a>
                        <button className="btn-secondary" onClick={next}>Continuar</button>
                    </div>
                )}
                {step === 7 && (
                    <div>
                        <h2>Configurar mesas</h2>
                        <label>Zona</label>
                        <input value={data.zona_nombre} onChange={e => set('zona_nombre', e.target.value)} />
                        <label>Numero de mesa</label>
                        <input type="number" min="1" value={data.mesa_numero}
                            onChange={e => set('mesa_numero', e.target.value)} />
                        <label>Capacidad</label>
                        <input type="number" min="1" value={data.mesa_capacidad}
                            onChange={e => set('mesa_capacidad', e.target.value)} />
                        <button className="btn-primary" onClick={handleCreateMesa}>Crear mesa <ChevronRight size={16} /></button>
                    </div>
                )}
                {step === 8 && (
                    <div>
                        <h2>Descargar QR</h2>
                        <p>Genera el QR de tu primera mesa para imprimir y colocar.</p>
                        <button className="btn-primary" onClick={handleGenerateQR}>
                            <QrCode size={16} /> Generar QR
                        </button>
                    </div>
                )}
                {step === 9 && (
                    <div>
                        <h2>Escanea tu QR</h2>
                        {qrUrl?.base64 && <img src={'data:' + qrUrl.mime + ';base64,' + qrUrl.base64} alt="QR" style={{maxWidth: 200}} />}
                        <p>Escanea el QR con tu movil para comprobar que la carta se ve correctamente.</p>
                        <button className="btn-primary" onClick={next}>Listo, funciona <Check size={16} /></button>
                    </div>
                )}
                {step === 10 && (
                    <div>
                        <h2>Tu carta esta activa</h2>
                        <p>Enlace directo: <strong>/carta/{localId}</strong></p>
                        <p>Comparte este enlace con tus clientes o imprime los QR de tus mesas.</p>
                        <a href="https://wa.me/34611677577?text=Hola%2C%20necesito%20ayuda%20con%20MyLocal"
                            target="_blank" rel="noopener noreferrer" className="btn-secondary">
                            Soporte WhatsApp
                        </a>
                        <a href="/dashboard" className="btn-primary">Ir al panel de gestion</a>
                    </div>
                )}
            </div>

            {step > 1 && step < TOTAL_STEPS && (
                <button className="wizard-back" onClick={prev}>
                    <ChevronLeft size={16} /> Atras
                </button>
            )}
        </div>
    );
}
