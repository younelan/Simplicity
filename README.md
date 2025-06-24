# Simplicity PHP Framework
This is *Simple* PHP Framework started as Login with templates. I am currently turning it into a more evolved framework to simplify dweb development. 

The framework is meant to centralize code I have used on other projects to maximize reuse and turbo charge development

**Primary Folders**:
- **src** directory: Simplicity Framework Files
    - **SimpleAuth**: a simple auth library for providing authentication
    - **SimpleTemplate**: a simple theme engine
    - **SimpleForm**: a simple form engine
    - **SimpleDebug**: a collapsible prettier alternative to print_r
    - **SimpleCache**: caches a file for a specified amount of time
    - **SimpleHttpRequest**: uses curl to fetch a file
    - **CSRF** : Simple CSRF class, see Qrcode demo for example 
- **src/plugins** directory: Plugins for the Simplicity Cms/Plugin Handler
    - **Block** - The basic element on a page is a block, there are multiple types of blocks
    - **TextBlock** - Specific block which provides text
    - **Section** - There can be multiple blocks in one section. Like header, content, footer
    - **Page** - A page is what eventually is served and contains multiple section
    - **Feed** - RSS feed plugin. Fetches and displays RSS Feeds
    - **Folder** - a Folder serves a folder as a route
    - **SimpleTemplate** - Template Provider using simple substitution
    - **TwigTemplate** - Template Provider using Twig as an engine
    - **Menu** - Creates menus
    - **ContentProvider** - Block providing a typical blog
    - **OSZContent** - Default content provider using the open site database as data source
    - **ImageMenu** - Image menu block

There are more complex frameworks but this is probably as simple as you can get to understand

## License
© 2025 **Youness El Andaloussi**

This **Simplicity Framework** is distributed under the terms of the **GNU Lesser General Public License**, Version 2 (LGPLv2). This license ensures that you can freely use this library in both **open-source** and proprietary projects, while also ensuring that any modifications to the library itself remain open. Please refer to the LICENSE-LGPLv2.md file for the full license text and important details, including the disclaimer of warranty.

When you contribute to the Simplicity Framework by submitting a patch, pull request, or any other form of contribution, you agree that your **code is contributed under the MIT License**. This allows us to integrate contributions easily while maintaining the LGPL licensing for the framework's core.

