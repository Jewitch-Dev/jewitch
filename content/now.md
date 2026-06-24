---
title: Now
description: A living little snapshot of what has my attention, heart, energy, and stubborn curiosity right now.
created: "2026-06-15 22:35:48"
modified: "2026-06-24 02:45:00"
updated: June 2026
uuid: 0b49af2c-9ad7-42ff-8caf-42a9a7952943
sections:
  - heading: In this season
    summary: "I am tending to the soft and strange parts of life: disability, politics, Jewishness, witchcraft, music, rest, grief, play, and the difficult work of staying human in a world that keeps asking people to become machines."
    items:
      - Making Jewitch feel more like a home than a website
      - Letting rest count as real work
      - Writing from the messy middle instead of waiting for perfect clarity
  - heading: Playing
    summary: Comfort worlds, familiar rhythms, and a little digital wandering.
    items:
      - Final Fantasy XI
      - Final Fantasy XIV
  - heading: Building
    summary: Slowly shaping the web into something more personal, cozy, useful, and mine.
    items:
      - Jewitch, this small corner of the internet
      - Better archives and more intentional pages
      - A site that feels alive without being exhausting to maintain
  - heading: Watching
    summary: Revisiting stories that can sit beside me while I think.
    items:
      - The Closer
      - The West Wing, probably forever
  - heading: Listening for
    summary: The songs, voices, and emotional weather that keep me company.
    items:
      - Stevie Nicks energy
      - Big feelings with a good bridge
      - Music that understands survival
---
<?php
$title = trim($data['title'] ?? 'Now');
$description = trim($data['description'] ?? '');
$updated = trim($data['updated'] ?? '');
$sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];

echo '<div class="now-page">';
echo '<section class="now-hero">';
echo '<div class="now-hero-copy">';
echo '<p class="now-kicker">Current Dispatch</p>';
echo '<h1>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h1>';

if($description !== '') {
  echo '<p class="now-description">'.htmlspecialchars($description, ENT_QUOTES, 'UTF-8').'</p>';
}

echo '<div class="now-pills" aria-label="Current themes">';
echo '<span>Writing</span>';
echo '<span>Resting</span>';
echo '<span>Playing</span>';
echo '<span>Remembering</span>';
echo '</div>';
echo '</div>';

if($updated !== '') {
  echo '<aside class="now-status" aria-label="Page status">';
  echo '<span>Last updated</span>';
  echo '<strong>'.htmlspecialchars($updated, ENT_QUOTES, 'UTF-8').'</strong>';
  echo '<em>Still becoming.</em>';
  echo '</aside>';
}

echo '</section>';

if(count($sections) > 0) {
  echo '<div class="now-sections">';

  foreach($sections as $index => $section) {
    $heading = trim($section['heading'] ?? '');
    $summary = trim($section['summary'] ?? '');
    $items = is_array($section['items'] ?? null) ? $section['items'] : [];

    if($heading === '' && $summary === '' && count($items) === 0) {
      continue;
    }

    $class = $index === 0 ? 'now-section now-section-featured' : 'now-section';
    echo '<section class="'.$class.'">';

    if($heading !== '') {
      echo '<h2><span>'.htmlspecialchars($heading, ENT_QUOTES, 'UTF-8').'</span></h2>';
    }

    if($summary !== '') {
      echo '<p class="now-section-summary">'.nl2br(htmlspecialchars($summary, ENT_QUOTES, 'UTF-8')).'</p>';
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

echo '</div>';
?>
