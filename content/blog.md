---
title: Blog
description: Blog archive
created: "2026-06-15 22:42:41"
modified: "2026-06-15 22:42:41"
uuid: 3fd9ddc2-3e9b-4842-9f59-cfef47ea30f0
---
<h1>Blog</h1>

<ul>

<?php

$posts = glob('./content/posts/*.md');

foreach ($posts as $post) {

    $content = file_get_contents($post);

    if (preg_match('/title:\s*(.+)/i', $content, $matches)) {
        $title = trim($matches[1]);
    } else {
        $title = basename($post, '.md');
    }

    $filename = basename($post, '.md');

    echo "<li><a href='/posts/{$filename}/'>{$title}</a></li>";

}

?>

</ul>