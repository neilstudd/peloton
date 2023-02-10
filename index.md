---
# Feel free to add content and custom Front Matter to this file.
# To modify the layout, see https://jekyllrb.com/docs/themes/#overriding-theme-defaults

layout: default
---

{% assign currentYear = "now" | date: "%Y" %}

{% capture total_mileage %}
{{ site.data.peloton.distanceCycled["Total"] }}
{% endcapture %}

{% capture peloton_rides %}
{{ site.data.peloton.workouts.Total.Cycling }}
{% endcapture %}

{% capture peloton_total_classes %}
{{ site.data.peloton.workouts.Total.Total }}
{% endcapture %}

{% capture peloton_latest_ride_instructor %}
{{ site.data.peloton.latestRide.Instructor }}
{% endcapture %}

## Statistics for DustLined

Here you can find all of my vital Peloton statistics - currently updated nightly.

I have taken a total of **{% include format-thousand-separators.html number=peloton_total_classes %}** Peloton classes, including {% include format-thousand-separators.html number=peloton_rides %} cycle rides (covering {% include format-thousand-separators.html number=total_mileage %} miles) and {{ site.data.peloton.workouts.Total.Meditation }} meditations.

<div style="border-radius: 25px; border: 2px solid #396; padding: 10px;">
<img src="{% include avatar.html instructor=peloton_latest_ride_instructor %}"  style="float: right; border-radius: 50%; padding-left: 10pt;"/>
<h2>Latest Ride</h2>
{% assign avgPerMin = site.data.peloton.latestRide['Total Output'] | plus: 0.0 | divided_by: site.data.peloton.latestRide.Length | round: 1 %}
<p><strong>{{ site.data.peloton.latestRide['Ride Name'] }} with {{ site.data.peloton.latestRide.Instructor }}</strong>
{% if site.data.peloton.latestRide['Live Ride'] %}
<span class="highlight">LIVE</span>
{% endif %}
{% if site.data.peloton.latestRide.Timestamp == site.data.peloton.PBs[site.data.peloton.latestRide.Length].Timestamp %}
<span class="highlight">🥇 PB</span>
{% endif %}
<br/>
{{ site.data.peloton.latestRide.Timestamp | date: "%-d %B %Y at %H:%M" }}<br/>
Total Output: {{ site.data.peloton.latestRide['Total Output'] }}kJ ({{ avgPerMin }}kJ/min)</p>
</div>
## Top 10 Cycling Instructors (All-Time)
{% for instructor in site.data.peloton.byInstructorAndDiscipline.Total.Cycling limit: 10 %}
<strong>{{ forloop.index }}: {{ instructor[0] }}</strong> ({{ instructor[1]}} classes)<br/>
{% endfor %}
## Top 10 Instructors of {{currentYear}}
{% for instructor in site.data.peloton.byInstructor[currentYear] limit: 10 %}
<strong>{{ forloop.index }}: {{ instructor[0] }}</strong> ({{ instructor[1]}} classes)<br/>
{% endfor %}
## Total Distance Cycled Per Year
<table>
{% for year in site.data.peloton.distanceCycled %}
    {% if year[0] == "Total" %}{% continue %}{% endif %}
    {% capture mileage %}
    {{ year[1] }}
    {% endcapture %}
    <tr>
        <td><strong>{{ year[0] }}</strong></td>
        <td>{% include format-thousand-separators.html number=mileage %} miles</td>
    </tr>
{% endfor %}
    <tr><td><strong>Total</strong></td><td><strong>{% include format-thousand-separators.html number=total_mileage %} miles</strong></td></tr>
</table>

