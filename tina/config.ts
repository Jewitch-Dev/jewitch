import { defineConfig } from "tinacms";

const branch =
  process.env.HEAD ||
  process.env.GITHUB_REF_NAME ||
  process.env.CF_PAGES_BRANCH ||
  "main";

export default defineConfig({
  branch,
  clientId: "10f49e2b-5c36-4a1a-be72-268088c0f3da",
  token: process.env.TINA_TOKEN,
  build: {
    outputFolder: "admin",
    publicFolder: "published",
  },
  media: {
    tina: {
      mediaRoot: "assets",
      publicFolder: "content",
    },
  },
  schema: {
    collections: [
      {
        name: "pages",
        label: "Pages",
        path: "content",
        format: "md",
        match: {
          include: "*.md",
          exclude: "posts/**",
        },
        ui: {
          allowedActions: {
            create: false,
            delete: false,
          },
        },
        fields: [
          {
            type: "string",
            name: "title",
            label: "Title",
            isTitle: true,
            required: true,
          },
          {
            type: "string",
            name: "description",
            label: "Description",
            ui: {
              component: "textarea",
            },
          },
          {
            type: "string",
            name: "created",
            label: "Created",
          },
          {
            type: "string",
            name: "modified",
            label: "Modified",
          },
          {
            type: "string",
            name: "status",
            label: "Status",
            options: ["draft"],
            description: "Set to draft to keep a page from publishing.",
          },
          {
            type: "string",
            name: "template",
            label: "Template",
            options: ["page.html", "home.html"],
            description: "Leave blank for the default page template.",
          },
          {
            type: "string",
            name: "uuid",
            label: "UUID",
          },
          {
            type: "rich-text",
            name: "body",
            label: "Page Content",
            isBody: true,
          },
        ],
      },
      {
        name: "posts",
        label: "Posts",
        path: "content/posts",
        format: "md",
        ui: {
          allowedActions: {
            create: true,
            delete: true,
          },
          filename: {
            readonly: false,
            slugify: (values) => {
              const title = values?.title || "new-post";
              return String(title)
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, "-")
                .replace(/^-|-$/g, "");
            },
          },
        },
        fields: [
          {
            type: "string",
            name: "title",
            label: "Title",
            isTitle: true,
            required: true,
          },
          {
            type: "string",
            name: "description",
            label: "Description",
            ui: {
              component: "textarea",
            },
          },
          {
            type: "string",
            name: "created",
            label: "Created",
            required: true,
          },
          {
            type: "string",
            name: "modified",
            label: "Modified",
          },
          {
            type: "string",
            name: "tags",
            label: "Tags",
            description: "Comma-separated, like: Mania, Creativity, Personal",
          },
          {
            type: "string",
            name: "template",
            label: "Template",
            required: true,
          },
          {
            type: "string",
            name: "uuid",
            label: "UUID",
          },
          {
            type: "rich-text",
            name: "body",
            label: "Post",
            isBody: true,
          },
        ],
      },
    ],
  },
});
