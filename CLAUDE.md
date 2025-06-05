# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Testing Commands

This Laravel package uses Orchestra Testbench for testing. Use these commands:

**Run all tests:**
```bash
vendor/bin/phpunit
```

**Run specific test file:**
```bash
vendor/bin/phpunit tests/Unit/Pic/PicForceDeleteTest.php
```

## Package Architecture

### Core Concepts

This is a **Laravel 11+ attachment package** that handles file and image uploads with polymorphic relationships. It provides two main components:

- **File Component**: Generic file handling for any file type
- **Pic Component**: Specialized image handling with automatic resizing and thumbnail generation

### File vs Pic Differences

| Aspect | File | Pic |
|--------|------|-----|
| **Table** | `files` | `pics` |
| **Morphic Prefix** | `fileable_*` | `picable_*` |
| **Processing** | Basic storage | Image resizing + thumbnails |
| **URL Generation** | Download only | Multiple size variants |
| **Allowed Types** | Most files (configurable) | Images only (jpg, jpeg, png, gif) |

### Key Architecture Patterns

1. **Interface-Driven Design**: All components implement contracts/interfaces found in `Contracts/` directories
2. **Repository Pattern**: Separate classes for `Creator`, `Deleter`, `Setter` operations
3. **Strategy Pattern**: Different upload strategies (`StorageUploader`, `UrlUploader`) and resizing strategies (`PicFitResizer`, `PicReduceResizer`)
4. **Polymorphic Relationships**: Both File and Pic support morphic relations via traits

### Storage Path Structure

Files are organized using this pattern:
```
{type}/{dir1}/{dir2}/{dir3}/{filename}
Example: pic/00/00/00/01-97b293386532c2b0.jpg
```

Where:
- `{type}` = "file" or "pic"
- `{dir1}/{dir2}/{dir3}` = derived from zero-padded ID (00000001 → 00/00/00)
- `{filename}` = `{last2digits}-{md5hash}.{extension}`

### Model Integration

Add these traits to your models:

```php
// For file attachments
use FF\Attachment\Relations\CanRelateFile;

// For image attachments  
use FF\Attachment\Relations\CanRelatePic;
```

### Scale Sizes Configuration

When working with Pic models, scale sizes are determined by:
1. If `picable_attr` exists: Look for `getScaleSizes()` method on the related model's attribute
2. If no `picable_attr`: Use the related model's `getScaleSizes()` static method
3. Fallback: Use the Pic model's default `getScaleSizes()`

This is critical for `forceDelete()` operations to properly clean up all image variants.

### Testing Environment

Tests use Orchestra Testbench with a workbench environment:
- Test storage path: `workbench/storage/`
- Fake storage disks for isolated testing
- `RefreshDatabase` trait for clean test state
- Service provider bindings configured per test

### Development Workflow

1. **Package Development**: Use the workbench environment (`composer serve`)
2. **Testing**: Always test both Unit and Browser test suites
3. **Storage**: Configure `attachment.upload_disk` in your application config
4. **Dependencies**: Ensure proper service provider bindings when customizing implementations

### Service Provider Patterns

The `AttachmentProvider` uses sophisticated dependency injection:
- Interface-to-implementation bindings
- Contextual bindings with `when()->needs()->give()`
- Parameter injection for class names
- Automatic API route registration

### API Endpoints

The package automatically registers these routes:
- `POST /api/file/upload` - File upload
- `GET /api/file/download/{id}` - File download
- `ANY /api/file/delete` - File deletion
- `ANY /api/file/rename` - File renaming
- `POST /api/pic/upload` - Image upload
- `ANY /api/pic/delete` - Image deletion