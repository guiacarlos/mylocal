import type { ComponentType } from 'react';

import { AgendaPage }        from './pages/AgendaPage';
import { PacientesPage }     from './pages/PacientesPage';
import { HistorialPage }     from './pages/HistorialPage';
import { StockPage }         from './pages/StockPage';
import { RecordatoriosPage } from './pages/RecordatoriosPage';

import './clinica.css';

import manifestData from './manifest.json';
import { validateManifest } from '../../app/config';

export const COMPONENTS: Record<string, ComponentType> = {
    AgendaPage,
    PacientesPage,
    HistorialPage,
    StockPage,
    RecordatoriosPage,
};

export const manifest = validateManifest(manifestData);
