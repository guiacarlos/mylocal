import React from 'react';

const ALERGENOS_EU = [
    { id: 'gluten', nombre: 'Gluten' },
    { id: 'crustaceos', nombre: 'Crustaceos' },
    { id: 'huevos', nombre: 'Huevos' },
    { id: 'pescado', nombre: 'Pescado' },
    { id: 'cacahuetes', nombre: 'Cacahuetes' },
    { id: 'soja', nombre: 'Soja' },
    { id: 'lacteos', nombre: 'Lacteos' },
    { id: 'frutos_cascara', nombre: 'Frutos de cascara' },
    { id: 'apio', nombre: 'Apio' },
    { id: 'mostaza', nombre: 'Mostaza' },
    { id: 'sesamo', nombre: 'Sesamo' },
    { id: 'sulfitos', nombre: 'Sulfitos' },
    { id: 'altramuces', nombre: 'Altramuces' },
    { id: 'moluscos', nombre: 'Moluscos' }
];

export default function AlergensSelector({ selected = [], onChange }) {
    const toggle = (id) => {
        const next = selected.includes(id)
            ? selected.filter(a => a !== id)
            : [...selected, id];
        onChange(next);
    };

    return (
        <div className="alergens-selector">
            <label>Alergenos (14 EU)</label>
            <div className="alergens-grid">
                {ALERGENOS_EU.map(a => (
                    <label key={a.id} className="alergen-item">
                        <input
                            type="checkbox"
                            checked={selected.includes(a.id)}
                            onChange={() => toggle(a.id)}
                        />
                        <span>{a.nombre}</span>
                    </label>
                ))}
            </div>
        </div>
    );
}
