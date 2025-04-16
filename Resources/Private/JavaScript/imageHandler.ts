import { ImageConfig } from './types';
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

    private static updateImageSource(image: HTMLImageElement | HTMLSourceElement, src: string): void {
        if (image instanceof HTMLSourceElement) {
            image.srcset = src;
        } else {
            image.src = src;
        }
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

            const newSrc = await ApiService.getOptimizedImageUrl(image.config, width, height);
            await this.preloadImage(newSrc);

            // Check if image still exists in DOM before updating
            if (document.body.contains(image.element)) {
                const targetElement = this.findTargetElement(image.element);
                this.updateImageSource(targetElement, newSrc);
            }
        } catch (error) {
            console.error('Failed to process image:', error);
        }
    }
}
