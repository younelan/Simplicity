# Simplicity PHP Framework
This is *Simple* PHP Framework started as Login with templates. I am currently turning it into a more evolved framework to simplify dweb development. 

The framework is meant to centralize code I have used on other projects to maximize reuse and turbo charge development

**Primary Folders**:
- **lib** directory: Simplicity Framework Files
    - **SimpleAuth**: a simple auth library for providing authentication
    - **SimpleTemplate**: a simple theme engine
    - **SimpleForm**: a simple form engine
    - **SimpleDebug**: a collapsible prettier alternative to print_r
    - **SimpleCache**: caches a file for a specified amount of time
    - **SimpleHttpRequest**: uses curl to fetch a file
    - **CSRF** : Simple CSRF class, see Qrcode demo for example 
- **src/plugins** directory: Plugins for the Simplicity Cms/Plugin Handler
    - ** Block ** - The basic element on a page is a block, there are multiple types of blocks
    - ** TextBlock ** - Specific block which provides text
    - ** Section ** - There can be multiple blocks in one section. Like header, content, footer
    - ** Page ** - A page is what eventually is served and contains multiple section
    - ** Feed ** - RSS feed plugin. Fetches and displays RSS Feeds
    - ** Folder ** - a Folder serves a folder as a route
    - ** SimpleTemplate ** - Template Provider using simple substitution
    - ** TwigTemplate ** - Template Provider using Twig as an engine
    - ** Menu ** - Creates menus
    - ** ContentProvider - Block providing a typical blog
    - ** OSZContent ** - Default content provider using the open site database as data source
    - ** ImageMenu ** - Image menu block

There are more complex frameworks but this is probably as simple as you can get to understand

## License
(c) Youness El Andaloussi
MIT License, no warranty applies

## contents
This repository contains a simple demo of a login zone with a simple template engine

## files
- SimpleTemplate.php - a simple theme engine - basically substitutes variables in the template. If you need something sophisticated, I recommend either tinybutstrong or smarty
- **SimpleAuth.php** - simple auth class
- **base**: a base controller/plugin framework

- **SimpleDebug.php** - simple debug class to print arrays in a much more user Friendly Format
    - require SimpleDebug.php
    - $debug = new SimpleDebug()
    - $debug-> printArray($array)
- **Auth.php** - a demo of the auth clas in use, basically include and $auth->require_login() will password protect the page
- **index.php** - example of using SimpleAuth for simple password protected, supports templates

- **editpasswd.php** - Edit your password form, in progress
- **SimpleForm.php** - Needs update, here mostly until I extract a more modern version. a simple forms generation engine with the option to validate input
- **CompiledTemplate.php** - experimental Template that compiles templates to php and handles recursive blocks for more complex scenarios. Works but needs to be vetted
