#!/bin/bash

###############################################################################
# Example: ConjureWP CLI Integration for Automated Deployments
###############################################################################
#
# This script demonstrates how to integrate ConjureWP CLI commands into
# automated deployment workflows, CI/CD pipelines, or hosting provisioning.
#
# Usage:
#   ./example-cli-integration.sh [environment]
#
# Environments: development, staging, production
#
###############################################################################

set -e

# Configuration
ENVIRONMENT="${1:-development}"
WP_URL="${WP_URL:-http://localhost}"
WP_TITLE="${WP_TITLE:-My WordPress Site}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Helper functions
info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

# Check prerequisites
check_prerequisites() {
    info "Checking prerequisites..."
    
    if ! command -v wp &> /dev/null; then
        error "WP-CLI is not installed. Please install it first: https://wp-cli.org/"
    fi
    
    if ! wp core is-installed 2>/dev/null; then
        warn "WordPress is not installed. Will install now..."
        return 1
    fi
    
    return 0
}

# Install WordPress
install_wordpress() {
    info "Installing WordPress..."
    
    wp core install \
        --url="$WP_URL" \
        --title="$WP_TITLE" \
        --admin_user="$ADMIN_USER" \
        --admin_password="$ADMIN_PASSWORD" \
        --admin_email="$ADMIN_EMAIL" \
        --skip-email
    
    info "WordPress installed successfully"
}

# Install and activate theme
setup_theme() {
    local theme_slug="${THEME_SLUG:-my-theme}"
    
    info "Setting up theme: $theme_slug"
    
    if wp theme is-installed "$theme_slug" 2>/dev/null; then
        wp theme activate "$theme_slug"
        info "Theme activated: $theme_slug"
    else
        warn "Theme not found: $theme_slug"
        info "Please install your theme manually or via composer/npm"
    fi
}

# Install required plugins
setup_plugins() {
    info "Installing required plugins..."
    
    # Example plugins - adjust for your needs
    local plugins=(
        "contact-form-7"
        "wordpress-seo"
    )
    
    for plugin in "${plugins[@]}"; do
        if wp plugin is-installed "$plugin" 2>/dev/null; then
            info "Plugin already installed: $plugin"
        else
            info "Installing plugin: $plugin"
            wp plugin install "$plugin" --activate || warn "Failed to install: $plugin"
        fi
    done
}

# Import demo content using ConjureWP CLI
import_demo_content() {
    info "Checking available demo imports..."
    
    # List available demos
    if ! wp conjure list 2>/dev/null; then
        warn "No demo imports available or ConjureWP not properly configured"
        return 1
    fi
    
    # Import based on environment
    local demo_index="0"
    
    case "$ENVIRONMENT" in
        development)
            info "Importing development demo content..."
            wp conjure import --demo="$demo_index"
            ;;
        staging)
            info "Importing staging demo content..."
            wp conjure import --demo="$demo_index"
            ;;
        production)
            warn "Skipping demo import for production environment"
            return 0
            ;;
        *)
            warn "Unknown environment: $ENVIRONMENT. Skipping demo import."
            return 0
            ;;
    esac
    
    info "Demo content imported successfully"
}

# Configure WordPress settings
configure_wordpress() {
    info "Configuring WordPress settings..."
    
    # Permalink structure
    wp rewrite structure '/%postname%/' --hard
    
    # Timezone
    wp option update timezone_string 'America/New_York'
    
    # Discourage search engines (for non-production)
    if [ "$ENVIRONMENT" != "production" ]; then
        wp option update blog_public 0
    fi
    
    # Default ping status
    wp option update default_ping_status 'closed'
    wp option update default_comment_status 'closed'
    
    info "WordPress configured"
}

# Clean up default content
cleanup_default_content() {
    info "Cleaning up default WordPress content..."
    
    # Delete sample post
    wp post delete 1 --force 2>/dev/null || true
    
    # Delete sample page
    wp post delete 2 --force 2>/dev/null || true
    
    # Delete default comment
    wp comment delete 1 --force 2>/dev/null || true
    
    info "Default content cleaned up"
}

# Main deployment flow
main() {
    info "Starting automated WordPress deployment for: $ENVIRONMENT"
    info "Site URL: $WP_URL"
    echo ""
    
    # Check if WordPress is already installed
    if ! check_prerequisites; then
        install_wordpress
    else
        info "WordPress is already installed"
    fi
    
    # Setup theme
    setup_theme
    
    # Setup plugins
    setup_plugins
    
    # Import demo content (if not production)
    if [ "$ENVIRONMENT" != "production" ]; then
        import_demo_content || warn "Demo import failed or skipped"
    fi
    
    # Configure WordPress
    configure_wordpress
    
    # Clean up
    cleanup_default_content
    
    # Flush rewrite rules
    wp rewrite flush --hard
    
    # Clear cache if using cache plugin
    wp cache flush 2>/dev/null || true
    
    echo ""
    info "Deployment completed successfully!"
    info "Site URL: $WP_URL"
    info "Admin URL: $WP_URL/wp-admin"
    info "Admin User: $ADMIN_USER"
    echo ""
}

# Run main function
main

###############################################################################
# Example Usage:
###############################################################################
#
# 1. Development environment:
#    ./example-cli-integration.sh development
#
# 2. Staging with custom URL:
#    WP_URL=https://staging.example.com ./example-cli-integration.sh staging
#
# 3. Production (no demo import):
#    ./example-cli-integration.sh production
#
# 4. Docker Compose:
#    Add to docker-compose.yml:
#
#    services:
#      wordpress:
#        image: wordpress:latest
#        volumes:
#          - ./example-cli-integration.sh:/usr/local/bin/setup.sh
#        entrypoint: /usr/local/bin/setup.sh
#
# 5. GitHub Actions:
#    - name: Deploy WordPress
#      run: |
#        chmod +x example-cli-integration.sh
#        ./example-cli-integration.sh staging
#
# 6. AWS/DigitalOcean User Data:
#    #!/bin/bash
#    # Install WP-CLI
#    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
#    chmod +x wp-cli.phar
#    mv wp-cli.phar /usr/local/bin/wp
#    # Run deployment
#    cd /var/www/html
#    ./example-cli-integration.sh production
#
###############################################################################

