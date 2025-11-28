#!/bin/bash

###############################################################################
# ConjureWP Release Build Script
#
# This script prepares a release ZIP for uploading to Freemius.
# It handles building assets, installing dependencies, and creating the ZIP.
#
# Usage:
#   ./scripts/build-release.sh 1.0.1
#
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if version argument provided
if [ -z "$1" ]; then
    echo -e "${RED}Error: Version number required${NC}"
    echo "Usage: ./scripts/build-release.sh 1.0.1"
    exit 1
fi

VERSION=$1
PLUGIN_SLUG="ConjureWP"
BUILD_DIR="build-release"
DIST_DIR="dist"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}ConjureWP Release Build Script${NC}"
echo -e "${GREEN}Version: $VERSION${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Clean previous builds
echo -e "${YELLOW}Cleaning previous builds...${NC}"
rm -rf "$BUILD_DIR"
rm -rf "$DIST_DIR"
mkdir -p "$BUILD_DIR"
mkdir -p "$DIST_DIR"

# Copy plugin files
echo -e "${YELLOW}Copying plugin files...${NC}"
rsync -a --progress ./ "$BUILD_DIR/$PLUGIN_SLUG/" \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='tests' \
    --exclude='examples' \
    --exclude='build-release' \
    --exclude='dist' \
    --exclude='.DS_Store' \
    --exclude='*.md' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='phpcs.xml' \
    --exclude='vite.config.js' \
    --exclude='scripts'

# Build assets
echo -e "${YELLOW}Building assets...${NC}"
if [ -f "package.json" ]; then
    npm install
    npm run build
    
    # Copy built assets
    if [ -d "build" ]; then
        cp -r build/* "$BUILD_DIR/$PLUGIN_SLUG/build/"
    fi
fi

# Install production dependencies
echo -e "${YELLOW}Installing production Composer dependencies...${NC}"
if [ -f "composer.json" ]; then
    cd "$BUILD_DIR/$PLUGIN_SLUG"
    composer install --no-dev --optimize-autoloader --no-interaction
    cd ../..
fi

# Create ZIP
echo -e "${YELLOW}Creating release ZIP...${NC}"
cd "$BUILD_DIR"
zip -r "../$DIST_DIR/conjurewp-$VERSION.zip" "$PLUGIN_SLUG" -q
cd ..

# Get file size
FILE_SIZE=$(du -h "$DIST_DIR/conjurewp-$VERSION.zip" | cut -f1)

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Build Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "Version:  ${YELLOW}$VERSION${NC}"
echo -e "File:     ${YELLOW}$DIST_DIR/conjurewp-$VERSION.zip${NC}"
echo -e "Size:     ${YELLOW}$FILE_SIZE${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Test the ZIP file on a clean WordPress install"
echo "2. Upload to Freemius Dashboard → Releases → Add New Version"
echo "3. Wait for Freemius to process (2-5 minutes)"
echo "4. Download and test both free and premium versions"
echo "5. Set release status to 'Released' when ready"
echo ""
echo -e "${GREEN}Happy deploying! 🚀${NC}"


