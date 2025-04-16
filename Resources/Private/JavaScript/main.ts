import { ImageConfig } from './types';
import { ImageHandler } from './imageHandler';

/**
 * Hauptklasse für das Picturerino Image Processing System
 *
 * Verwendung:
 * <img onload="Picturerino.handle(this)" data-config="image-config" src="placeholder.jpg" />
 */
class Picturerino {
    private static images = new Map<HTMLImageElement, ImageConfig>();
    private static resizeDebounceTimeout: number | null = null;
    private static isInitialized = false;

    private static handleResize(): void {
        if (this.resizeDebounceTimeout) {
            window.clearTimeout(this.resizeDebounceTimeout);
        }

        this.resizeDebounceTimeout = window.setTimeout(() => {
            this.images.forEach(image => {
                // Nur verarbeiten wenn das Element noch im DOM existiert
                if (document.body.contains(image.element)) {
                    ImageHandler.processImage(image);
                } else {
                    this.images.delete(image.element);
                }
            });
        }, 250); // Debounce 250ms
    }

    /**
     * Initialisiert das Picturerino System
     */
    public static init(): void {
        if (this.isInitialized) return;

        window.addEventListener('resize', () => this.handleResize());
        this.isInitialized = true;
    }

    /**
     * Verarbeitet ein einzelnes Bildelement
     * @param element Das zu verarbeitende Bildelement
     */
    public static handle(element: HTMLImageElement): void {
        element.removeAttribute('onload'); // Entferne onload Attribut um Rekursion zu vermeiden
        element.removeAttribute('srcset');

        if (!this.isInitialized) {
            this.init();
        }

        const config = element.getAttribute('data-config');
        if (!config) {
            console.error('No data-config attribute found on image element');
            return;
        }

        const imageConfig: ImageConfig = { element, config };
        this.images.set(element, imageConfig);
        ImageHandler.processImage(imageConfig);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    Picturerino.init();
});

// Make Picturerino available globally
(window as any).Picturerino = Picturerino;