## Time Spent Per Activity, Per Year
<table>
<tr><th>Year</th><th>Cycling</th><th>Running</th><th>Meditation</th><th>Total</th></tr>
{% for year in site.data.peloton.byTimeAndDiscipline %}
    {% if year[0] == "Total" %}
        {% continue %}
    {% endif %}
    {% capture cycleminutes ~%}{{ year[1]["Cycling"] }}{% endcapture ~%}
    {% capture runminutes ~%}{{ year[1]["Running"] }}{% endcapture ~%}
    {% capture meditationminutes ~%}{{ year[1]["Meditation"] }}{% endcapture ~%}
    {% capture totalminutes ~%}{{ year[1]["Total"] }}{% endcapture ~%}
    <tr>
        <td><strong>{{ year[0] }}</strong></td>
        <td>{% if cycleminutes != "" %}{% include minutes-to-hours.html number=cycleminutes %}{% endif %}</td>
        <td>{% if runminutes != "" %}{% include minutes-to-hours.html number=runminutes %}{% endif %}</td>
        <td>{% if meditationminutes != "" %}{% include minutes-to-hours.html number=meditationminutes %}{% endif %}</td>
        <td>{% if totalminutes != "" %}{% include minutes-to-hours.html number=totalminutes %}{% endif %}</td>
    </tr>
{% endfor %}
{% capture totalcycleminutes ~%}{{ site.data.peloton.byTimeAndDiscipline["Total"]["Cycling"] }}{% endcapture ~%}
{% capture totalrunminutes ~%}{{ site.data.peloton.byTimeAndDiscipline["Total"]["Running"] }}{% endcapture ~%} 
{% capture totalmeditationminutes ~%}{{ site.data.peloton.byTimeAndDiscipline["Total"]["Meditation"] }}{% endcapture ~%} 
{% capture totalminutes ~%}{{ site.data.peloton.byTimeAndDiscipline["Total"]["Total"] }}{% endcapture ~%} 
<tr>
    <td><strong>Total</strong></td>
    <td><strong>{% include minutes-to-hours.html number=totalcycleminutes %}</strong></td>
    <td><strong>{% include minutes-to-hours.html number=totalrunminutes %}</strong></td>
    <td><strong>{% include minutes-to-hours.html number=totalmeditationminutes %}</strong></td>
    <td><strong>{% include minutes-to-hours.html number=totalminutes %}</strong></td>
</tr>
</table>

## Personal Bests by Class Length
{% for distance in site.data.peloton.PBs %}
{% assign avgPerMin = distance[1]['Total Output'] | plus: 0.0 | divided_by: distance[0] | round: 1 %}
{% capture this_instructor %}
{{ distance[1].Instructor }}
{% endcapture %}
{% capture peloton_output %}
{{ distance[1]['Total Output'] }}
{% endcapture %}
<div style="border-radius: 25px; border: 2px solid #396; padding: 10px;">
<img src="{% include avatar.html instructor=this_instructor %}"  style="float: right; border-radius: 50%; padding-left: 10pt"/>
<h2>{{ distance[0] }}min PB:</h2>
<p><strong>{{ distance[1]['Ride Name'] }}{% if distance[1].Instructor != "N/A" %} with {{ distance[1].Instructor }}{% endif %}</strong>
{% if distance[1]['Live Ride'] %}
<span class="highlight">LIVE</span>
{% endif %}
<br/>
{{ distance[1].Timestamp | date: "%-d %B %Y at %H:%M" }}<br/>
Total Output: <strong>{% include format-thousand-separators.html number=peloton_output %}kJ</strong> ({{ avgPerMin }}kJ/min)</p>

<details>
<summary>View {{ distance[1]['progressions'] | size }} previous {{ distance[0] }}min PBs</summary>
<table>
{% for progress in distance[1]['progressions'] %}
{% capture pr_output %}
{{ progress['Total Output'] }}
{% endcapture %}
<tr><td>{{ progress.Timestamp | date: "%d/%m/%y" }}</td><td><strong>{% include format-thousand-separators.html number=pr_output %}kJ</strong></td><td>{%if progress.Instructor != "N/A" %}{{progress.Instructor}}{% endif %} {{progress['Ride Name']}} {% if progress['Live Ride'] %}<span class="highlight">LIVE</span>{% endif %}</td></tr>
{% endfor %}
</table>
</details>
</div>
<br/>
{% endfor %}

## Years Gone By

Check out my previous years' [Peloton Cooldown summary videos](/peloton/cooldown)!

## The science part

One of the downsides of Peloton is that they don't have the concept of a "public profile" to show-off your stats; it's one of the ways that they get you to buy into their ecosystem. ("Want to see stats? Sign up then!")

So, I built a stack to game the system a bit:
* I created a webpage (not this one) which, when loaded, connects to my Peloton account, downloads a huge CSV of all my workouts, extracts key information (including, for instance, calculating all of my personal bests) and outputs the result in a chunk of JSON.
* I created a GitHub action (well, I utilised [this one](https://github.com/TheLastProject/keep-remote-file-locally-up-to-date-action)) to connect to my page, read the JSON, and detect if it's different to the last version that it saw. If there are differences, it saves the JSON into a static file in my GitHub repo.
* When serving this site (and specifically rendering this page), Jekyll reads the data out of the static file. (GitHub Pages forbids the use of random gems, otherwise I could've just used [this one](https://github.com/brockfanning/jekyll-get-json) and bypassed the need for a static file.)