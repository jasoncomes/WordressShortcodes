# Composer Setup
```
"require": {
    "wordpressmeta": "dev-master"
},
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/jasoncomes/WordpressShortcodes"
    }
],
```

Install Meta Library project by using composer:
```
composer install
```

To Update:
```
composer update
```

If Composer is not installed on your machine, run:
```
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

Setup Autoloader in `functions.php`:
```
require_once ABSPATH . '../../vendor/autoload.php';
```


# Wordpress Shortcodes
Used to build shortcodes/html snippets on the fly.

### Features
* [Easy Initialization](#initialize)
* [Shortcodes](#shortcode)
* [HTML Snippets](#html-snippets)
* [Styleguide](#styleguide)
* [Styleguide - Fillers](#fillers)
* [Styleguide - Variations](#variations)
* [Styleguide - Waypoints Hooks & Jump Anchors](#waypoints-hooks-&-jump-anchors)
* [Frequently Asked Questions](#frequently-asked-questions)



## Initialize

**1.** Add to themes functions.php:

```
( new Admin\AdminShortcodes )->init();
```

A folder called `shortcode-templates` is created in your themes root. For better organization, feel free to add a one tier folder structure to break up your elements. e.g.

```
theme/shortcode-templates/
theme/shortcode-templates/buttons/
theme/shortcode-templates/elements/
theme/shortcode-templates/tables/
theme/shortcode-templates/modules/
```

**2.** Create Shortcode/HTML Snippets by adding new `.php` files to folder structure. e.g.

```
/shortcode-templates/btn-primary.php or /shortcode-templates/buttons/btn-primary.php
/shortcode-templates/rankings/badge.php
/shortcode-templates/rankings/school.php
/shortcode-templates/rankings/school_collection.php
```

**Note:** For developer use only, prepending **_** before file name will remove it from the WP Dropdown Button and Styleguide Template. Very useful for when breaking larger modules into smaller partials for cleaner, better organized code.


**3.** Insert into header of `.php` file, this registers the Shortcode/HTML Snippet, adds it to WP Dropdown Button and Styleguide Template.

```
<?php
  /*
  Title: [Shortcode|HTML Snippet Title]
  Shortcode: [Shortcode]
  HTML: [HTML Snippet]
  Styleguide: [Shortcode|HTML Snippet]
  Instructions: [Instructions|Usage|Information displayed on Styleguide]
  */

 [PHP Shortcode Logic]
?>

 [PHP/HTML/JS Shortcode Structure]

```

**Note:** For a Shortcode, fill out the `Shortcode` attribute. For HTML Snippet, fill out the `HTML` attribute. The PHP Logic and PHP/HTML/JS Structure are only used for Shortcodes.



## Shortcode

```
<?php
  /*
  Title: Did You Know
  Shortcode: [did_you_know title="" image='' link='' button_label='' float=""]Content Goes Here[/did_you_know]
  HTML:
  Styleguide:
  Instructions:
  */

  $float = ( ! empty( $float ) ? ' fl-' . $float : '' );

?>

<div class="element-didyouknow<?php echo $float; ?>">

  <img class="featured-image" src="<?php echo $image; ?>" />
  <h3 class="title"><?php echo $title; ?></h3>
  <p><?php echo $content; ?></p>
  <a class="link" href="<?php echo $link; ?>"><?php echo $button_label; ?></a>

</div>
```
> Flow: DocBlock(Information) > PHP Logic > HTML/JS/PHP Structure

All the shortcode attributes are ready to use as illustrated. Notice that some attributes have different quotation types around the values.

* `" "` indicates that it only accepts text values.
* `' '` indicates it can also accept HTML. Note: If the value starts and ends with `<img src="" />` or `<a href=""></a>` it will return only the url string. This is a fallback/enhancement, so when inserting media or links into the visual editor, things run smoothly. e.g.


```
image='http://domain.com/image.png' = http://domain.com/image.png
image='<img src="http://www.domain.com/image.png" />' = http://www.domain.com/image.png
image='<img src="/wp-content/wp-uploads/###/image.png" />' = http://www.domain.com/wp-content/wp-uploads/###/image.png
link='<a href="www.domain.com/">domain.com</a>' = http://www.domain.com/
link='http://domain.com/' = http://domain.com/
source='Data was brought to you by <a href="www.google.com/">google.com</a>' = Data was brought to you by <a href="http://www.google.com/">google.com</a>
```

Setting up attribute value choices will be piped like this:

```
active="true|false"
type="a|b|c|d"
style="default|dark|light"
```



## HTML Snippet

```
<?php
  /*
  Title: White Outline Button
  Shortcode:
  HTML: <a href="#" class="btn-secondary-white">White Outline Button</a>
  Styleguide:
  Instructions:
  */

