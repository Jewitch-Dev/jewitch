#!/usr/bin/env python3
"""Convert Jewitch's legacy Markdown archive into a Ghost JSON import.

Usage from repository root:
    python3 tools/migrate-to-ghost.py
    python3 tools/migrate-to-ghost.py --input content/posts --output ghost-import.json

The generated file is intended for Ghost Admin -> Settings -> Advanced -> Import/Export.
It does not modify the legacy Markdown archive.
"""
from __future__ import annotations

import argparse
import json
import re
import uuid
from datetime import datetime, timezone
from pathlib import Path


def parse_scalar(value: str):
    value = value.strip()
    if len(value) >= 2 and value[0] == value[-1] and value[0] in {'"', "'"}:
        value = value[1:-1]
    return value


def parse_markdown(path: Path):
    text = path.read_text(encoding="utf-8-sig")
    meta = {}
    body = text
    if text.startswith("---"):
        parts = text.split("---", 2)
        if len(parts) == 3:
            raw_meta, body = parts[1], parts[2].lstrip("\r\n")
            for line in raw_meta.splitlines():
                if not line.strip() or line.lstrip().startswith("#") or ":" not in line:
                    continue
                key, value = line.split(":", 1)
                meta[key.strip()] = parse_scalar(value)
    return meta, body.rstrip() + "\n"


def ghost_date(value: str | None, fallback: datetime) -> str:
    if not value:
        dt = fallback
    else:
        cleaned = value.strip().replace("Z", "+00:00")
        dt = None
        for fmt in (None, "%Y-%m-%d %H:%M:%S", "%Y-%m-%d"):
            try:
                dt = datetime.fromisoformat(cleaned) if fmt is None else datetime.strptime(cleaned, fmt)
                break
            except ValueError:
                pass
        if dt is None:
            dt = fallback
    if dt.tzinfo is None:
        dt = dt.replace(tzinfo=timezone.utc)
    return dt.astimezone(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")


def slugify(filename: str) -> str:
    slug = Path(filename).stem.lower()
    slug = re.sub(r"[^a-z0-9]+", "-", slug).strip("-")
    return slug or str(uuid.uuid4())


def markdown_card(markdown: str) -> str:
    # Ghost's Lexical format supports a Markdown card. This preserves the source
    # faithfully and lets Ghost render normal Markdown without lossy conversion.
    lexical = {
        "root": {
            "children": [{"type": "markdown", "version": 1, "markdown": markdown}],
            "direction": None,
            "format": "",
            "indent": 0,
            "type": "root",
            "version": 1,
        }
    }
    return json.dumps(lexical, ensure_ascii=False, separators=(",", ":"))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", default="content/posts")
    parser.add_argument("--output", default="ghost-import.json")
    parser.add_argument("--author", default="Shira")
    args = parser.parse_args()

    source = Path(args.input)
    files = sorted(source.glob("*.md"))
    if not files:
        raise SystemExit(f"No Markdown posts found in {source}")

    now = datetime.now(timezone.utc)
    posts = []
    tags_by_name = {}
    post_tags = []

    for index, path in enumerate(files, start=1):
        meta, body = parse_markdown(path)
        title = meta.get("title") or path.stem.replace("-", " ").title()
        created = ghost_date(meta.get("created"), now)
        updated = ghost_date(meta.get("modified") or meta.get("created"), now)
        slug = slugify(path.name)
        old_uuid = meta.get("uuid") or str(uuid.uuid4())
        post_id = str(index)

        posts.append({
            "id": post_id,
            "uuid": old_uuid,
            "title": title,
            "slug": slug,
            "lexical": markdown_card(body),
            "custom_excerpt": (meta.get("description") or "").strip() or None,
            "status": "published",
            "visibility": "public",
            "created_at": created,
            "updated_at": updated,
            "published_at": created,
            "created_by": "1",
            "updated_by": "1",
            "published_by": "1",
        })

        raw_tags = meta.get("tags", "")
        for tag_name in [x.strip() for x in raw_tags.split(",") if x.strip()]:
            key = tag_name.casefold()
            if key not in tags_by_name:
                tag_id = str(len(tags_by_name) + 1)
                tag_slug = re.sub(r"[^a-z0-9]+", "-", tag_name.lower()).strip("-") or f"tag-{tag_id}"
                tags_by_name[key] = {"id": tag_id, "name": tag_name, "slug": tag_slug}
            post_tags.append({"post_id": post_id, "tag_id": tags_by_name[key]["id"], "sort_order": 0})

    export = {
        "meta": {"exported_on": int(now.timestamp() * 1000), "version": "6.0.0"},
        "data": {
            "posts": posts,
            "tags": list(tags_by_name.values()),
            "posts_tags": post_tags,
        },
    }

    output = Path(args.output)
    output.write_text(json.dumps(export, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Converted {len(posts)} posts and {len(tags_by_name)} tags -> {output}")
    print("Legacy Markdown was not modified.")


if __name__ == "__main__":
    main()
