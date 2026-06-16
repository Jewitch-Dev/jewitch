---
title: Blog
description: Blog archive
created: "2026-06-15 22:42:41"
modified: "2026-06-15 22:42:41"
uuid: 3fd9ddc2-3e9b-4842-9f59-cfef47ea30f0
---
<h1>Blog</h1>

<ul class="post-list">

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

    $filename = basename($post, '.md');
    $items[] = [
        'title' => $title,
        'description' => $description,
        'created' => $created,
        'filename' => $filename,
        'timestamp' => strtotime($created) ?: filemtime($post),
    ];

}

usort($items, function ($a, $b) {
    return $b['timestamp'] <=> $a['timestamp'];
});

foreach ($items as $item) {
    $title = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8');
    $created = htmlspecialchars(date('F j, Y', $item['timestamp']), ENT_QUOTES, 'UTF-8');
    $filename = rawurlencode($item['filename']);

    echo "<li>";
    echo "<a href='/posts/{$filename}/'>{$title}</a>";
    echo "<time>{$created}</time>";
    if ($description !== '') {
        echo "<p>{$description}</p>";
    }
    echo "</li>";

}

?>

</ul>
