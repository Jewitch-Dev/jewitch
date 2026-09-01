---
layout: layouts/page.njk
permalink: /now/index.html
title: Now
description: A brief glimpse into the parts that make up the whole we call the Jewitch!
created: "2026-06-15 22:35:48"
modified: "2026-06-24 02:45:00"
updated: June 2026
uuid: 0b49af2c-9ad7-42ff-8caf-42a9a7952943
sections:
  - heading: In this season
    summary: "Unpacking my identity one piece at a time, and cleaning up a decade and a half of neglect."
    items:
      - Building Jewit.ch up the way I truly envision. Learning new skills along the way.
      - Letting rest count as real work
      - Writing from where I am now post stroke. Embracing the rough edges and accepting my imperfections.
  - heading: Playing
    summary: What digital worlds am I calling home at the moment
    items:
      - Final Fantasy XI
      - Final Fantasy XIV
      - Guild Wars (Its on Mobile now! I can get lost in Tyria on the go now!)
  - heading: Building
    summary: My contributions to de-shitifying the Net
    items:
      - Jewitch, this small corner of the internet
      - Seeing what can be integrated into the Neato Alpha! 
  - heading: Watching
    summary: Revisiting stories that can sit beside me while I think.
    shows:
      - title: The Closer
        image: /assets/tv/The Closer.jpg
      - title: The West Wing (the 5,000th rewatch)
        image: /assets/tv/The West Wing.jpg
      - title: The X-Files (Attempting another rewatch)
        image: /assets/tv/The X-Files.jpg
      - title: Murder, She Wrote
        image: /assets/tv/Murder She Wrote.jpg
  - heading: Listening To
    summary: The voices that are currently guiding me through the wastelands
    albums:
      - title: Trouble in Shangri-La
        artist: Stevie Nicks
        image: /assets/albums/Trouble in Shangri-La Stevie Nicks.jpg
      - title: Say You Will
        artist: Fleetwood Mac
        image: /assets/albums/Say You Will Fleetwood Mac.jpg
      - title: Fumbling Towards Ecstasy
        artist: Sarah McLachlan
        image: /assets/albums/Fumbling Towards Ecstasy Sarah McLachlan.jpg
      - title: Surfacing
        artist: Sarah McLachlan
        image: /assets/albums/Surfacing Sarah McLachlan.jpg
---
<div class="now-page">
  <section class="now-hero">
    <div class="now-hero-copy">
      <p class="now-kicker">Current Dispatch</p>
      <h1>{{ title }}</h1>
      <p class="now-description">{{ description }}</p>
      <div class="now-pills" aria-label="Current themes">
        <span>Writing</span>
        <span>Resting</span>
        <span>Playing</span>
        <span>Remembering</span>
      </div>
    </div>
    <aside class="now-status" aria-label="Page status">
      <span>Last updated</span>
      <strong>{{ updated }}</strong>
      <em>Still becoming.</em>
    </aside>
  </section>

  <div class="now-sections">
    {% for section in sections %}
      <section class="now-section{% if loop.first %} now-section-featured{% endif %}">
        <h2><span>{{ section.heading }}</span></h2>
        {% if section.summary %}<p class="now-section-summary">{{ section.summary }}</p>{% endif %}

        {% if section.shows %}
          <div class="now-show-grid">
            {% for show in section.shows %}
              <figure class="now-show-card">
                {% if show.image %}<img src="{{ show.image | url }}" alt="{{ show.title }} show art">{% endif %}
                <figcaption><strong>{{ show.title }}</strong></figcaption>
              </figure>
            {% endfor %}
          </div>
        {% endif %}

        {% if section.albums %}
          <div class="now-album-grid">
            {% for album in section.albums %}
              <figure class="now-album-card">
                {% if album.image %}<img src="{{ album.image | url }}" alt="{{ album.title }} album cover">{% endif %}
                <figcaption>
                  <strong>{{ album.title }}</strong>
                  {% if album.artist %}<span>{{ album.artist }}</span>{% endif %}
                </figcaption>
              </figure>
            {% endfor %}
          </div>
        {% endif %}

        {% if section.items %}
          <ul>
            {% for item in section.items %}<li>{{ item }}</li>{% endfor %}
          </ul>
        {% endif %}
      </section>
    {% endfor %}
  </div>
</div>
