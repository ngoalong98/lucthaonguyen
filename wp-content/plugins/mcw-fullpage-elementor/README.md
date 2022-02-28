This plugin simplifies creation of fullscreen scrolling websites with WordPress/Elementor and saves you big time.

## Top Features
* Fully responsive.
* Touch support for mobiles, tablets, touch screen computers.
* Easy adding sections and slides.
* Full page scroll with optional visible scrollbar.
* Optional Auto-height sections.
* CSS3 and (optional) JS animations.
* Animated anchor links.
* Optional show/hide anchor links in the address bar.
* Optional vertically centered row content.
* Optional section and slide loops.
* Optional section only scrollbars.
* Optional keyboard support while scrolling.
* Optional history record. When this is enabled, browser back button will go to the previous section.
* Optional horizontal and vertical navigation bars with different styles.
* Optional responsive scrollbar. When responsive width and height given, a normal scroll page will be used under the given width and height values.
* TEMPLATES: You can use empty page templates or supply your own page template
* CSS and JS minified.

## How To Use

For the full documentation visit [documentation site](https://www.meceware.com/docs/fullpage-for-elementor/)

For parameters of fullpage.js, please visit [fullpage.js](https://github.com/alvarotrigo/fullpage.js).

* Create a new page/post.
* Add your content in Elementor sections. Each section is defined as FullPage section as well.
* To create slides, add Elementor Inner Section widgets inside sections. Each Inner Section widget is defined as slides if `Is Slides` option of the section is enabled.
* Adjust parameters of the sections.
* Adjust FullPage parameters from Page Settings.

## Credits

Thanks to [Álvaro Trigo](https://www.alvarotrigo.com/fullPage/) for awesome fullpage.js plugin.

## License

This plugin is comprised of two parts.

- The PHP code and integrated HTML are licensed under the General Public License (GPLv3). (Please see LICENSE)

- All other parts, but not limited to the CSS code, images, and design are licensed according to the terms of your purchased license.

### Commercial license
If you want to use this plugin to develop non open sourced sites, themes, projects, and applications, the Commercial license is the appropriate license. [Purchase a Commercial License](https://gum.co/fullPageForElementor)

### Open source license
If you are creating an open source application under a license compatible with the GNU GPL license v3, you may use this plugin under the terms of the GPLv3.

## Build

For production release, run `py release.py` script.

## How To New Release

- In mcw-fullpage-elementor.php,
  - Change the plugin version.
  - Change $version.
- In readme.txt,
  - Change Stable Tag version.
  - Update changelog section.
  - Check `Tested up to` section.
- Commit all files.
- Run `py release.py` in the plugin folder.
- Update Github Releases.
- Add the plugin file to the server.

## Code Samples

Add your code samples here!