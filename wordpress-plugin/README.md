# Scenario Lager - WordPress Plugin

Equipment checkout and storage management system for TeaterScenario.

## Installation

1. Upload `scenario-lager-plugin-v5.zip` to WordPress via Plugins
2. Activate the plugin
3. The plugin will automatically create the necessary database tables

## Setup

1. Create a new WordPress page called "Lager" (or any name)
2. Set the page slug to `/lager` (or your preferred URL)
3. Add the shortcode `[scenario_lager]` to the page content
4. Publish the page

## Usage
Visit `http://multipartner.dk/lager` (or your chosen URL) to access the warehouse system.

**Initial Login credentials:**
- Username: `admin`
- Password: `admin123`

After logging in, you can:
- View all inventory items
- Search items by name or ID
- Check out items (mark as "in use")
- Return items
- View item history and details

## Features

- **Authentication**: Secure login system with session management
- **Inventory Management**: View and search all items
- **Checkout System**: Track who is using items and for how long
- **Item History**: Complete history of all checkouts and returns
- **Responsive Design**: Clean, modern interface matching the Python version
- **"Scenario" Branding**: Red "o" in logo

## Security Note

The default credentials (`admin`/`admin123`) should be changed in a production environment by modifying the authentication logic in `includes/shortcodes.php`.
