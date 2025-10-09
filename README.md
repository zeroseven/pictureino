# Pictureiño – when your images size themselves

**Responsive Images Done Right in TYPO3:**

Pictureiño intelligently calculates the **ideal image dimensions** based on real viewport sizes and usage patterns. It learns from request data over time and adapts its sizing logic to serve only what’s needed – no more, no less. Images are delivered in **pixel-perfect dimensions**, as **modern WebP**, with **lazy loading** and **retina support** out of the box.

This fully automated approach eliminates the need for manual configuration, **improves PageSpeed and Core Web Vitals**, and **boosts SEO** – all while maintaining maximum visual quality across devices.

## How does it work?

Responsive images that think for themselves: Instead of pre-calculating image sizes server-side, a small fallback image is delivered that analyzes the viewport size and device characteristics (like pixel density) in the frontend. Based on this data, the image sends a request to TYPO3, which calculates the optimal size. The image is then processed and seamlessly replaced with the correct size, ensuring optimal performance and visual quality on the fly.

This entire process happens dynamically, without the need for any manual configuration, making it seamless for developers and highly efficient for end users.

## Usage Examples

Simple image integration in your fluid template.

```html
<html xmlns:pictureino="http://typo3.org/ns/Zeroseven/Pictureino/ViewHelpers" data-namespace-typo3-fluid="true">

    <!-- Simple image in the orginal aspect ratio -->
    <pictureino:image src="{image}" />

    <!-- All image attributes are available -->
    <pictureino:image src="{image}" class="image" alt="cute cats" style="width: 50%" title="😻" />

    <!-- Use different aspect ratios on different breakpoints -->
    <pictureino:image src="{image}" aspectRatio="{768: '4:3', 992: '16:9'}" />

    <!-- … or use your defined breakpoint variabels instead -->
    <pictureino:image src="{image}" aspectRatio="{'md': '4:3', lg: '16:9'}" />

    <!-- The image will be delivered in any aspect ratio, depends on it's size in the frontend -->
    <pictureino:image src="{image}" freeAspectRatio="1" />

</html>
```

## Renderer example

```html
<!-- Initial in DOM -->
<pictureino-wrap data-loading data-config="bHhHOXJqZXRzUDg5NVrbTdWQ1E1WFRoeHZuUnM4PQ">
    <img src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDABQODxIPDRQ..." alt="cute cats" width="80" height="50"/>
</pictureino-wrap>

<!-- When the image has been loaded-->
<pictureino-wrap>
    <img src="cats-large.webp" alt="cute cats" width="1200" height="800"/>
</pictureino-wrap>
```

## Installation

```bash
composer require zeroseven/pictureino
```

## Commands

### Cleanup processed images

Remove all images of dynamic image requests and cleanup the pictureino database entries

```bash
vendor/bin/typo3 pictureino:cleanup
```

## Security

Pictureiño includes several mechanisms to ensure secure and efficient image processing:

- **Rate Limiter**: A built-in rate limiter monitors the number of image requests for new image sizes. This prevents abuse through excessive requests and protects server resources.
- **Request Optimization**: Similar image requests are automatically grouped to avoid generating too many images in close proximity. This reduces server load and improves performance.
- **Cleanup Task**: Additionally, the processed files are logged along with information about image requests. You can easily delete these images via command, for example, via a regular cron job.

These features make Pictureiño a reliable and secure solution for dynamic image generation in production environments.

## Frequently Asked Questions

### Does this have any disadvantages for SEO?

No, quite the opposite! Pictureiño improves SEO. By serving WebP images, page load speeds are significantly reduced, which is a key ranking factor for Google. Additionally, with lazy loading, only visible images are loaded, optimizing the initial load time. Pictureiño also ensures that images are always delivered in their optimal size and quality across devices. Plus, structured data is automatically available, even while images are still loading. This helps search engines like Google understand the content and context of images right away, improving indexing.

### Is no configuration necessary at all?

No, the responsive image optimization starts immediately with no setup required. However, you can make manual adjustments if needed, such as defining maximum image sizes or setting breakpoints for different aspect ratios.

### Do I need JavaScript?

Yes, JavaScript is required. The concept is designed so that images dynamically report their optimal size to TYPO3 directly from the frontend. Without JavaScript, this functionality cannot be achieved.

### Can I use this with content blocks?

Yes, you can add the custom TCA field `aspect_ratio` to your content blocks.

```yaml
name: vendor/element
typeName: vendor_element
group: default
prefixFields: true
prefixType: vendor
fields:
  # Use the existing field
  - identifier: aspect_ratio
    useExistingField: true
    type: AspectRatio
  # Add a new field of type AspectRatio
  - identifier: foo_bar
    type: AspectRatio
```
