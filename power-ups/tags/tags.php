<?php

function jewitch_normalize_tags($tags): array {
  if(is_array($tags)) {
    $items = $tags;
  } else {
    $items = preg_split('/\s*,\s*/', (string)$tags);
  }

  $items = array_map('trim', $items);
  return array_values(array_filter($items, fn($tag) => $tag !== ''));
}

function jewitch_render_tags($tags): string {
  $tags = jewitch_normalize_tags($tags);
  if(count($tags) === 0) return '';

  $html = '<div class="post-tags" aria-label="Tags">';
  foreach($tags as $tag) {
    $label = htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');
    $html .= '<span class="post-tag">'.$label.'</span>';
  }
  $html .= '</div>';

  return $html;
}

add_hook('after_page', function (&$data) {
  $data['tags_html'] = isset($data['tags']) ? jewitch_render_tags($data['tags']) : '';
});
