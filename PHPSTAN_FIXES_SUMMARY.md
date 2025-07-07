# PHPStan Fixes Summary

This document summarizes the changes made to fix PHPStan errors in the pivotphp-cycle-orm project.

## Issues Fixed

1. **Private/Protected Property Access**: Fixed direct access to private properties of `PivotPHP\Core\Http\Request`
2. **Type Mismatch**: Resolved issues with `CycleRequest` vs `Request` type compatibility
3. **Namespace References**: Updated old namespace references to `PivotPHP\Core`

## Files Modified

### 1. `/src/Health/HealthCheckMiddleware.php`
- Replaced direct property access (`$req->path`, `$req->pathCallable`) with public method `$req->getPathCallable()`
- Replaced direct query property access (`$req->query`) with public method `$req->get('detailed', false)`
- Fixed code style issue (removed space before semicolon)

### 2. `/src/Middleware/CycleMiddleware.php`
- Changed middleware to pass the original `Request` object instead of `CycleRequest` wrapper
- Added `cycleRequest` as an attribute on the original request for compatibility
- This ensures type compatibility with the middleware chain

### 3. `/src/Middleware/TransactionMiddleware.php`
- Removed `CycleRequest` type hints, now only accepts `Request`
- Removed unused `CycleRequest` import
- Updated `getRouteInfo()` method to use public methods `$req->getMethod()` and `$req->getPathCallable()`
- Simplified middleware logic by removing CycleRequest instance checks

### 4. `/phpstan.neon`
- Updated namespace patterns in configuration
- Updated `universalObjectCratesClasses` to `PivotPHP\Core\*` namespaces

## Result

All PHPStan errors have been resolved. The code now:
- Passes PHPStan analysis at Level 9 (highest level)
- Adheres to PSR-12 coding standards
- Properly uses public API methods instead of accessing private properties
- Has consistent type handling throughout the middleware chain