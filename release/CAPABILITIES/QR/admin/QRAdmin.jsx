import React, { useState, useEffect } from 'react';
import {
    QrCode,
    Printer,
    Table as TableIcon,
    ExternalLink,
    RefreshCw
} from 'lucide-react';
import { acideService } from '@/acide/acideService';
import './qr_admin.css';

/**
 * 📱 QR ADMIN - GESTIÓN SOBERANA DE PEDIDOS POR MESA
 * Administración de la capability QR
 */
export default function QRAdmin() {
    const [qrItems, setQrItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [isGenerating, setIsGenerating] = useState(false);

    useEffect(() => {
        loadQRData();
    }, []);

    const loadQRData = async () => {
        setLoading(true);
        try {
            const res = await acideService.call('generate_qr_list');
            if (res.success) {
                setQrItems(res.data.items || []);
            }
        } catch (err) {
            console.error("Error cargando QRs:", err);
        } finally {
            setLoading(false);
        }
    };

    const handlePrint = () => {
        window.print();
    };

    if (loading) return <div className="p-xl text-center">Sincronizando con el generador de QRs...</div>;

    return (
        <div className="tpv_admin_container animate-reveal">
            <header className="tpv_admin_header">
                <div>
                    <h1 className="tpv_admin_title">Gestión de Códigos QR</h1>
                    <p className="tpv_admin_subtitle">Códigos únicos para pedidos automáticos desde la mesa.</p>
                </div>
                <div style={{ display: 'flex', gap: '10px' }}>
                    <button onClick={loadQRData} className="tpv_tab_btn">
                        <RefreshCw size={16} /> Refrescar
                    </button>
                    <button onClick={handlePrint} className="tpv_save_btn" style={{ background: '#007aff' }}>
                        <Printer size={18} /> Imprimir Etiquetas
                    </button>
                </div>
            </header>

            <section className="tpv_admin_card">
                <div className="tpv_card_title_area">
                    <QrCode size={20} color="#007aff" />
                    <h2 className="tpv_card_title">Listado de Mesas Configurado</h2>
                </div>
                <p className="tpv_admin_subtitle" style={{ marginBottom: '2rem' }}>
                    Cada código al ser escaneado llevará al cliente a la carta filtrada por su mesa.
                </p>

                <div className="tpv_qr_print_zone">
                    <div className="tpv_qr_grid">
                        {qrItems.map((qr, idx) => (
                            <div
                                key={idx}
                                className="tpv_qr_label_card clickable-qr"
                                onClick={() => window.open(qr.url, '_blank')}
                                title={`Probar enlace: ${qr.url}`}
                            >
                                <div className="tpv_qr_label_header">
                                    <span className="tpv_qr_label_brand">Socolá</span>
                                    <div className="tpv_qr_label_location">
                                        <span className="tpv_qr_label_zone">{qr.zone_name}</span>
                                        <span className="tpv_qr_label_table">MESA {qr.table_number}</span>
                                    </div>
                                </div>
                                <div className="tpv_qr_code_real">
                                    {/* 🖼️ QR REAL Y FUNCIONAL: Generado mediante API para total soberanía */}
                                    <img
                                        src={`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qr.url)}&bgcolor=ffffff&color=202124&margin=10&format=png`}
                                        alt={`QR Mesa ${qr.table_number}`}
                                        className="tpv_qr_image"
                                    />
                                </div>
                                <div className="tpv_qr_label_footer">
                                    ESCANEAME Y PIDE
                                    <div className="tpv_qr_url_tiny">{qr.url}</div>
                                </div>
                            </div>
                        ))}
                        {qrItems.length === 0 && (
                            <div className="p-xl text-center" style={{ gridColumn: '1 / -1', color: '#94a3b8' }}>
                                <TableIcon size={48} style={{ marginBottom: '1rem', opacity: 0.2 }} />
                                <p>No hay mesas configuradas. Crea el plano en "Distribución Mesas".</p>
                            </div>
                        )}
                    </div>
                </div>
            </section>

            <style>{`
                @media print {
                    .sidebar-compact, .tpv_admin_header, .sidebar-nav, .tpv_tabs, .sidebar-footer, .tpv_admin_subtitle { display: none !important; }
                    .tpv_admin_container { padding: 0 !important; max-width: none !important; }
                    .tpv_admin_card { border: none !important; box-shadow: none !important; padding: 0 !important; }
                    .tpv_qr_grid { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 20px !important; }
                    .tpv_qr_label_card { break-inside: avoid; border: 1px dashed #ccc !important; padding: 15px !important; }
                    .sidebar-compact { width: 0 !important; }
                }
            `}</style>
        </div>
    );
}
