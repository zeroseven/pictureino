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

    private static updateImageSource(image: HTMLImageElement, imageData: ImageResponse): void {
        image.src = imageData.attributes.src;
        image.width = imageData.attributes.width;
        image.height = imageData.attributes.height;
    }

    private static removePictureTag(element: HTMLImageElement): void {
        const picture = element.closest('picture');
        if (picture && picture.parentNode) {
            // Kopiere die Attribute vom source-Element zum img-Element, falls vorhanden
            const source = picture.querySelector('source');
            if (source && source.srcset) {
                element.src = source.srcset;
            }
            // Ersetze das picture-Tag mit dem img-Element
            picture.parentNode.replaceChild(element, picture);
        }
    }

    private static findTargetElement(element: HTMLImageElement): HTMLImageElement {
        // Wenn das Element in einem picture Tag ist, entferne das picture Tag
        this.removePictureTag(element);
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
