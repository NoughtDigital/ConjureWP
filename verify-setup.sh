#!/bin/bash

# ConjureWP Setup Verification Script
# This script verifies that your development environment is properly configured

echo "=========================================="
echo "  ConjureWP Setup Verification"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Track overall status
ALL_GOOD=true

# Check PHP version
echo "Checking PHP version..."
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    echo "  Found PHP $PHP_VERSION"
    
    PHP_MAJOR=$(echo $PHP_VERSION | cut -d "." -f 1)
    PHP_MINOR=$(echo $PHP_VERSION | cut -d "." -f 2)
    
    if [ "$PHP_MAJOR" -ge 7 ] && [ "$PHP_MINOR" -ge 4 ] || [ "$PHP_MAJOR" -ge 8 ]; then
        echo -e "  ${GREEN}✓${NC} PHP version is compatible (>= 7.4)"
    else
        echo -e "  ${RED}✗${NC} PHP version must be >= 7.4"
        ALL_GOOD=false
    fi
else
    echo -e "  ${RED}✗${NC} PHP is not installed"
    ALL_GOOD=false
fi
echo ""

# Check Node.js version
echo "Checking Node.js version..."
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v | cut -d "v" -f 2 | cut -d "." -f 1)
    echo "  Found Node.js v$NODE_VERSION"
    
    if [ "$NODE_VERSION" -ge 18 ]; then
        echo -e "  ${GREEN}✓${NC} Node.js version is compatible (>= 18)"
    else
        echo -e "  ${YELLOW}!${NC} Node.js version should be >= 18 (recommended)"
    fi
else
    echo -e "  ${RED}✗${NC} Node.js is not installed"
    ALL_GOOD=false
fi
echo ""

# Check npm
echo "Checking npm..."
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm -v)
    echo -e "  ${GREEN}✓${NC} npm $NPM_VERSION is installed"
else
    echo -e "  ${RED}✗${NC} npm is not installed"
    ALL_GOOD=false
fi
echo ""

# Check Composer
echo "Checking Composer..."
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer -V | cut -d " " -f 3)
    echo -e "  ${GREEN}✓${NC} Composer $COMPOSER_VERSION is installed"
else
    echo -e "  ${YELLOW}!${NC} Composer is not installed (optional but recommended)"
fi
echo ""

# Check if node_modules exists
echo "Checking Node dependencies..."
if [ -d "node_modules" ]; then
    echo -e "  ${GREEN}✓${NC} node_modules directory exists"
    
    # Check for key dependencies
    if [ -d "node_modules/vite" ]; then
        echo -e "  ${GREEN}✓${NC} Vite is installed"
    else
        echo -e "  ${RED}✗${NC} Vite is not installed"
        echo "      Run: npm install"
        ALL_GOOD=false
    fi
else
    echo -e "  ${RED}✗${NC} node_modules directory not found"
    echo "      Run: npm install"
    ALL_GOOD=false
fi
echo ""

# Check if vendor directory exists
echo "Checking PHP dependencies..."
if [ -d "vendor" ]; then
    echo -e "  ${GREEN}✓${NC} vendor directory exists"
    
    # Check for key dependencies
    if [ -d "vendor/monolog" ]; then
        echo -e "  ${GREEN}✓${NC} Monolog is installed"
    else
        echo -e "  ${RED}✗${NC} Monolog is not installed"
        echo "      Run: composer install"
        ALL_GOOD=false
    fi
else
    echo -e "  ${YELLOW}!${NC} vendor directory not found"
    echo "      Run: composer install"
fi
echo ""

# Check if built assets exist
echo "Checking built assets..."
ASSETS_MISSING=false

if [ -f "assets/css/conjure.min.css" ]; then
    echo -e "  ${GREEN}✓${NC} conjure.min.css exists"
else
    echo -e "  ${RED}✗${NC} conjure.min.css is missing"
    ASSETS_MISSING=true
fi

if [ -f "assets/js/conjure.min.js" ]; then
    echo -e "  ${GREEN}✓${NC} conjure.min.js exists"
else
    echo -e "  ${RED}✗${NC} conjure.min.js is missing"
    ASSETS_MISSING=true
fi

if [ "$ASSETS_MISSING" = true ]; then
    echo "      Run: npm run build"
    ALL_GOOD=false
fi
echo ""

# Check configuration files
echo "Checking configuration files..."
if [ -f "vite.config.js" ]; then
    echo -e "  ${GREEN}✓${NC} vite.config.js exists"
else
    echo -e "  ${RED}✗${NC} vite.config.js is missing"
    ALL_GOOD=false
fi

if [ -f "composer.json" ]; then
    echo -e "  ${GREEN}✓${NC} composer.json exists"
else
    echo -e "  ${RED}✗${NC} composer.json is missing"
    ALL_GOOD=false
fi

if [ -f "package.json" ]; then
    echo -e "  ${GREEN}✓${NC} package.json exists"
else
    echo -e "  ${RED}✗${NC} package.json is missing"
    ALL_GOOD=false
fi
echo ""

# Check if old gulpfile exists
echo "Checking for old build system files..."
if [ -f "gulpfile.js" ]; then
    echo -e "  ${YELLOW}!${NC} gulpfile.js still exists (should be deleted)"
else
    echo -e "  ${GREEN}✓${NC} gulpfile.js has been removed"
fi
echo ""

# PHP syntax check
echo "Checking PHP syntax..."
php -l conjurewp.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "  ${GREEN}✓${NC} conjurewp.php has no syntax errors"
else
    echo -e "  ${RED}✗${NC} conjurewp.php has syntax errors"
    ALL_GOOD=false
fi

php -l class-conjure.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "  ${GREEN}✓${NC} class-conjure.php has no syntax errors"
else
    echo -e "  ${RED}✗${NC} class-conjure.php has syntax errors"
    ALL_GOOD=false
fi
echo ""

# Final summary
echo "=========================================="
if [ "$ALL_GOOD" = true ]; then
    echo -e "${GREEN}✓ All checks passed!${NC}"
    echo ""
    echo "You can now run:"
    echo "  npm run build    - Build production assets"
    echo "  npm run dev      - Build development assets"
else
    echo -e "${RED}✗ Some checks failed${NC}"
    echo ""
    echo "Please fix the issues above before building."
fi
echo "=========================================="

