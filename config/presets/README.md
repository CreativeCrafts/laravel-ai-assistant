# AI Assistant Configuration Presets

This directory contains simplified configuration presets to help you get started quickly with the Laravel AI Assistant package without being overwhelmed by the full 395-line configuration file.

## Available Presets

### 1. Simple (`simple.php`)

**Perfect for beginners and basic chat functionality**

- Minimal configuration with only essential settings
- Memory persistence (no database required)
- Basic retry logic with conservative settings  
- Streaming and advanced features disabled
- Ideal for development and simple use cases

**Key Features:**
- Chat completion with OpenAI models
- Basic error handling and logging
- Simple tool calling support
- No background jobs or complex monitoring

### 2. Advanced (`advanced.php`) 

**Full-featured configuration for power users**

- All available features enabled
- Eloquent persistence for data storage
- Comprehensive monitoring and metrics
- Background job processing
- Advanced streaming capabilities
- Full tool calling with parallel execution

**Key Features:**
- Complete feature set
- Production-ready monitoring
- Performance optimizations
- Webhook support
- Health checks and error reporting

### 3. Production (`production.php`)

**Optimized for production environments**

- Security-focused configuration
- Conservative resource limits
- Reliable defaults with fallbacks
- Comprehensive error reporting
- Performance monitoring enabled
- Cost-efficient model selection (gpt-4o-mini)

**Key Features:**
- Enhanced security settings
- Optimized for reliability over features
- Lower resource consumption
- Production-grade monitoring
- Stricter timeouts and limits

## How to Use Presets

### Option 1: Copy Configuration (Recommended)

1. Choose the preset that matches your needs
2. Copy the configuration from the preset file
3. Paste it into your published `config/ai-assistant.php` file
4. Customize as needed for your specific use case

```bash
# First, publish the config file if you haven't already
php artisan vendor:publish --tag="laravel-ai-assistant-config"

# Then copy from your chosen preset to config/ai-assistant.php
```

### Option 2: Environment Variables

All presets support environment variable overrides. You can use any preset as a base and override specific values in your `.env` file:

```env
# Required for all presets
OPENAI_API_KEY=your-api-key-here

# Override default model
OPENAI_CHAT_MODEL=gpt-4o

# Enable/disable features
AI_STREAMING_ENABLED=true
AI_METRICS_ENABLED=false
```

## Configuration Comparison

| Feature | Simple | Advanced | Production |
|---------|--------|----------|------------|
| Persistence | Memory | Eloquent | Eloquent |
| Streaming | Disabled | Enabled | Disabled |
| Background Jobs | Disabled | Enabled | Enabled |
| Metrics Collection | Disabled | Enabled | Enabled |
| Health Checks | Disabled | Enabled | Enabled |
| Tool Calling | Basic (sync) | Advanced (parallel) | Conservative (queue) |
| Error Reporting | Log only | Full featured | External service |
| Default Model | gpt-4o | gpt-4o | gpt-4o-mini |
| Memory Threshold | N/A | 256MB | 128MB |
| Max Connections | N/A | 100 | 50 |
| Webhook Support | Disabled | Enabled | Conditional |

## Migration Guide

### From Full Configuration to Preset

If you're currently using the full configuration file and want to simplify:

1. **Identify your usage patterns** - Are you using advanced features like streaming, webhooks, or background jobs?

2. **Choose the appropriate preset:**
   - Use **Simple** if you only need basic chat functionality
   - Use **Advanced** if you need all features
   - Use **Production** if you're deploying to production

3. **Backup your current configuration** before making changes

4. **Copy the preset configuration** and add back any custom settings you need

### From Preset to Custom Configuration

If you outgrow a preset:

1. **Publish the full configuration:**
   ```bash
   php artisan vendor:publish --tag="laravel-ai-assistant-config"
   ```

2. **Copy your current preset settings** as a starting point

3. **Enable additional features** as needed by consulting the full configuration file

## Environment-Specific Configurations

The package also supports environment-specific configurations in `config/environments/`:

- `development.php` - Development environment defaults
- `testing.php` - Testing environment defaults  
- `production.php` - Production environment defaults

These work alongside presets and follow this precedence order:
1. Runtime overrides (`.env` file, `config()` calls)
2. Environment overlay defaults
3. Base configuration (your chosen preset or full config)

## Tips for Success

1. **Start simple** - Begin with the Simple preset and add features as needed
2. **Use environment variables** - Keep sensitive data and environment-specific settings in `.env`
3. **Monitor in production** - The Production preset includes comprehensive monitoring
4. **Test thoroughly** - Always test configuration changes in a staging environment
5. **Keep backups** - Backup your configuration before making significant changes

## Support

For questions about configuration presets or the Laravel AI Assistant package:

- Check the main documentation
- Review the full configuration file for detailed parameter explanations
- Submit issues on the project repository
- Join the community discussions

---

*These presets are designed to simplify your initial setup while maintaining the flexibility to customize as your needs evolve.*