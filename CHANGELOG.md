# Changelog

All notable changes to HelixPHP Cycle ORM will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-07

### 🎉 Initial Release of HelixPHP Cycle ORM

First stable release of HelixPHP Cycle ORM integration, marking the rebrand from the previous namespace to HelixPHP.

### Added

#### Core Integration
- Complete Cycle ORM integration with HelixPHP Core
- CycleServiceProvider for seamless setup
- CycleRequest class extending HelixPHP Request with ORM capabilities
- Repository factory with type-safe implementations
- Entity manager integration for persistence

#### Middleware Suite
- **TransactionMiddleware**: Automatic transaction wrapping for routes
- **EntityValidationMiddleware**: Request validation against entity rules
- **HealthCheckMiddleware**: Database health monitoring
- **CycleMiddleware**: Core middleware for ORM integration

#### Monitoring & Performance
- **QueryLogger**: Track and analyze database queries
- **PerformanceProfiler**: Profile database operations
- **MetricsCollector**: Gather performance statistics
- **CycleHealthCheck**: Monitor database connection health

#### CLI Commands
- `cycle:entity` - Generate entity classes
- `cycle:migrate` - Run database migrations
- `cycle:schema` - Update database schema
- `cycle:status` - Check database status

#### Developer Experience
- Zero-configuration setup with sensible defaults
- Type-safe repository pattern
- Comprehensive helper functions
- Integration with HelixPHP's validation system

### Changed
- **Namespace Migration**: Changed from CAFernandes\ExpressPHP to Helix\CycleORM
- **Package Name**: Now `helixphp/cycle-orm`
- **Dependencies**: Updated to use `helixphp/core` instead of `cafernandes/express-php`
- **PHP Version**: Requires PHP 8.1+

### Testing
- 67 comprehensive tests covering all features
- Unit, feature, and integration test suites
- Mock implementations for testing
- Test helpers and utilities

### Documentation
- Complete integration guide
- Quick reference documentation
- Example implementations
- Migration guide from previous versions

### Quality
- PHPStan Level 9 compliance
- PSR-12 code style
- Comprehensive type coverage
- Performance optimized

---

## Previous History

### [1.1.0] - Previous Namespace
- Added complete integration guide
- PHP 8.4 compatibility updates
- PSR-12 compliance improvements
- CRUD examples

### [1.0.0] - Previous Namespace
- Initial release under previous namespace
- Basic Cycle ORM integration
- Core middleware implementation