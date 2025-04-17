import { ImageHandler } from './imageHandler';

const list: Picturerino[] = [];

class Picturerino {
    private resizeDebounceTimeout: number | null = null;
    private imageHandler: ImageHandler;
    private element: HTMLImageElement;
    private config: string = '';

    private constructor(element: HTMLImageElement) {
        this.element = element;
        this.imageHandler = ImageHandler.getInstance();

        this.init();
    }

    private handleResize(element: HTMLImageElement, config: string): void {
        if (this.resizeDebounceTimeout) {
            window.clearTimeout(this.resizeDebounceTimeout);
        }

        this.resizeDebounceTimeout = window.setTimeout(() =>
            this.imageHandler.processImage(element, config, false), 250);
    }

    public init(): void {
        this.config = this.element.getAttribute('data-config') as string;

        if (this.config) {
          this.element.removeAttribute('data-config');
          this.element.removeAttribute('onload');
          this.element.removeAttribute('srcset');

          window.addEventListener('resize', () => this.handleResize(this.element, this.config));
        }

        this.imageHandler.processImage(this.element, this.config, true);

        list.push(this);
    }

    public static handle(element: HTMLImageElement): void {
        new Picturerino(element);
    }
}

(window as any).Pictureino = {
  handle: (element: HTMLImageElement) => Picturerino.handle(element)
};
