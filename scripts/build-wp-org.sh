#!/bin/bash

###############################################################################
# ConjureWP WordPress.org Build Script
#
# This script prepares a release ZIP for WordPress.org plugin repository.
# It builds assets, installs production dependencies, and creates the ZIP.
#
# Usage:
#   npm run build:wp
#
###############################################################################

set -e

# Colours for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Colour

PLUGIN_SLUG="conjurewp"
BUILD_DIR="build-wp-org"
DIST_DIR="dist"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}ConjureWP WordPress.org Build${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Clean previous builds
echo -e "${YELLOW}Cleaning previous builds...${NC}"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"
mkdir -p "$DIST_DIR"

# Build assets first
echo -e "${YELLOW}Building assets...${NC}"
npm run build

# Copy plugin files
echo -e "${YELLOW}Copying plugin files...${NC}"
rsync -a --progress ./ "$BUILD_DIR/$PLUGIN_SLUG/" \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='tests' \
    --exclude='examples' \
    --exclude='demo' \
    --exclude='docs' \
    --exclude='docs-api' \
    --exclude='scripts' \
    --exclude='build-wp-org' \
    --exclude='build-release' \
    --exclude='dist' \
    --exclude='.DS_Store' \
    --exclude='*.md' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='phpcs.xml' \
    --exclude='phpunit.xml' \
    --exclude='doctum.php' \
    --exclude='vite.config.js' \
    --exclude='generate-api-docs.sh' \
    --exclude='*.txt' \
    --exclude='assets/scss' \
    --exclude='test-count-fix.php' \
    --exclude='cli-fix-counts.php'

# Copy back essential files
echo -e "${YELLOW}Copying essential files...${NC}"
cp readme.txt "$BUILD_DIR/$PLUGIN_SLUG/"
cp LICENSE "$BUILD_DIR/$PLUGIN_SLUG/"

# Install production dependencies
echo -e "${YELLOW}Installing production Composer dependencies...${NC}"
# Copy composer files temporarily for installation
cp composer.json "$BUILD_DIR/$PLUGIN_SLUG/"
cp composer.lock "$BUILD_DIR/$PLUGIN_SLUG/"
cd "$BUILD_DIR/$PLUGIN_SLUG"
composer install --no-dev --optimize-autoloader --no-interaction --quiet
# Remove composer files from final build
rm composer.json composer.lock
cd ../..

# Create ZIP
echo -e "${YELLOW}Creating WordPress.org ZIP...${NC}"
cd "$BUILD_DIR"
zip -r "../$DIST_DIR/conjurewp.zip" "$PLUGIN_SLUG" -q
cd ..

# Get file size
FILE_SIZE=$(du -h "$DIST_DIR/conjurewp.zip" | cut -f1)

# Clean up build directory
echo -e "${YELLOW}Cleaning up...${NC}"
rm -rf "$BUILD_DIR"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Build Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "File:     ${YELLOW}$DIST_DIR/conjurewp.zip${NC}"
echo -e "Size:     ${YELLOW}$FILE_SIZE${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Test the ZIP file on a clean WordPress install"
echo "2. Extract and verify all files are present"
echo "3. Upload to WordPress.org SVN repository"
echo ""
echo -e "${GREEN}Ready for WordPress.org deployment!${NC}"