?>
```
> Flow: Docblock


This injects HTML Snippets into the WP Content Editor, very useful if you have HTML markup that contains specific `classes`, `ids`, or `data attributes`. If your HTML contains PHP logic, please use a `Shortcode` instead.



## Styleguide

```
<?php
  /*
  Title: Did You Know
  Shortcode: [did_you_know title="" image='' link='' button_label='' float=""]Content Goes Here[/did_you_know]
  HTML:
  Styleguide: [did_you_know title="Stay Classy" image='image-300x300' link='http://www.daveismyhero.com' button_label='Read This Now' float=""]copy-40 ul-5-3[/did_you_know]
  Instructions:
  */

?>

```
The Styleguide template is initially created with the `Shortcode` or `HTML` attribute, but if you have a value in the `Styleguide` attribute this will overwrite them. Reason for this is to create a filled Styleguide Template that does not actually effect the working Shortcode or HTML Snippet. You'll also have the ability to add filler keywords(explained below) to fill out the `Shortcodes`, `HTML`, `Styleguide` attributes.



### Fillers

```
copy-# = copy-60 (Words)
image-#x# | image-#x#/###/### = image-300x500 or image-300x500/000000/FFFFFF (Size, Bg Color, Txt Color)
ul-# | ul-#-# = ul-5 or ul-5-3 (li Count - Words) 
ol-# | ol-#-# = ol-3 or ol-4-5 (li Count - Words)
```
Filler keywords are the lorem ipsum component of the shortcodes system. They help identifiy copy variations, image demensions/colors, and flow of the elements used on the Styleguide Template.



### Variations

```
<?php
  /*
  Title: Did You Know
  Shortcode: [did_you_know title="" image='' link='' button_label='' float=""]Content Goes Here[/did_you_know]
  HTML:
  Styleguide: [did_you_know title="Stay Classy" image='image-300x300' link='http://www.daveismyhero.com' button_label='Read This Now' float=""]copy-40 ul-5-3[/did_you_know]
  Styleguide_2: [did_you_know title="Stay Classy" image='' link='' button_label='Read This Now' float=""]copy-100[/did_you_know]
  Styleguide_3: [did_you_know title="Stay Classy" image='image-300x300' link='http://www.daveismyhero.com' button_label='Read This Now' float=""][/did_you_know]
  Instructions:
  */

?>
```
Element variations are a way to add different visual appearances of an element based on fallbacks, empty attribute fields, different attribute choice selections, element/copy lengths, and so ons. You may add up to 5 different variations of an element by adding `Styleguide_2`, `Styleguide_3`, ..., `Stylguide_5`.

**Note:** To hide from displaying on the Styleguide Template, add keyword `hidden` to the `Styleguide` attribute tag.



### Waypoints Hooks & Jump Anchors

Each element and parent folder in the `shortcode-templates` directory will have a santized ID assigned to it for quick jump links. If you site has a sub navigation you would like to hook into, there are Waypoint Hooks added to parent folders. e.g.

```
/shortcode-templates/buttons/ = <h2 class="waypoint" data-destination="wp-buttons" id="buttons">Buttons<a href="#buttons"><sup>⚓</sup></a></h2>
/shortcode-templates/buttons/btn-primary.php = <h3 id="primary-button">Primary Button<a href="#primary-button"><sup>⚓</sup></a></h3>
```



## Frequently Asked Questions

####In the DocBlock, can you have multi-line pre formatted code?
```
HTML: <table>
        <tr>
          <td>
            ...
```
No, the plugin is using the WordPress native function [get_file_data()](https://developer.wordpress.org/reference/functions/get_file_data/). Each piece of metadata must be on its own line. Fields can not span multiple lines, the value will get cut at the end of the first line.


####Is there a limit to how much information I can place in the DocBlock?
Yes, searches for metadata in the first 8kiB of a file. If the file data is not within that first 8kiB, then the author should correct their plugin file and move the data headers to the top.
