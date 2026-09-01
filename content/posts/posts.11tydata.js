export default {
  layout: "layouts/post.njk",
  permalink: "/posts/{{ page.fileSlug }}/index.html",
  eleventyComputed: {
    date: (data) => {
      if (!data.created) return data.page.date;
      const text = String(data.created).trim();
      const usDate = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
      if (usDate) return new Date(Date.UTC(Number(usDate[3]), Number(usDate[1]) - 1, Number(usDate[2])));
      const normalized = text.replace(" ", "T");
      return new Date(`${normalized}${normalized.endsWith("Z") ? "" : "Z"}`);
    },
  },
};
