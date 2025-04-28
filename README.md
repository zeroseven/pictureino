# Picturerino 🦏

Picturerino is a zero-configuration TYPO3 extension that automatically optimizes image delivery. Without any manual setup, it intelligently calculates and delivers images in their optimal dimensions by analyzing viewport sizes and usage patterns. The extension learns from request patterns and automatically adapts its image size calculations, ensuring each image is delivered in the most efficient size for its specific use case. This fully automated approach eliminates the need for manual image size configurations while maintaining optimal performance and visual quality across all devices.

## Usage Examples

Simple image integration in your fluid template.

```html
<html xmlns:picturerino="http://typo3.org/ns/Zeroseven/Picturerino/ViewHelpers" data-namespace-typo3-fluid="true">

<!-- Simple image in the orginal aspect ratio -->
<picturerino:image src="{image}" />

<!-- Verious aspect ratios on different breakpoints -->
<picturerino:image src="{image}" aspectRatio="{600:'16:9', 1200:'2:1'}" />

<!-- The image will be delivered in any aspect ratio, depends on it's size in the frontend -->
<picturerino:image src="{image}" freeAspectRatio="1" />

</html>
```

## Installation

```bash
composer require zeroseven/picturerino
```

## Configuration

...

## Commands

### Cleanup processed images

Remove all images of dynamic image requests and cleanup the picturerino database entries:

```bash
vendor/bin/typo3 picturerino:cleanup
```
