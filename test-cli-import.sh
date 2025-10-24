#!/bin/bash

# Test script for ConjureWP WP-CLI commands
# This script demonstrates how to use the CLI commands for automated imports

set -e

echo "=========================================="
echo "ConjureWP WP-CLI Command Test"
echo "=========================================="
echo ""

# Check if WP-CLI is available
if ! command -v wp &> /dev/null; then
    echo "Error: WP-CLI is not installed or not in PATH"
    exit 1
fi

# Check if WordPress is installed
if ! wp core is-installed 2>/dev/null; then
    echo "Error: WordPress is not installed"
    exit 1
fi

echo "✓ WP-CLI is available"
echo "✓ WordPress is installed"
echo ""

# List available demos
echo "=========================================="
echo "Step 1: Listing available demos"
echo "=========================================="
echo ""

wp conjure list

echo ""
echo "=========================================="
echo "Step 2: Running test import"
echo "=========================================="
echo ""

# Ask user if they want to proceed
read -p "Do you want to proceed with the import? (y/n) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Import cancelled"
    exit 0
fi

# Run the import
echo ""
echo "Starting import..."
echo ""

wp conjure import --demo=0

echo ""
echo "=========================================="
echo "Import completed successfully!"
echo "=========================================="
echo ""
echo "You can now visit your site to see the imported content."
echo ""

