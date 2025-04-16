import { ImageConfig, ImageResponse } from './types';
import { ApiService } from './apiService';
import { ViewportService } from './viewportService';

export class ImageHandler {
    private static async preloadImage(src: string): Promise<void> {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve();
            img.onerror = () => reject(new Error(`Failed to load image: ${src}`));
            img.src = src;
        });
    }

    private static updateImageSource(image: HTMLImageElement | HTMLSourceElement, imageData: ImageResponse): void {
        if (image instanceof HTMLSourceElement) {
            image.srcset = imageData.attributes.src;
        } else {
            image.src = imageData.attributes.src;
          }

          image.width = imageData.attributes.width;
          image.height = imageData.attributes.height;
    }

    private static findTargetElement(element: HTMLImageElement): HTMLImageElement | HTMLSourceElement {
        // Wenn das Element in einem picture Tag ist, finde das passende source Element
        const picture = element.closest('picture');
        if (picture) {
            const sources = Array.from(picture.getElementsByTagName('source'));
            // Nimm das erste source Element oder das img selbst wenn kein source existiert
            return sources[0] || element;
        }
        return element;
    }

    static async processImage(image: ImageConfig): Promise<void> {
        try {
            await ViewportService.whenInViewport(image.element);

            const width = Math.round(image.element.offsetWidth);
            const height = Math.round(image.element.offsetHeight);

            // Skip if dimensions are 0
            if (width === 0 || height === 0) {
                return;
            }

            const imageData = await ApiService.getOptimizedImage(image.config, width, height);
            await this.preloadImage(imageData.attributes.src);

            // Check if image still exists in DOM before updating
            if (document.body.contains(image.element)) {
                const targetElement = this.findTargetElement(image.element);
                this.updateImageSource(targetElement, imageData);
            }
        } catch (error) {
            console.error('Failed to process image:', error);
        }
    }
}
