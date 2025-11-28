#!/bin/bash

# Generate API documentation using Doctum
# Run this script after making changes to PHP files

echo "Generating ConjureWP API Documentation..."
php vendor/bin/doctum.php update doctum.php -v

if [ $? -eq 0 ]; then
    echo ""
    echo "Documentation generated successfully!"
    echo "Output: docs-api/build/"
    echo ""
    echo "JSON API for Next.js: docs-api/build/doctum-search.json"
    echo "HTML Documentation: docs-api/build/index.html"
else
    echo ""
    echo "Documentation generation failed!"
    exit 1
fi


