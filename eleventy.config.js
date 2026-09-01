function parseContentDate(value) {
  if (value instanceof Date) return value;
  const text = String(value || "").trim();
  const usDate = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
  if (usDate) return new Date(Date.UTC(Number(usDate[3]), Number(usDate[1]) - 1, Number(usDate[2])));
  const normalized = text.replace(" ", "T");
  return new Date(`${normalized}${normalized.endsWith("Z") ? "" : "Z"}`);
}

export default function (eleventyConfig) {
  eleventyConfig.addPassthroughCopy({ "content/assets": "assets" });

  eleventyConfig.addCollection("posts", (collectionApi) =>
    collectionApi.getFilteredByGlob("content/posts/*.md").sort((a, b) => b.date - a.date)
  );

  eleventyConfig.addCollection("notes", (collectionApi) =>
    collectionApi.getFilteredByGlob("content/notes/imported/*.md").sort((a, b) => b.date - a.date)
  );

  eleventyConfig.addFilter("readableDate", (date) =>
    new Intl.DateTimeFormat("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      timeZone: "UTC",
    }).format(parseContentDate(date))
  );

  eleventyConfig.addFilter("shortDate", (date) =>
    new Intl.DateTimeFormat("en-US", {
      month: "short",
      day: "numeric",
      timeZone: "UTC",
    }).format(parseContentDate(date)).toUpperCase()
  );

  eleventyConfig.addFilter("tagList", (tags) => {
    if (!tags) return [];
    return Array.isArray(tags) ? tags : String(tags).split(",").map((tag) => tag.trim()).filter(Boolean);
  });

  return {
    dir: {
      input: "content",
      includes: "../_includes",
      data: "../_data",
      output: "_site",
    },
    markdownTemplateEngine: "njk",
    htmlTemplateEngine: "njk",
  };
}
