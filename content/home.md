---
title: Jewitch
description: Musings of a Jewish Witch
created: "2026-06-15 23:02:24"
modified: "2026-06-15 23:02:24"
uuid: 17007495-212a-4610-bf8f-b9d7c890ff52
---
<h1>Jewitch</h1>

<p>
Musings of a Jewish Witch.
</p>

<?php

$posts = glob('./content/posts/*.md');

$years = [];

foreach ($posts as $post) {

    $content = file_get_contents($post);

    $title = basename($post, '.md');
    $created = date('Y-m-d', filemtime($post));

    if (preg_match('/title:\s*(.+)/i', $content, $matches)) {
        $title = trim($matches[1]);
    }

    if (preg_match('/created:\s*([0-9]{4})-([0-9]{2})-([0-9]{2})/i', $content, $matches)) {
        $created = "{$matches[1]}-{$matches[2]}-{$matches[3]}";
    }

    $year = substr($created, 0, 4);

    $years[$year][] = [
        'title' => $title,
        'slug' => basename($post, '.md'),
        'created' => $created
    ];
}

krsort($years);

foreach ($years as $year => $entries) {

    usort($entries, function($a, $b) {
        return strcmp($b['created'], $a['created']);
    });

    echo "<h2>{$year}</h2>";
    echo "<ul>";

    foreach ($entries as $entry) {
        echo "<li><a href='/posts/{$entry['slug']}/'>{$entry['title']}</a></li>";
    }

    echo "</ul>";
}
?>