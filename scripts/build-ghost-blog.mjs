import fs from 'node:fs/promises';
import path from 'node:path';

const ghostUrl = (process.env.GHOST_URL || 'https://cms.jewit.ch').replace(/\/$/, '');
const contentKey = process.env.GHOST_CONTENT_API_KEY;
const publicBase = (process.env.BLOG_PUBLIC_BASE || '/blog-next').replace(/\/$/, '');
const outputRoot = process.env.BLOG_OUTPUT || 'published/blog-next';
const siteUrl = 'https://jewit.ch';
if (!contentKey) throw new Error('GHOST_CONTENT_API_KEY is required.');

const esc = (v = '') => String(v).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
const date = v => new Intl.DateTimeFormat('en-US',{year:'numeric',month:'long',day:'numeric',timeZone:'UTC'}).format(new Date(v));
const xml = esc;
const postUrl = p => `${siteUrl}${publicBase}/${p.slug}/`;
const tagUrl = t => `${publicBase}/tag/${t.slug}/`;

async function ghost(endpoint, params={}) {
  const url = new URL(`${ghostUrl}/ghost/api/content/${endpoint}/`);
  url.searchParams.set('key',contentKey);
  Object.entries(params).forEach(([k,v])=>url.searchParams.set(k,v));
  const r=await fetch(url); if(!r.ok) throw new Error(`Ghost API ${r.status}: ${await r.text()}`); return r.json();
}
async function write(rel,content){const file=path.join(outputRoot,rel);await fs.mkdir(path.dirname(file),{recursive:true});await fs.writeFile(file,content);}

function shell({title,description,canonical,body,type='website',image=''}){
 const imageMeta=image?`<meta property="og:image" content="${esc(image)}"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:image" content="${esc(image)}">`:'<meta name="twitter:card" content="summary">';
 return `<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${esc(title)} | Jewitch</title><meta name="description" content="${esc(description)}"><link rel="canonical" href="${esc(canonical)}"><link rel="alternate" type="application/rss+xml" title="Jewitch Blog" href="${siteUrl}${publicBase}/rss.xml"><meta property="og:title" content="${esc(title)}"><meta property="og:description" content="${esc(description)}"><meta property="og:type" content="${type}"><meta property="og:url" content="${esc(canonical)}"><meta property="og:site_name" content="Jewitch">${imageMeta}<link rel="stylesheet" href="/assets/site.css"><link rel="stylesheet" href="${publicBase}/ghost-blog.css"></head><body class="ghost-blog"><a class="visually-hidden" rel="me" href="https://social.lol/@jewitch">Mastodon</a><div class="site"><header class="site-header"><h1 class="site-title"><a href="/">Jewitch</a></h1><div class="tagline">Musings of a Jewish Witch</div><nav class="site-nav" aria-label="Main navigation"><a href="/">Home</a><a href="${publicBase}/">Blog</a><a href="${publicBase}/archive/">Archive</a><a href="/about/">About</a><a href="/now/">Now</a></nav></header><div class="content ghost-content"><main>${body}</main></div><footer class="site-footer">Copyright 2026 Shira. Published with Ghost, built statically, served by Cloudflare.</footer></div></body></html>`;
}
const tagLinks=p=>(p.tags||[]).map(t=>`<a class="ghost-tag" href="${tagUrl(t)}">${esc(t.name)}</a>`).join(' ');
const card=p=>`<article class="ghost-card">${p.feature_image?`<a class="ghost-card-image" href="${publicBase}/${esc(p.slug)}/"><img src="${esc(p.feature_image)}" alt="${esc(p.feature_image_alt||'')}"></a>`:''}<div class="ghost-card-copy"><div class="ghost-kicker">${date(p.published_at)}${p.reading_time?` · ${p.reading_time} min read`:''}</div><h2><a href="${publicBase}/${esc(p.slug)}/">${esc(p.title)}</a></h2><p>${esc(p.custom_excerpt||p.excerpt||'')}</p><div class="ghost-tags">${tagLinks(p)}</div></div></article>`;

await fs.rm(outputRoot,{recursive:true,force:true}); await fs.mkdir(outputRoot,{recursive:true});
const {posts=[]}=await ghost('posts',{limit:'all',include:'tags,authors',order:'published_at desc'});
const tagsMap=new Map(); for(const p of posts) for(const t of (p.tags||[])){if(!tagsMap.has(t.slug))tagsMap.set(t.slug,{...t,posts:[]});tagsMap.get(t.slug).posts.push(p);}

