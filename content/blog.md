---
title: Blog
description: Blog archive
created: "2026-06-15 22:42:41"
modified: "2026-06-15 22:42:41"
uuid: 3fd9ddc2-3e9b-4842-9f59-cfef47ea30f0
---
<h1>Blog</h1>
<p class="blog-intro">A personal blog. Random brain droppings, late-night projects, music, memory, and whatever else wanders through.</p>

<?php

$posts = glob('./content/posts/*.md');
$items = [];

foreach ($posts as $post) {

    $content = file_get_contents($post);

    if (preg_match('/title:\s*(.+)/i', $content, $matches)) {
        $title = trim($matches[1]);
    } else {
        $title = basename($post, '.md');
    }

    if (preg_match('/description:\s*(.+)/i', $content, $matches)) {
        $description = trim($matches[1], " \"'");
    } else {
        $description = '';
    }

    if (preg_match('/created:\s*"?([^"\n]+)"?/i', $content, $matches)) {
        $created = trim($matches[1]);
    } else {
        $created = date('Y-m-d', filemtime($post));
    }

    if (preg_match('/tags:\s*(.+)/i', $content, $matches)) {
        $tags = array_filter(array_map('trim', explode(',', trim($matches[1], " \"'"))));
    } else {
        $tags = [];
    }

    $filename = basename($post, '.md');
    $items[] = [
        'title' => $title,
        'description' => $description,
        'created' => $created,
        'tags' => $tags,
        'filename' => $filename,
        'timestamp' => strtotime($created) ?: filemtime($post),
    ];

}

usort($items, function ($a, $b) {
    return $b['timestamp'] <=> $a['timestamp'];
});

$current_year = null;

foreach ($items as $item) {
    $title = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
    $year = date('Y', $item['timestamp']);
    $date = htmlspecialchars(strtoupper(date('M j', $item['timestamp'])), ENT_QUOTES, 'UTF-8');
    $filename = rawurlencode($item['filename']);

    if ($year !== $current_year) {
        if ($current_year !== null) {
            echo "</ul>";
        }

        $current_year = $year;
        echo "<h2 class='archive-year'>{$year}</h2>";
        echo "<ul class='post-list archive-list'>";
    }

    echo "<li class='archive-item'>";
    echo "<a class='archive-link' href='/posts/{$filename}/'>";
    echo "<time datetime='" . htmlspecialchars(date('Y-m-d', $item['timestamp']), ENT_QUOTES, 'UTF-8') . "'>{$date}</time>";
    echo "<span>{$title}</span>";
    echo "</a>";
    echo "</li>";

}

if ($current_year !== null) {
    echo "</ul>";
}

?>
