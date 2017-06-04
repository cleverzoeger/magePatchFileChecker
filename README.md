# magePatchFileChecker
## additional Magento Security Checks because of parsing security patch provided by magentocommerce

# Introduction
Because many developer Duplicating files from core, base and co, some (security) patches don't affecting this files and security holes are not realy fixed. therefore this tool checks local and community files to find more affected files.
# Installation
Copy this php file directly into the magento shell directory.
# Usage
```bash
cd shell
php magePatchFileChecker.php ../PATCH-SUP..sh /var/www/magento1.x_docroot/
```
# Changelog
1.1.0
- code cleanup
- magento doc root parameter

1.0.0
- cli parameter cleanup
- sourcecode comments
- tool renaming

0.9.0
- Initial release
- check all files if there are duplicates