await write('ghost-blog.css',`.ghost-content{max-width:980px}.ghost-hero{padding:2rem 0 1.5rem;border-bottom:1px solid var(--border-color,#444);margin-bottom:1.5rem}.ghost-hero h1{font-size:clamp(2.5rem,8vw,5.5rem);line-height:.95;margin:0 0 .75rem}.ghost-hero p{font-size:1.15rem;max-width:44rem}.ghost-grid{display:grid;gap:1.25rem}.ghost-card{display:grid;grid-template-columns:minmax(0,240px) 1fr;gap:1.25rem;padding:1.25rem 0;border-bottom:1px solid var(--border-color,#444)}.ghost-card-image img,.ghost-feature{width:100%;height:auto;display:block;border-radius:12px}.ghost-card h2{margin:.25rem 0;font-size:clamp(1.5rem,4vw,2.2rem)}.ghost-card h2 a{text-decoration:none}.ghost-kicker,.ghost-post-meta{font-size:.85rem;opacity:.72}.ghost-tag{display:inline-block;margin:.35rem .3rem 0 0;padding:.2rem .55rem;border:1px solid currentColor;border-radius:999px;font-size:.78rem;text-decoration:none}.ghost-post-header{margin:2rem 0 1.5rem}.ghost-post-header h1{font-size:clamp(2.4rem,7vw,4.8rem);line-height:1;margin:.35rem 0}.ghost-deck{font-size:1.2rem;max-width:48rem}.ghost-post-body{font-size:1.08rem;line-height:1.75}.ghost-post-body img{max-width:100%;height:auto}.ghost-post-body blockquote{margin-left:0;padding-left:1.25rem;border-left:3px solid currentColor}.ghost-archive-list{list-style:none;padding:0}.ghost-archive-list li{display:grid;grid-template-columns:9rem 1fr;gap:1rem;padding:.7rem 0;border-bottom:1px solid var(--border-color,#444)}@media(max-width:650px){.ghost-card{grid-template-columns:1fr}.ghost-card-image img{max-height:260px;object-fit:cover}.ghost-archive-list li{grid-template-columns:1fr;gap:.15rem}}`);

const hero=`<section class="ghost-hero"><div class="ghost-kicker">JEWITCH / THE BLOG</div><h1>Random brain droppings.</h1><p>Writing, life, technology, religion, games, and whatever else escaped containment.</p></section>`;
await write('index.html',shell({title:'Blog',description:'Random brain droppings from Jewitch.',canonical:`${siteUrl}${publicBase}/`,body:`${hero}<section class="ghost-grid">${posts.map(card).join('')||'<p>No posts yet.</p>'}</section>`}));

for(const p of posts){const authors=(p.authors||[]).map(a=>esc(a.name)).join(', ');const body=`<article><header class="ghost-post-header"><div class="ghost-kicker">${tagLinks(p)}</div><h1>${esc(p.title)}</h1>${(p.custom_excerpt||p.excerpt)?`<p class="ghost-deck">${esc(p.custom_excerpt||p.excerpt)}</p>`:''}<div class="ghost-post-meta">${date(p.published_at)}${authors?` · ${authors}`:''}${p.reading_time?` · ${p.reading_time} min read`:''}</div></header>${p.feature_image?`<img class="ghost-feature" src="${esc(p.feature_image)}" alt="${esc(p.feature_image_alt||p.title)}">`:''}<div class="ghost-post-body">${p.html||''}</div></article><p><a class="back-link" href="${publicBase}/">← Back to the blog</a></p>`;await write(`${p.slug}/index.html`,shell({title:p.meta_title||p.title,description:p.meta_description||p.custom_excerpt||p.excerpt||'',canonical:postUrl(p),type:'article',image:p.og_image||p.feature_image||'',body}));}

const archive=`<section class="ghost-hero"><div class="ghost-kicker">EVERYTHING, IN ORDER</div><h1>Archive</h1><p>${posts.length} published post${posts.length===1?'':'s'}.</p></section><ul class="ghost-archive-list">${posts.map(p=>`<li><time>${date(p.published_at)}</time><a href="${publicBase}/${esc(p.slug)}/">${esc(p.title)}</a></li>`).join('')}</ul>`;
await write('archive/index.html',shell({title:'Archive',description:'The Jewitch blog archive.',canonical:`${siteUrl}${publicBase}/archive/`,body:archive}));
for(const t of tagsMap.values()){const body=`<section class="ghost-hero"><div class="ghost-kicker">TAG</div><h1>${esc(t.name)}</h1>${t.description?`<p>${esc(t.description)}</p>`:''}</section><section class="ghost-grid">${t.posts.map(card).join('')}</section>`;await write(`tag/${t.slug}/index.html`,shell({title:`${t.name} — Blog`,description:t.description||`Posts tagged ${t.name}.`,canonical:`${siteUrl}${tagUrl(t)}`,body}));}

const rss=`<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>Jewitch</title><link>${siteUrl}${publicBase}/</link><description>Random brain droppings from Jewitch.</description>${posts.slice(0,50).map(p=>`<item><title>${xml(p.title)}</title><link>${postUrl(p)}</link><guid>${postUrl(p)}</guid><pubDate>${new Date(p.published_at).toUTCString()}</pubDate><description>${xml(p.custom_excerpt||p.excerpt||'')}</description></item>`).join('')}</channel></rss>`;
await write('rss.xml',rss);
const sitemap=`<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>${siteUrl}${publicBase}/</loc></url><url><loc>${siteUrl}${publicBase}/archive/</loc></url>${posts.map(p=>`<url><loc>${postUrl(p)}</loc><lastmod>${new Date(p.updated_at).toISOString()}</lastmod></url>`).join('')}${[...tagsMap.values()].map(t=>`<url><loc>${siteUrl}${tagUrl(t)}</loc></url>`).join('')}</urlset>`;
await write('sitemap.xml',sitemap);
console.log(`Built ${posts.length} post(s), ${tagsMap.size} tag page(s), archive, RSS, and sitemap into ${outputRoot}.`);
