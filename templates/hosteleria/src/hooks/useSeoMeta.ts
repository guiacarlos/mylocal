import { useEffect } from 'react';

interface SeoMeta {
  title:       string;
  description: string;
  ogImage?:    string;
  ogType?:     string;
  canonical?:  string;
}

function setMetaTag(attr: 'name' | 'property', key: string, value: string) {
  let el = document.querySelector(`meta[${attr}="${key}"]`) as HTMLMetaElement | null;
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute(attr, key);
    document.head.appendChild(el);
  }
  el.content = value;
}

function setLinkTag(rel: string, href: string) {
  let el = document.querySelector(`link[rel="${rel}"]`) as HTMLLinkElement | null;
  if (!el) {
    el = document.createElement('link');
    el.rel = rel;
    document.head.appendChild(el);
  }
  el.href = href;
}

export function useSeoMeta({ title, description, ogImage, ogType = 'website', canonical }: SeoMeta) {
  useEffect(() => {
    const prevTitle = document.title;
    document.title = title;

    setMetaTag('name',     'description',       description);
    setMetaTag('property', 'og:title',          title);
    setMetaTag('property', 'og:description',    description);
    setMetaTag('property', 'og:type',           ogType);
    setMetaTag('name',     'twitter:card',      'summary_large_image');
    setMetaTag('name',     'twitter:title',     title);
    setMetaTag('name',     'twitter:description', description);

    if (ogImage) {
      setMetaTag('property', 'og:image',         ogImage);
      setMetaTag('name',     'twitter:image',    ogImage);
    }
    if (canonical) setLinkTag('canonical', canonical);

    return () => { document.title = prevTitle; };
  }, [title, description, ogImage, ogType, canonical]);
}
