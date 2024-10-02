# Google Sign-In & Collect WordPress Plugin

## Description

Google Sign-In & Collect is a WordPress plugin that allows you to create custom landing pages with Google Sign-In functionality and email collection capabilities. It's perfect for websites looking to streamline user registration and data collection using Google authentication.

## Features

- Create custom landing pages with Google Sign-In integration
- Collect and store user emails securely
- Redirect new users to a custom "Thank You" page
- Admin dashboard for managing collected emails
- Export collected emails as CSV
- Customizable templates for landing pages

## Installation

1. Download the plugin zip file.
2. Log in to your WordPress admin panel.
3. Navigate to Plugins > Add New.
4. Click on the "Upload Plugin" button.
5. Choose the downloaded zip file and click "Install Now".
6. After installation, click "Activate Plugin".

## Configuration

1. In the WordPress admin panel, go to "GS Collect" in the sidebar.
2. Navigate to the "Settings" tab.
3. Enter your Google Client ID and Client Secret.
4. Set the "Thank You Page URL" for new user redirections.
5. Save the settings.

## Usage

### Creating a Landing Page

1. In the WordPress admin, go to Pages > Add New.
2. Set the page template to "GSC Google-only Email Capture".
3. Publish the page.

### Viewing Collected Emails

1. In the WordPress admin, go to "GS Collect" in the sidebar.
2. Navigate to the "Collected Emails" tab.
3. Here you can view all collected emails and export them as CSV.

## Customization

You can customize the appearance of the landing page by modifying the CSS in the `standalone-email-capture-template.php` file located in the plugin's `templates` directory.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Support

For support, please create an issue on the plugin's GitHub repository or contact the plugin author.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Reda Essnoussi

## Changelog

### 1.0.0

- Initial release
