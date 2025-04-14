# Custom Attributes AIQ - Gravity Forms Webhooks

A WordPress plugin that enhances Gravity Forms webhooks by allowing you to designate specific form fields as custom attributes in your webhook payloads.

## Description

This plugin provides a simple admin interface to configure which Gravity Forms fields should be sent as custom attributes in your webhook payloads. It transforms your webhook data to include a structured `customAttributes` array, making it easier to integrate with external systems that support custom attributes.

### Key Features

- **Custom Attributes Configuration**: Easily specify which form fields should be sent as custom attributes through a user-friendly admin interface
- **Webhook Feed Integration**: Configure custom attributes on a per-webhook basis
- **Automatic Type Conversion**: Automatically converts `favoriteStoreID` to a proper integer in all webhook payloads
- **Data Transformation**: Moves specified fields from the root level to a structured `customAttributes` array in your webhook payload

## Installation

1. Download the plugin zip file or clone this repository
2. Upload the plugin folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

## Requirements

- WordPress 5.0 or higher
- Gravity Forms 2.4 or higher
- Gravity Forms Webhooks Add-On

## Usage

### Setting Up Custom Attributes

1. Go to **Settings > GF Custom Attributes** in your WordPress admin
2. Add a new feed configuration:
   - Enter the Webhook Feed ID (found in the URL when editing a webhook feed as the `fid` parameter)
   - Enter a comma-separated list of field keys that should be sent as custom attributes
3. Click "Save Settings"

### Example

If you configure the fields `website` and `bajolalunarsvp` as custom attributes for webhook feed ID `5`, your webhook payload will be transformed from:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "website": "https://example.com",
  "bajolalunarsvp": "yes"
}
```

to:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "customAttributes": [
    {
      "key": "website",
      "value": "https://example.com"
    },
    {
      "key": "bajolalunarsvp",
      "value": "yes"
    }
  ]
}
```

### Finding Your Webhook Feed ID

To find your webhook feed ID:
1. Go to your Gravity Forms Webhooks settings
2. Edit the webhook feed you want to configure
3. Look at the URL in your browser - the `fid` parameter is your feed ID
   - Example: In the URL `...&fid=5`, the feed ID is `5`

## Automatic Type Conversion

This plugin automatically converts the `favoriteStoreID` field to a proper integer in all webhook payloads. If the conversion fails or results in a value less than or equal to zero, the field will be removed from the payload.

## Changelog

### 1.0
- Initial release

## Author

deeproots partners

## License

This project is licensed under the GPL v2 or later - see the LICENSE file for details.
