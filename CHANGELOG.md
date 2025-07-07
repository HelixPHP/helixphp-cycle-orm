# Changelog

All notable changes to PivotPHP Cycle ORM will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-07-07

### 🎉 **Initial Stable Release**

First stable release of PivotPHP Cycle ORM integration, providing robust database ORM capabilities for the PivotPHP Framework.

#### Added
- **Cycle ORM Integration**: Complete integration with Cycle ORM for PivotPHP Framework
- **Service Provider**: `CycleServiceProvider` for seamless framework integration
- **Repository Pattern**: Built-in repository pattern support with custom repositories
- **Transaction Middleware**: Automatic transaction handling for requests
- **Entity Validation**: Middleware for entity validation with custom rules
- **Query Monitoring**: Performance monitoring and query logging capabilities
- **Health Checks**: Database health monitoring integration
- **Migration Support**: Schema migration tools and commands
- **Database Factory**: Support for multiple database connections
- **Type Safety**: Full type safety with PHPStan Level 9 compliance

#### Features
- **Multiple Databases**: Support for MySQL, PostgreSQL, SQLite, SQL Server
- **Relationships**: Full support for all Cycle ORM relationship types
- **Migrations**: Schema versioning and migration system
- **Factories**: Entity factories for testing and seeding
- **Events**: Database events and listeners
- **Caching**: Query result caching integration
- **Debugging**: Query debugging and profiling tools
- **Commands**: CLI commands for database operations

#### Technical Details
- **Namespace**: `PivotPHP\CycleORM`
- **Framework**: PivotPHP Core v1.0.0+
- **Cycle ORM**: v2.x compatibility
- **PHP**: 8.1+ with full 8.4 compatibility
- **Standards**: PSR-11, PSR-12 compliant
- **Testing**: Comprehensive test coverage

#### Performance
- **Optimized Queries**: Query optimization and caching
- **Connection Pooling**: Efficient database connection management
- **Lazy Loading**: Intelligent lazy loading of relationships
- **Memory Management**: Optimized memory usage for large datasets

#### Documentation
- Complete integration guide
- API reference documentation
- Performance optimization guide
- Migration from other ORMs
- Best practices and examples

#### CLI Commands
```bash
php vendor/bin/pivotphp cycle:entity User       # Create entity
php vendor/bin/pivotphp cycle:migrate          # Run migrations
php vendor/bin/pivotphp cycle:schema           # Update schema
php vendor/bin/pivotphp cycle:status           # Check status
```

#### Basic Usage
```php
use PivotPHP\Core\Core\Application;
use PivotPHP\CycleORM\CycleServiceProvider;

$app = new Application();
$app->register(new CycleServiceProvider());

// Use in routes
$app->get('/users', function (CycleRequest $request) {
    $users = $request->getRepository(User::class)->findAll();
    return $request->response()->json($users);
});
```

### 📋 Release Notes

This initial release provides a complete Cycle ORM integration for PivotPHP Framework, offering:

1. **Database Abstraction**: Work with multiple database systems
2. **Type Safety**: Full type safety and static analysis support
3. **Performance**: Optimized for high-performance applications
4. **Developer Experience**: Rich CLI tools and debugging capabilities
5. **Testing**: Comprehensive test coverage and factories

### 🔄 Future Roadmap

Future releases will focus on:
- Additional database drivers
- Enhanced performance optimizations
- Advanced caching strategies
- Extended CLI tooling
- Community-requested features

### 📞 Support

For questions, issues, or contributions:
- **GitHub**: [https://github.com/PivotPHP/pivotphp-cycle-orm](https://github.com/PivotPHP/pivotphp-cycle-orm)
- **Documentation**: [docs/](docs/)
- **Integration Guide**: [docs/integration-guide.md](docs/integration-guide.md)
- **Examples**: [examples/](examples/)

---

**Current Version**: v1.0.0  
**Release Date**: July 7, 2025  
**Stability**: Stable  
**Framework Requirement**: PivotPHP Core v1.0.0+