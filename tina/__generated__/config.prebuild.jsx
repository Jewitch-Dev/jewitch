// tina/config.ts
import { defineConfig } from "tinacms";
var branch = process.env.HEAD || process.env.GITHUB_REF_NAME || process.env.CF_PAGES_BRANCH || "main";
var config_default = defineConfig({
  branch,
  clientId: "10f49e2b-5c36-4a1a-be72-268088c0f3da",
  token: process.env.TINA_TOKEN,
  build: {
    outputFolder: "admin",
    publicFolder: "published"
  },
  media: {
    tina: {
      mediaRoot: "assets",
      publicFolder: "content"
    }
  },
  schema: {
    collections: [
      {
        name: "posts",
        label: "Posts",
        path: "content/posts",
        format: "md",
        ui: {
          filename: {
            readonly: false,
            slugify: (values) => {
              const title = values?.title || "new-post";
              return String(title).toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
            }
          }
        },
        fields: [
          {
            type: "string",
            name: "title",
            label: "Title",
            isTitle: true,
            required: true
          },
          {
            type: "string",
            name: "description",
            label: "Description",
            ui: {
              component: "textarea"
            }
          },
          {
            type: "string",
            name: "created",
            label: "Created",
            required: true
          },
          {
            type: "string",
            name: "modified",
            label: "Modified"
          },
          {
            type: "string",
            name: "tags",
            label: "Tags",
            description: "Comma-separated, like: Mania, Creativity, Personal"
          },
          {
            type: "string",
            name: "template",
            label: "Template",
            required: true
          },
          {
            type: "string",
            name: "uuid",
            label: "UUID"
          },
          {
            type: "rich-text",
            name: "body",
            label: "Post",
            isBody: true
          }
        ]
      }
    ]
  }
});
export {
  config_default as default
};
