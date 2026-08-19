import fs from 'node:fs/promises';
import path from 'node:path';

const ghostUrl = (process.env.GHOST_URL || 'https://cms.jewit.ch').replace(/\/$/, '');
const contentKey = process.env.GHOST_CONTENT_API_KEY;
const publicBase = (process.env.BLOG_PUBLIC_BASE || '/blog-next').replace(/\/$/, '');
const outputRoot = process.env.BLOG_OUTPUT || 'published/blog-next';

if (!contentKey) {
  console.error('GHOST_CONTENT_API_KEY is required.');
  process.exit(1);
}

const escapeHtml = (value = '') => String(value)
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')
  .replaceAll('"', '&quot;')
  .replaceAll("'", '&#039;');

const formatDate = (value) => new Intl.DateTimeFormat('en-US', {
  year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC'
}).format(new Date(value));

async function ghost(endpoint, params = {}) {
  const url = new URL(`${ghostUrl}/ghost/api/content/${endpoint}/`);
  url.searchParams.set('key', contentKey);
  for (const [key, value] of Object.entries(params)) url.searchParams.set(key, value);
  const response = await fetch(url);
  if (!response.ok) throw new Error(`Ghost API ${response.status}: ${await response.text()}`);
  return response.json();
}

function shell({ title, description, canonical, body, type = 'website', image = '' }) {
  const imageMeta = image ? `\n<meta property="og:image" content="${escapeHtml(image)}">\n<meta name="twitter:card" content="summary_large_image">\n<meta name="twitter:image" content="${escapeHtml(image)}">` : '';
  return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${escapeHtml(title)} | Jewitch</title>
<meta name="description" content="${escapeHtml(description)}">
<link rel="canonical" href="${escapeHtml(canonical)}">
<meta property="og:title" content="${escapeHtml(title)}">
<meta property="og:description" content="${escapeHtml(description)}">
<meta property="og:type" content="${type}">
<meta property="og:url" content="${escapeHtml(canonical)}">
<meta property="og:site_name" content="Jewitch">${imageMeta}
<link rel="stylesheet" href="/assets/site.css">
</head>
<body class="post-page">
<a class="visually-hidden" rel="me" href="https://social.lol/@jewitch">Mastodon</a>
<div class="site">
<header class="site-header">
<h1 class="site-title"><a href="/">Jewitch</a></h1>
<div class="tagline">Musings of a Jewish Witch</div>
<nav class="site-nav" aria-label="Main navigation">
<a href="/">Home</a><a href="${publicBase}/">Blog Next</a><a href="/about/">About</a><a href="/now/">Now</a><a href="/colophon/">Colophon</a>
</nav>
</header>
<div class="content post-content"><main>${body}</main></div>
<footer class="site-footer">Copyright 2026 Shira.</footer>
</div>
</body>
</html>`;
}

await fs.rm(outputRoot, { recursive: true, force: true });
await fs.mkdir(outputRoot, { recursive: true });

const data = await ghost('posts', { limit: 'all', include: 'tags,authors', order: 'published_at desc' });
const posts = data.posts || [];

const cards = posts.map(post => `
<article class="post-article">
<header class="post-heading">
<h2><a href="${publicBase}/${escapeHtml(post.slug)}/">${escapeHtml(post.title)}</a></h2>
<p>${escapeHtml(post.custom_excerpt || post.excerpt || '')}</p>
</header>
<div class="post-meta"><span>Published ${formatDate(post.published_at)}</span></div>
</article>`).join('\n');

const indexBody = `<header class="post-heading"><h1>Blog Next</h1><p>Ghost-powered development preview. The live blog remains untouched.</p></header>${cards || '<p>No published posts yet.</p>'}`;
await fs.writeFile(path.join(outputRoot, 'index.html'), shell({
  title: 'Blog Next',
  description: 'Ghost-powered development preview for Jewitch.',
  canonical: `https://jewit.ch${publicBase}/`,
  body: indexBody
}));

for (const post of posts) {
  const dir = path.join(outputRoot, post.slug);
  await fs.mkdir(dir, { recursive: true });
  const tags = (post.tags || []).map(tag => `<span>${escapeHtml(tag.name)}</span>`).join(' · ');
  const body = `<article class="post-article">
<header class="post-heading"><h1>${escapeHtml(post.title)}</h1><p>${escapeHtml(post.custom_excerpt || post.excerpt || '')}</p></header>
<div class="post-meta"><span>Published ${formatDate(post.published_at)}</span>${tags ? ` · ${tags}` : ''}</div>
${post.feature_image ? `<img src="${escapeHtml(post.feature_image)}" alt="${escapeHtml(post.feature_image_alt || post.title)}">` : ''}
<div class="post-body">${post.html || ''}</div>
</article>
<a class="back-link" href="${publicBase}/">Return to Blog Next</a>`;
  await fs.writeFile(path.join(dir, 'index.html'), shell({
    title: post.meta_title || post.title,
    description: post.meta_description || post.custom_excerpt || post.excerpt || '',
    canonical: `https://jewit.ch${publicBase}/${post.slug}/`,
    type: 'article',
    image: post.og_image || post.feature_image || '',
    body
  }));
}

console.log(`Built ${posts.length} Ghost post(s) into ${outputRoot}.`);
