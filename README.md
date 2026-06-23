# WP Floating Contacts

A lightweight WordPress plugin that adds a floating contact widget to the frontend of a site.

The widget displays a main floating button and a vertical list of contact buttons for WhatsApp, Telegram, email, and phone. Icons are loaded from image files, colors can be configured from the WordPress admin panel, and the dropdown buttons are displayed as circular icon-only buttons.

## Features

- Floating contact widget on the frontend
- WhatsApp, Telegram, email, and phone contact buttons
- Icon-only dropdown buttons
- Circular buttons with the same size as the main button
- Custom main button color from the admin panel
- Custom dropdown button colors from the admin panel
- SVG icons stored in `assets/img/`
- Clean CSS without unnecessary `!important` rules
- Lightweight frontend JavaScript

## Installation

### Install from ZIP

1. Download the plugin ZIP archive.
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP file.
4. Activate the plugin.
5. Go to **Settings → Floating Contacts** and configure the widget.


## Settings

The settings page is available in:

```text
Settings → Floating Contacts
```

Available settings include:

- Enable or disable the widget
- Widget position
- Main button color
- WhatsApp contact value and button color
- Telegram contact value and button color
- Email contact value and button color
- Phone contact value and button color

## Icons

Icons are stored in:

```text
assets/img/
```

Current icon files:

```text
chat.svg
close.svg
whatsapp.svg
telegram.svg
email.svg
phone.svg
```

To replace an icon, keep the same file name and replace the SVG file in `assets/img/`.

## Styles

Frontend styles are stored in:

```text
assets/css/widget.css
```

The widget uses CSS variables for sizes and colors, for example:

```css
--wfc-size: 58px;
--wfc-mobile-size: 56px;
--wfc-icon-size: 26px;
```

Button colors are passed from PHP to CSS using custom properties.

