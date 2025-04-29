# AspectRatioUtility

Handles aspect ratios for responsive images with breakpoint support.

## Usage

### Single Ratio

```php
use Zeroseven\Pictureino\Utility\AspectRatioUtility;

$ratios = AspectRatioUtility::processRatios('16:9');

// Result:
// [
//     0 => ['x' => 16, 'y' => 9]
// ]
```

### Responsive Ratios

```php
$ratios = AspectRatioUtility::processRatios([
    'xs' => '2:1',   // 0px+
    'md' => '1:1',   // 768px+
    'xl' => '16:9'   // 1200px+
]);

// Result:
// [
//     0    => ['x' => 2,  'y' => 1],
//     768  => ['x' => 1,  'y' => 1],
//     1200 => ['x' => 16, 'y' => 9]
// ]
```

### Missing Breakpoints

```php
$ratios = AspectRatioUtility::processRatios([
    'md' => '1:1',
    'xl' => '16:9'
]);

// Result:
// [
//     0    => ['x' => null, 'y' => null],
//     768  => ['x' => 1,    'y' => 1],
//     1200 => ['x' => 16,   'y' => 9]
// ]
```

## Breakpoints

- xs: 0px
- sm: 576px
- md: 768px
- lg: 992px
- xl: 1200px
- xxl: 1400px

## ViewHelper Integration

```html
<p:image
    src="{image.uid}"
    treatIdAsReference="1"
    aspectRatio="{
        xs: '2:1',
        md: '1:1',
        xl: '16:9'
    }"
/>
