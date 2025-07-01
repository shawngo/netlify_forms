# Netlify Forms for Drupal

[![Drupal 11](https://img.shields.io/badge/Drupal-11-blue)](https://www.drupal.org)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-green.svg)](https://github.com/shawngo/netlify_forms/graphs/commit-activity)

A Drupal 11 module that seamlessly integrates with Netlify's Forms API to provide real-time form submission management, automated data storage, and a beautiful client portal interface.

## Features

### **Real-Time Integration**
- **Webhook-powered** instant form submission storage
- **Zero API rate limits** with local database caching
- **Automatic sync** for historical submissions
- **Duplicate-proof** webhook handling

### **Multi-Client Management**
- **Customer portal** with role-based access control
- **Multi-site support** - manage multiple Netlify sites
- **Granular permissions** - control form access per customer
- **Secure isolation** - customers only see their own data

### **Advanced Data Export**
- **Smart CSV exports** with automatic batching
- **Progress tracking** for large datasets (500+ submissions)
- **Dynamic field detection** - exports all form fields automatically
- **Clean file naming** with timestamps

## Quick Start

### Installation

```bash
# Clone directly
git clone https://github.com/shawngo/netlify_forms.git modules/custom/netlify_forms

# 2. Enable the module
drush en netlify_forms -y

```

### Basic Setup

1. **Configure API Access**
   - Go to `Admin → Configuration → Web Services → Netlify Forms`
   - Add your [Netlify Personal Access Token](https://app.netlify.com/user/applications#personal-access-tokens)

2. **Create a Customer**
   - Navigate to `Admin → Content → Netlify Customers → Add Customer`
   - Link to a Drupal user account
   - Enter the Netlify site ID
   - Select which forms the customer can access

3. **Set Up Webhooks**
   - Visit the customer's form management page
   - Copy the provided webhook URL
   - In Netlify: `Settings → Forms → Form notifications → Add webhook`
   - Paste the URL and set event to "Form submission"

4. **Done!** Form submissions now flow in real-time to your Drupal database

## Configuration

### Required Permissions

| Permission | Description |
|------------|-------------|
| `Administer Netlify Forms` | Full system configuration access |
| `Manage Netlify Customers` | Create and manage customer accounts |
| `View own Netlify submissions` | Customer portal access |

### Webhook Setup

Each customer gets a unique webhook URL:
```
https://yoursite.com/webhooks/netlify/{site-id}
```

The module provides one-click copying and setup instructions on the admin interface.

### API Requirements

- **Netlify Personal Access Token** with `sites:read` and `forms:read` permissions
- **Webhook access** to your Drupal site (publicly accessible)

## Architecture

### Database Schema
- **Customer entities** store user-to-site relationships
- **Submissions table** stores form data with JSON flexibility
- **Smart indexing** for fast queries on large datasets

### Key Components
- **WebhookController** - Handles incoming Netlify webhooks
- **NetlifyApiService** - Manages Netlify API communication
- **Customer Portal** - Interface for end users
- **Admin Interface** - Comprehensive management tools

### Security Features
- **Input validation** on all webhook data
- **Access control** - users only access their own data
- **Webhook endpoint validation** - verifies customer/site relationships
- **Duplicate submission protection** via unique constraints

## Use Cases

### **Agency/Freelancer Workflow**
- Manage multiple client Netlify sites from one Drupal installation
- Provide clients with branded access to their form submissions
- Export data for client reporting and analysis

### **Enterprise Integration**
- Centralize form submissions from multiple marketing sites
- Integrate with existing Drupal-based business processes
- Maintain audit trails and submission history

### **Client Portals**
- White-labeled interface for client self-service
- Real-time submission notifications
- Historical data access and export capabilities

## Advanced Usage

### Custom Export Formats
```php
// Extend the export functionality
class CustomExportFormat extends NetlifyFormsController {
  public function exportSubmissionsXML($form_id) {
    // Your custom export logic
  }
}
```

### Webhook Extensions
```php
// Add custom processing to webhook handler
function mymodule_netlify_submission_received($submission, $customer) {
  // Send notifications, trigger workflows, etc.
}
```

### Multi-Site Customer Support
```php
// Extend customer entity for multiple sites
$customer->addSite($site_id, $permissions);
```

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup
```bash
# Clone the repository
git clone https://github.com/shawngo/netlify_forms.git

# Install dependencies
composer install

# Run tests
phpunit --configuration phpunit.xml
```

### Reporting Issues
- Use the [GitHub issue tracker](https://github.com/shawngo/netlify_forms/issues)
- Include Drupal version, PHP version, and detailed steps to reproduce
- Check existing issues before creating new ones

## 📋 Requirements

- **Drupal**: 11.0+
- **PHP**: 8.1+
- **MySQL**: 5.7+ (for JSON field support)
- **Netlify Account** with API access

## Support

- **Documentation**: [Full documentation](https://github.com/shawngo/netlify_forms/wiki)
- **Issues**: [GitHub Issues](https://github.com/shawngo/netlify_forms/issues)
- **Discussions**: [GitHub Discussions](https://github.com/shawngo/netlify_forms/discussions)

## License

This project is licensed under the GPL v2 License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Built for the Drupal community with ❤️
- Powered by [Netlify's excellent Forms API](https://docs.netlify.com/forms/setup/)

## Show Your Support

If this module helps your project, please give it a star! It helps others discover the project and motivates continued development.

---

**Made with Drupal** | **Powered by Netlify** | **Enhanced with Modern UX**
