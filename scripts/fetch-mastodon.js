import fs from "node:fs/promises";
import path from "node:path";

const instance = (process.env.MASTODON_INSTANCE || "https://social.lol").replace(/\/$/, "");
const username = process.env.MASTODON_USERNAME || "jewitch";
const maximum = Math.max(1, Number(process.env.MASTODON_MAX_POSTS || 100));
const outputDirectory = path.resolve("content/notes/imported");

function escapeHtml(value = "") {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function safeAttribute(value = "") {
  return escapeHtml(value).replaceAll("{", "&#123;").replaceAll("}", "&#125;");
}

function cleanMastodonHtml(value = "") {
  return String(value)
    .replace(/<(script|style|iframe|object|embed|form)[^>]*>[\s\S]*?<\/\1>/gi, "")
    .replace(/\s(?:on\w+|style)=(?:"[^"]*"|'[^']*')/gi, "")
    .replaceAll("{", "&#123;")
    .replaceAll("}", "&#125;");
}

function plainText(value = "") {
  return String(value)
    .replace(/<br\s*\/?\s*>/gi, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/&nbsp;/gi, " ")
    .replace(/&amp;/gi, "&")
    .replace(/&lt;/gi, "<")
    .replace(/&gt;/gi, ">")
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/gi, "'")
    .replace(/\s+/g, " ")
    .trim();
}

function mediaMarkup(attachments = []) {
  if (!attachments.length) return "";
  const items = attachments.map((media) => {
    const source = safeAttribute(media.url || media.remote_url || media.preview_url || "");
    const preview = safeAttribute(media.preview_url || source);
    const description = safeAttribute(media.description || "Attached media from Mastodon");
    if (!source) return "";
    if (media.type === "video" || media.type === "gifv") {
      return `<figure class="note-media"><video controls playsinline preload="metadata" poster="${preview}"><source src="${source}"></video><figcaption>${description}</figcaption></figure>`;
    }
    if (media.type === "audio") {
      return `<figure class="note-media"><audio controls preload="metadata" src="${source}"></audio><figcaption>${description}</figcaption></figure>`;
    }
    return `<figure class="note-media"><a href="${source}"><img src="${preview || source}" alt="${description}" loading="lazy"></a></figure>`;
  }).filter(Boolean);
  return items.length ? `<div class="note-media-grid">${items.join("")}</div>` : "";
}

function renderBody(status) {
  const body = `${cleanMastodonHtml(status.content)}${mediaMarkup(status.media_attachments)}`;
  if (!status.spoiler_text) return body;
  return `<details class="note-warning"><summary>Content warning: ${escapeHtml(status.spoiler_text)}</summary>${body}</details>`;
}

function yamlString(value) {
  return JSON.stringify(String(value ?? ""));
}

async function mastodonJson(url) {
  const response = await fetch(url, {
    headers: { Accept: "application/json", "User-Agent": "Jewitch-Eleventy/1.0 (+https://jewit.ch/)" },
  });
  if (!response.ok) throw new Error(`${response.status} ${response.statusText} from ${url}`);
  return { data: await response.json(), link: response.headers.get("link") || "" };
}

function nextPage(linkHeader) {
  const match = linkHeader.match(/<([^>]+)>;\s*rel="next"/);
  return match?.[1] || null;
}

async function run() {
  await fs.mkdir(outputDirectory, { recursive: true });
  const lookup = new URL("/api/v1/accounts/lookup", instance);
  lookup.searchParams.set("acct", username);
  const { data: account } = await mastodonJson(lookup);

  const firstPage = new URL(`/api/v1/accounts/${account.id}/statuses`, instance);
  firstPage.searchParams.set("limit", "40");
  firstPage.searchParams.set("exclude_reblogs", "true");
  firstPage.searchParams.set("exclude_replies", "true");

  const statuses = [];
  let pageUrl = firstPage.toString();
  while (pageUrl && statuses.length < maximum) {
    const page = await mastodonJson(pageUrl);
    statuses.push(...page.data);
    pageUrl = nextPage(page.link);
  }

  const publicStatuses = statuses
    .filter((status) => !status.reblog && !status.in_reply_to_id && status.visibility === "public")
    .slice(0, maximum);

  for (const status of publicStatuses) {
    const text = plainText(status.content);
    const description = text.length > 180 ? `${text.slice(0, 177).trimEnd()}…` : text;
    const dateLabel = new Intl.DateTimeFormat("en-US", {
      year: "numeric", month: "long", day: "numeric", timeZone: "UTC",
    }).format(new Date(status.created_at));
    const document = `---\nlayout: layouts/note.njk\ntitle: ${yamlString(`Note — ${dateLabel}`)}\ndescription: ${yamlString(description || "A note from Shira on Mastodon.")}\ndate: ${yamlString(status.created_at)}\nmastodon_created_at: ${yamlString(status.created_at)}\nmastodon_id: ${yamlString(status.id)}\nsource_url: ${yamlString(status.url)}\npermalink: /notes/${status.id}/index.html\ntags:\n  - note\n---\n${renderBody(status)}\n`;
    await fs.writeFile(path.join(outputDirectory, `${status.id}.md`), document, "utf8");
  }

  console.log(`Imported ${publicStatuses.length} public posts from @${username}@${new URL(instance).host}.`);
}

run().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
