# MOC WP Logger

A simple WordPress plugin for logging events, designed to help developers debug and monitor their WordPress sites more effectively.

## Description

MOC WP Logger enables easy and efficient logging of various events within WordPress. Whether you're debugging during development or monitoring your live site, this plugin provides a seamless way to log custom messages without interfering with the default `debug.log` file.

## Features

- **Custom Logging**: Log custom messages easily, keeping your debugging separate from WordPress's default logging system.
- **Flexible Integration**: Seamlessly integrates with any WordPress theme or plugin.
- **File-based Logging**: Logs are saved directly to a dedicated file within the `wp-content/moc-logs` directory, ensuring easy access and monitoring.

## Installation

1. **Download the Plugin**: Download the zip file from the [GitHub repository](#).
2. **Upload to WordPress**:
    - Navigate to `Plugins` > `Add New` > `Upload Plugin` in your WordPress dashboard.
    - Choose the downloaded zip file and click `Install Now`.
3. **Activate the Plugin**:
    - Click on `Activate Plugin` after installation.

Alternatively, manually install the plugin by extracting the zip file and uploading the `moc-wp-logger` folder to your WordPress installation's `wp-content/plugins` directory.

## Usage

To log a message, use the `moc_log` function within your WordPress site's code:

```php
moc_log('This is a debug message', 'DEBUG');
The moc_log function accepts two parameters:

$message (string): The message you want to log.
$level (string): The severity level of the message (e.g., 'INFO', 'DEBUG', 'ERROR'). The default is 'INFO'.
```

## Configuration
No additional configuration is required. Logs are stored by default in the wp-content/moc-logs directory. Ensure this directory is writable by WordPress.

## Contributing
Contributions are welcome! If you have a bug report, feature request, or a pull request, please feel free to contribute to the project by submitting an issue or pull request on GitHub.

## License
MOC WP Logger is open-sourced software licensed under the GPLv2 license.