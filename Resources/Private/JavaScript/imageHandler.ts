import { ApiService } from './apiService';
import { ViewportService } from './viewportService';

export class ImageHandler {
    private static preloadImage(src: string): Promise<void> {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve();
            img.onerror = () => reject(new Error(`Failed to load image: ${src}`));
            img.src = src;
        });
    }

    public static removePictureTag(element: HTMLImageElement): void {
        const picture = element.closest('picture');

        if (picture && picture.parentNode) {
            picture.parentNode.insertBefore(element, picture);
            picture.remove();
        }
    }

    static processImage(element: HTMLImageElement, config: string, firstLoad?: boolean): Promise<void> {
        return ViewportService.whenInViewport(element)
            .then(() => {
                const width = Math.round(element.offsetWidth);
                const height = Math.round(element.offsetHeight);

                if (!width || !height) return;

                return ApiService.getOptimizedImage(config, width, height)
                    .then(data => {
                        if (!document.body.contains(element)) return;

                        element.width = data.attributes.width;
                        element.height = data.attributes.height;
                        element.style.aspectRatio = (data.aspectRatio[0] || data.attributes.width) + '/' + (data.aspectRatio[1] || data.attributes.height);

                        return this.preloadImage(data.attributes.src).then(() => {
                              if (document.body.contains(element)) {
                                  element.style.removeProperty('aspect-ratio');

                                  if (firstLoad) {
                                    this.removePictureTag(element);
                                    firstLoad = false;
                                  }

                                  element.src = data.attributes.src;
                              }
                          });
                    });
            })
            .catch(error => console.error('Failed to process image:', error));
    }
}

