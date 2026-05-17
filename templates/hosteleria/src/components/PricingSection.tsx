import { motion } from 'motion/react';
import { Check, ArrowRight } from 'lucide-react';
import { cn } from '../lib/utils';

const plans = [
  {
    key:      'demo',
    name:     'Demo',
    price:    '0€',
    plus:     '21 días gratis',
    desc:     'Prueba todo el producto sin compromiso. Sin tarjeta.',
    features: [
      'Carta digital QR',
      'Hasta 20 platos',
      'Timeline y reseñas',
      'Subdominio propio',
      'Soporte por email',
    ],
    primary:  false,
    cta:      'Empezar gratis',
    href:     '/registro',
  },
  {
    key:      'pro_monthly',
    name:     'Pro mensual',
    price:    '27€',
    plus:     '+ IVA / mes',
    desc:     'Para el hostelero que ya sabe que funciona.',
    features: [
      'Todo lo del Demo',
      'Platos ilimitados',
      'Zonas y mesas ilimitadas',
      'Copiloto IA de carta',
      'Legales automáticos RGPD',
      'Soporte prioritario',
    ],
    primary:  true,
    cta:      'Activar Pro mensual',
    href:     '/registro',
  },
  {
    key:      'pro_annual',
    name:     'Pro anual',
    price:    '260€',
    plus:     '+ IVA / año',
    desc:     'Dos meses gratis respecto al mensual. Precio bloqueado.',
    features: [
      'Todo lo del Pro mensual',
      'Ahorro del 20% (2 meses gratis)',
      'Precio bloqueado durante 1 año',
      'Acceso prioritario al roadmap',
    ],
    primary:  false,
    cta:      'Activar Pro anual',
    href:     '/registro',
  },
];

export default function PricingSection() {
  return (
    <section id="planes" className="min-h-screen lg:h-screen pt-16 flex items-center bg-white overflow-hidden">
      <div className="w-full max-w-7xl mx-auto px-6 py-10 lg:py-8">

        <div className="text-center mb-8">
          <span className="text-[11px] font-mono text-gray-400 uppercase tracking-[0.22em] mb-3 block">
            Precios transparentes
          </span>
          <h2 className="text-4xl font-display font-bold tracking-tighter leading-[0.9]">
            Sin permanencias.<br />
            <span className="text-gray-400 italic font-light">Cancela cuando quieras.</span>
          </h2>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-5 items-center">
          {plans.map((plan, idx) => (
            <motion.div
              key={plan.key}
              initial={{ opacity: 0, y: 24 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: idx * 0.08 }}
              className={cn(
                'p-6 rounded-[2rem] border-2 flex flex-col transition-all duration-500 relative',
                plan.primary
                  ? 'bg-black text-white border-black shadow-2xl scale-105 z-10'
                  : 'bg-[#F9F9F7] text-gray-900 border-transparent hover:border-gray-200'
              )}
            >
              {plan.primary && (
                <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-yellow-400 text-black px-3 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-widest whitespace-nowrap">
                  Más popular
                </div>
              )}

              <div className="mb-4">
                <h3 className="text-lg font-display font-semibold mb-1">{plan.name}</h3>
                <p className={cn('text-[12px] leading-relaxed', plan.primary ? 'text-white/60' : 'text-gray-500')}>
                  {plan.desc}
                </p>
              </div>

              <div className="mb-4 flex items-baseline gap-1">
                <span className="text-4xl font-display font-bold tracking-tighter">{plan.price}</span>
                <span className={cn('text-[11px] font-medium opacity-50')}>{plan.plus}</span>
              </div>

              <ul className="space-y-2 mb-5 flex-1">
                {plan.features.map(f => (
                  <li key={f} className="flex items-center gap-2.5 text-[12px]">
                    <div className={cn(
                      'w-4 h-4 rounded-full flex items-center justify-center shrink-0',
                      plan.primary ? 'bg-white/10 text-white' : 'bg-black/5 text-black'
                    )}>
                      <Check className="w-2.5 h-2.5" />
                    </div>
                    <span className="opacity-80">{f}</span>
                  </li>
                ))}
              </ul>

              <a
                href={plan.href}
                className={cn(
                  'w-full py-3 rounded-xl font-medium text-sm flex items-center justify-center gap-2 transition-all active:scale-95',
                  plan.primary
                    ? 'bg-white text-black hover:bg-gray-100'
                    : 'bg-black text-white hover:bg-gray-800'
                )}
              >
                {plan.cta}
                <ArrowRight className="w-3.5 h-3.5" />
              </a>
            </motion.div>
          ))}
        </div>

        <p className="text-center text-[11px] text-gray-400 mt-6">
          Todos los precios son sin IVA. IVA aplicable según legislación española vigente (21%).
        </p>
      </div>
    </section>
  );
}
