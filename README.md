### Notes to self

Config currently coded to the [Cayman](https://github.com/pages-themes/cayman) theme, with minor CSS tweaks in `style.scss`

### To run locally:

`bundle exec jekyll serve`

### Data update and publising:

Controlled via GitHub Actions:

* Peloton data synced nightly at 00:10 UTC, and stored in `peloton.json`
* Site rebuild triggered at 00:15 UTC