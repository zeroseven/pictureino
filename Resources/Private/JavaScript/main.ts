import { ImageHandler } from './imageHandler';

class Picturerino {
    private static list: HTMLImageElement[] = [];
    private static resizeDebounceTimeout: number | null = null;

    private static handleResize(element: HTMLImageElement, config: string): void {
        if (this.resizeDebounceTimeout) {
            window.clearTimeout(this.resizeDebounceTimeout);
        }

        this.resizeDebounceTimeout = window.setTimeout(() => ImageHandler.processImage(element, config, false), 250);
    }

    public static init(element: HTMLImageElement): string|null {
        const config = element.getAttribute('data-config');

        if(config) {
          element.removeAttribute('data-config');
          element.removeAttribute('onload');
          element.removeAttribute('srcset');

          window.addEventListener('resize', () => this.handleResize(element, config));

          return config;
        }

        return null;
    }

    public static handle(element: HTMLImageElement): void {
        const config = this.init(element);

        if (config) {
          this.list.push(element);
          ImageHandler.processImage(element, config, true);
        }
    }
}

(window as any).Picturerino = Picturerino;
