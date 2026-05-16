import { useState, useRef, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { cn } from '../lib/utils';

export interface WebTheme {
  bg:         string;
  text:       string;
  textMuted:  string;
  border:     string;
  accent:     string;
  accentText: string;
  card:       string;
}

export const THEMES: Record<'light' | 'dark', WebTheme> = {
  light: {
    bg: '#ffffff', text: '#0a0a0a', textMuted: '#6b7280',
    border: '#f3f4f6', accent: '#0a0a0a', accentText: '#ffffff', card: '#f9f9f7',
  },
  dark: {
    bg: '#0a0a0a', text: '#ffffff', textMuted: '#9ca3af',
    border: 'rgba(255,255,255,0.08)', accent: '#ffffff', accentText: '#0a0a0a',
    card: 'rgba(255,255,255,0.06)',
  },
};

const POSTS = [
  { image: 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&q=80&w=200', title: 'Nuestro equipo' },
  { image: 'https://images.unsplash.com/photo-1577219491135-ce391730fb2c?auto=format&fit=crop&q=80&w=200', title: 'El chef en acción' },
  { image: 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?auto=format&fit=crop&q=80&w=200', title: 'Cumpleaños especial' },
  { image: 'https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&q=80&w=200', title: 'Noche de gala' },
];

const REVIEWS = [
  {
    name: 'Ana García', date: 'Mar 2024', category: 'Ambiente', stars: 5, likes: 24,
    text: 'La decoración es impresionante y el ambiente es de diez. Un espacio donde cada detalle está cuidado al milímetro.',
    image: 'https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&q=80&w=600',
  },
  {
    name: 'Marco Rodríguez', date: 'Feb 2024', category: 'Cocina', stars: 5, likes: 18,
    text: 'El chef supera todas las expectativas. Cada plato es una obra de arte y los sabores son absolutamente únicos.',
    image: 'https://images.unsplash.com/photo-1577219491135-ce391730fb2c?auto=format&fit=crop&q=80&w=600',
  },
  {
    name: 'Sofía Martín', date: 'Ene 2024', category: 'Servicio', stars: 5, likes: 31,
    text: 'El personal es amabilísimo. Desde la reserva hasta el postre, todo fue perfecto y muy profesional.',
    image: 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&q=80&w=600',
  },
];

interface Props { theme: WebTheme; device: 'desktop' | 'tablet' | 'mobile'; }

const T = 'transition-colors duration-500';

export default function WebMockup({ theme, device }: Props) {
  const narrow    = device === 'mobile';
  const scrollRef = useRef<HTMLDivElement>(null);
  const heroRef   = useRef<HTMLDivElement>(null);
  const [heroVisible, setHeroVisible] = useState(true);
  const [reviewIdx, setReviewIdx]     = useState(0);

  useEffect(() => {
    const timer = setInterval(() => setReviewIdx(i => (i + 1) % REVIEWS.length), 5500);
    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    const root = scrollRef.current;
    const hero = heroRef.current;
    if (!root || !hero) return;
    const obs = new IntersectionObserver(
      ([e]) => setHeroVisible(e.isIntersecting),
      { root, threshold: 0.15 }
    );
    obs.observe(hero);
    return () => obs.disconnect();
  }, []);

  return (
    <div ref={scrollRef} className={cn('w-full h-full overflow-y-auto scrollbar-hide flex flex-col', T)}
      style={{ background: theme.bg, color: theme.text }}>

      {/* Sticky header */}
      <div className={cn('sticky top-0 z-50 flex items-center justify-between px-5 py-3 border-b flex-shrink-0', T)}
        style={{ background: theme.bg + 'f5', borderColor: theme.border, backdropFilter: 'blur(14px)', WebkitBackdropFilter: 'blur(14px)' }}>
        <span className="text-[11px] font-bold tracking-tight flex-shrink-0">Mi Local</span>
        {!narrow && (
          <div className="flex gap-5">
            {['Carta', 'Nosotros', 'Contacto'].map(n => (
              <span key={n} className={cn('text-[9px] font-medium', T)} style={{ color: theme.textMuted }}>{n}</span>
            ))}
          </div>
        )}
        <div className="w-[60px] flex justify-end flex-shrink-0">
          <AnimatePresence>
            {!heroVisible && (
              <motion.button
                initial={{ opacity: 0, scale: 0.8 }} animate={{ opacity: 1, scale: 1 }}
                exit={{ opacity: 0, scale: 0.8 }} transition={{ duration: 0.18 }}
                className={cn('text-[8px] px-3 py-1 rounded-full font-semibold whitespace-nowrap', T)}
                style={{ background: theme.accent, color: theme.accentText }}
              >
                Reservar
              </motion.button>
            )}
          </AnimatePresence>
        </div>
      </div>

      {/* Hero */}
      <div ref={heroRef} className="relative flex-shrink-0 h-[190px] flex items-center justify-center text-center overflow-hidden">
        <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=900"
          alt="hero" className="absolute inset-0 w-full h-full object-cover scale-105" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/10" />
        <div className="relative z-10 px-8 flex flex-col items-center">
          <p className="text-[8px] text-white/50 font-mono uppercase tracking-[0.25em] mb-2">Bienvenido</p>
          <h1 className="text-[22px] font-bold text-white leading-[1.1] mb-3 tracking-tight">
            Gastronomía<br />de Vanguardia
          </h1>
          <p className="text-[9px] text-white/65 mb-4 max-w-[220px] leading-relaxed">
            Una experiencia culinaria única en el corazón de la ciudad
          </p>
          <button className="text-[9px] px-5 py-1.5 bg-white text-black rounded-full font-semibold">
            Reservar mesa
          </button>
        </div>
      </div>

      {/* Nosotros */}
      <div className={cn('relative overflow-hidden flex-shrink-0 py-7 px-6', T)}>

        {/* Watermark centrado — marca de agua */}
        <span
          className="absolute inset-0 flex items-center justify-center font-bold tracking-tighter leading-none select-none pointer-events-none"
          style={{ fontSize: '42px', color: theme.text, opacity: 0.05, whiteSpace: 'nowrap' }}
        >
          NOSOTROS
        </span>

        {/* Contenido */}
        <div className="relative z-10 text-center">
          <h2 className="text-[14px] font-bold mb-3 leading-tight">Una cocina con alma</h2>
          <p className="text-[9px] leading-relaxed mx-auto max-w-[280px]" style={{ color: theme.textMuted }}>
            Desde 2010 combinamos técnica y pasión para crear platos que sorprenden. Ingredientes locales, recetas propias y el calor de casa en cada visita.
          </p>
        </div>
      </div>

      {/* Publicaciones */}
      <div className={cn('relative py-7 flex-shrink-0 overflow-hidden', T)}>

        {/* Watermark SVG — texto estirado al ancho completo de la sección */}
        <svg className="absolute inset-0 w-full h-full pointer-events-none select-none" viewBox="0 0 600 140" preserveAspectRatio="none">
          <text x="0" y="105" fontWeight="800" fontSize="88" letterSpacing="-2"
            textLength="600" lengthAdjust="spacing"
            fill={theme.text} fillOpacity="0.05"
            style={{ fontFamily: 'inherit', userSelect: 'none' }}>
            PUBLICACIONES
          </text>
        </svg>

        <div className="relative z-10 flex gap-3 overflow-x-auto px-5 pb-1 scrollbar-hide justify-center">
          {POSTS.map(post => (
            <div key={post.title} className="flex-shrink-0 w-[100px] rounded-xl overflow-hidden shadow-sm text-center">
              <img src={post.image} alt={post.title} className="w-full h-[72px] object-cover" />
              <p className="text-[7px] font-medium px-1.5 py-1.5 leading-tight" style={{ color: theme.text }}>{post.title}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Reseñas — dos columnas: watermark izq 30% + Netflix der 70% */}
      <div className="relative flex-shrink-0 overflow-hidden flex" style={{ height: '165px' }}>

        {/* Bordes difuminados suaves */}
        <div className="absolute inset-x-0 top-0 h-4 z-20 pointer-events-none"
          style={{ background: `linear-gradient(to bottom, ${theme.bg}, transparent)` }} />
        <div className="absolute inset-x-0 bottom-0 h-4 z-20 pointer-events-none"
          style={{ background: `linear-gradient(to top, ${theme.bg}, transparent)` }} />
        <div className="absolute inset-y-0 left-0 w-2 z-20 pointer-events-none"
          style={{ background: `linear-gradient(to right, ${theme.bg}, transparent)` }} />
        <div className="absolute inset-y-0 right-0 w-3 z-20 pointer-events-none"
          style={{ background: `linear-gradient(to left, ${theme.bg}66, transparent)` }} />

        {/* Izquierda 30% — watermark tres líneas */}
        <div className="relative w-[30%] flex-shrink-0 overflow-hidden" style={{ background: theme.bg }}>
          <svg className="absolute inset-0 w-full h-full pointer-events-none select-none" viewBox="0 0 180 165" preserveAspectRatio="none">
            <text x="0" y="38" fontWeight="800" fontSize="27" textLength="180" lengthAdjust="spacing"
              fill={theme.text} fillOpacity="0.07" style={{ fontFamily: 'inherit' }}>LO QUE</text>
            <text x="0" y="80" fontWeight="800" fontSize="27" textLength="180" lengthAdjust="spacing"
              fill={theme.text} fillOpacity="0.07" style={{ fontFamily: 'inherit' }}>DICEN</text>
            <text x="0" y="120" fontWeight="800" fontSize="27" textLength="180" lengthAdjust="spacing"
              fill={theme.text} fillOpacity="0.07" style={{ fontFamily: 'inherit' }}>NUESTROS</text>
            <text x="0" y="158" fontWeight="800" fontSize="27" textLength="180" lengthAdjust="spacing"
              fill={theme.text} fillOpacity="0.07" style={{ fontFamily: 'inherit' }}>CLIENTES</text>
          </svg>
          {/* Fade hacia la derecha */}
          <div className="absolute inset-y-0 right-0 w-8 pointer-events-none"
            style={{ background: `linear-gradient(to right, transparent, ${theme.bg})` }} />
        </div>

        {/* Derecha 70% — tarjeta Netflix */}
        <div className="relative flex-1 overflow-hidden">
          <AnimatePresence mode="wait">
            <motion.div
              key={reviewIdx}
              className="absolute inset-0"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 1.1, ease: 'easeInOut' }}
            >
              <img src={REVIEWS[reviewIdx].image} alt="" className="absolute inset-0 w-full h-full object-cover" />
              {/* Overlay + fade izquierdo para fundir con la columna */}
              <div className="absolute inset-0 bg-gradient-to-r from-black/95 via-black/70 to-black/30" />
              {/* Fade superior e inferior suaves */}
              <div className="absolute inset-x-0 top-0 h-6 bg-gradient-to-b from-black/40 to-transparent" />
              <div className="absolute inset-x-0 bottom-0 h-6 bg-gradient-to-t from-black/40 to-transparent" />

              <div className="absolute inset-0 flex flex-col justify-between px-4 py-4">
                <div className="flex items-center gap-2">
                  <span className="text-[7px] font-semibold bg-white/15 text-white px-2 py-0.5 rounded-full">
                    {REVIEWS[reviewIdx].category}
                  </span>
                  <span className="text-[6.5px] text-white/45">{REVIEWS[reviewIdx].date}</span>
                </div>
                <div className="max-w-[75%]">
                  <p className="text-[11px] font-bold text-white mb-1 leading-tight">{REVIEWS[reviewIdx].name}</p>
                  <p className="text-[7.5px] text-white/70 leading-relaxed line-clamp-2">{REVIEWS[reviewIdx].text}</p>
                </div>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <span className="text-[9px] text-amber-400">{'★'.repeat(REVIEWS[reviewIdx].stars)}</span>
                    <span className="text-[6.5px] text-white/45">👍 {REVIEWS[reviewIdx].likes}</span>
                  </div>
                  <div className="flex gap-1 mr-1">
                    {REVIEWS.map((_, i) => (
                      <div key={i} className="h-[2px] rounded-full transition-all duration-700"
                        style={{ width: i === reviewIdx ? '18px' : '5px', background: i === reviewIdx ? '#fff' : 'rgba(255,255,255,0.28)' }} />
                    ))}
                  </div>
                </div>
              </div>
            </motion.div>
          </AnimatePresence>
        </div>
      </div>

      {/* Contacto */}
      <div className={cn('relative overflow-hidden flex-shrink-0 px-6 py-8 text-center', T)}>
        {/* Watermark "CONTACTO" de fondo */}
        <svg className="absolute inset-0 w-full h-full pointer-events-none select-none" viewBox="0 0 600 130" preserveAspectRatio="none">
          <text x="0" y="100" fontWeight="800" fontSize="88" textLength="600" lengthAdjust="spacing"
            fill={theme.text} fillOpacity="0.04" style={{ fontFamily: 'inherit', userSelect: 'none' }}>
            CONTACTO
          </text>
        </svg>

        {/* Contenido */}
        <div className="relative z-10 flex flex-col items-center gap-3">
          <div className="space-y-1.5">
            {['Calle Mayor 12, Madrid', '+34 910 000 000', 'hola@milocal.es'].map(line => (
              <p key={line} className="text-[8px]" style={{ color: theme.textMuted }}>{line}</p>
            ))}
          </div>
          <div className="flex gap-3 pt-1">
            {/* Instagram */}
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ color: theme.textMuted }}>
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
            </svg>
            {/* Facebook */}
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ color: theme.textMuted }}>
              <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
            </svg>
          </div>
        </div>
      </div>

      {/* Footer */}
      <div className={cn('px-6 py-5 text-center mt-auto flex-shrink-0', T)}>
        <p className="text-[9px] font-bold mb-1.5">Mi Local</p>
        <p className="text-[7px]" style={{ color: theme.textMuted }}>
          © 2024 Mi Local · Cookies · Privacidad · Aviso Legal
        </p>
      </div>
    </div>
  );
}
