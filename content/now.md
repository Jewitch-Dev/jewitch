---
title: Now
description: What I'm up to right now
created: "2026-06-15 22:35:48"
modified: "2026-06-15 22:35:48"
status: draft
updated: June 2026
uuid: 0b49af2c-9ad7-42ff-8caf-42a9a7952943
sections:
  - heading: Playing
    items:
      - Final Fantasy XI
      - Final Fantasy XIV
  - heading: Building
    items:
      - Jewitch
  - heading: Watching
    items:
      - The Closer
---
# Now

<?php
$updated = trim($data['updated'] ?? '');
$sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];

if($updated !== '') {
  echo '<p class="now-updated">Updated '.htmlspecialchars($updated, ENT_QUOTES, 'UTF-8').'</p>';
}

if(count($sections) > 0) {
  echo '<div class="now-sections">';

  foreach($sections as $section) {
    $heading = trim($section['heading'] ?? '');
    $summary = trim($section['summary'] ?? '');
    $items = is_array($section['items'] ?? null) ? $section['items'] : [];

    if($heading === '' && $summary === '' && count($items) === 0) {
      continue;
    }

    echo '<section class="now-section">';

    if($heading !== '') {
      echo '<h2>'.htmlspecialchars($heading, ENT_QUOTES, 'UTF-8').'</h2>';
    }

    if($summary !== '') {
      echo '<p>'.nl2br(htmlspecialchars($summary, ENT_QUOTES, 'UTF-8')).'</p>';
    }

    if(count($items) > 0) {
      echo '<ul>';
      foreach($items as $item) {
        $item = trim((string)$item);
        if($item !== '') {
          echo '<li>'.htmlspecialchars($item, ENT_QUOTES, 'UTF-8').'</li>';
        }
      }
      echo '</ul>';
    }

    echo '</section>';
  }

  echo '</div>';
}
?>
