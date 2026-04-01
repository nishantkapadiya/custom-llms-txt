# Custom LLMS.txt Generator

A lightweight, powerful WordPress plugin to dynamically generate and manage your site's `llms.txt` file. This helps Large Language Models (LLMs) and AI crawlers understand your site structure and discover your high-quality content more efficiently.

---

## 🌟 Key Features

- **Dynamic & Static Generation**: Serve your `llms.txt` dynamically on every request or generate a static file in your root directory for maximum performance.
- **Customizable Content**: Define a custom heading and description for your `llms.txt` file directly from the WordPress admin.
- **Post Type Support**: Select exactly which post types (Posts, Pages, and custom post types) should be included in the generated file.
- **AI-Friendly Format**: Generates content in a clean, human-readable, and machine-parsable Markdown format.
- **Permalink Compatibility**: Seamlessly integrates with WordPress permalinks and provides built-in support for the "Permalink Manager" plugin.
- **Easy Integration**: Automatically handles rewrite rules to serve your file at `/llms.txt`.

---

## 🚀 Installation

1.  **Upload**: Upload the `custom-llms-txt` folder to your `/wp-content/plugins/` directory.
2.  **Activate**: Navigate to the 'Plugins' menu in WordPress and activate **Custom LLMS.txt Generator**.
3.  **Configure**: Go to the new **LLMS.txt** menu item in your WordPress admin sidebar.
4.  **Save**: Select your desired post types, add a heading/description, and click **Save Settings**.
5.  **Verify**: Visit `yourdomain.com/llms.txt` to see your generated file in action.

---

## 🛠️ Configuration

### Settings Page
The plugin provides a dedicated settings page under `LLMS.txt` in the admin menu:

- **Heading**: The main title of your `llms.txt` file (e.g., your site name).
- **Description**: A brief summary or instructions for LLMs crawling your site.
- **Select Post Types**: Toggle checkboxes for each public post type you want to expose to AI crawlers.

### Manual Generation
If you prefer serving a static file, click the **Generate llms.txt** button on the settings page. This will create or update a physical `llms.txt` file in your WordPress installation's root directory.

---

## 🔧 Technical Details

- **Rewrite Rules**: The plugin adds a custom rewrite rule for `^llms\.txt$`. It automatically flushes these rules upon activation and deactivation.
- **Fallback Logic**: If the static `llms.txt` file doesn't exist, the plugin dynamically generates the content on the fly when `/llms.txt` is requested.
- **Hooks**: Uses standard WordPress `get_posts` and permalink functions, ensuring compatibility with most themes and SEO plugins.

---

## 📄 License

This plugin is licensed under the GNU General Public License v2 or later.

---
