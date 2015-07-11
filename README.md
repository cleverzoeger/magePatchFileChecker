# magePatchFileChecker
## additional Magento Security Checks because of parsing security patch provided by magentocommerce

# Introduction
Because many developer Duplicating files from core, base and co, some (security) patches don't affecting this files and security holes are not realy fixed. therefore this tool checks local and community files to find more affected files.
# Usage
```bash
magePatchFileChecker.php ../PATCH-SUP..sh
```
# Changelog
1.0.0
- cli parameter cleanup
- sourcecode comments
- tool renaming
0.9.0
- Initial release
- check all files if there are duplicates
