import { ElementSize } from './types';
import { Observer } from './observer';
import { Loader } from './loader';

export class Image {
  private element: HTMLImageElement;
  private config: string;
  private picture: HTMLPictureElement | null;
  private observer: Observer;
  private loader: Loader;
  private size: ElementSize;

  constructor(element: HTMLImageElement, config: string) {
    this.element = element;
    this.config = config;
    this.picture = this.element.closest('picture') as HTMLPictureElement;
    this.observer = new Observer(this.element);
    this.loader = new Loader();
    this.size = {
      width: this.element.offsetWidth,
      height: this.element.offsetHeight
    };

    this.init();
  }

  private getRequestUri(): string {
    return `/-/img/${this.size.width}x${this.size.height}/${Math.round(window.innerWidth)}/${this.config}/`;
  }

  private loadImage(): Promise<void> {
    return this.loader.requestImage(this.getRequestUri())
      .then(config => {
        Object.keys(config.attributes).forEach(key => {
          key === 'src' || this.element.setAttribute(key, config.attributes[key]);
        });

        this.element.style.aspectRatio = (config.aspectRatio[0] || config.attributes.width) + '/' + (config.aspectRatio[1] || config.attributes.height);

        return this.loader.preloadImage(config.attributes.src)
          .then(() => {
            this.element.src = config.attributes.src;
            this.element.style.removeProperty('aspect-ratio');
            this.removePictureTag();
          });
      })
      .catch(error => {
        console.error('Fehler beim Laden des Bildes:', error);
      });
  }

  private removePictureTag(): void {
    if (this.picture?.parentNode) {
      this.picture.parentNode.insertBefore(this.element, this.picture);
      this.picture.remove();
    }
  }

  private observeElement(): void {
    this.observer.inView(() => this.loadImage().then(() => {
      this.observer.onResize(size => {
        this.size = size;
        this.observeElement();
      }, this.size);
    }))
  }

  private init(): void {
    ['data-config', 'onload', 'srcset'].forEach(attr => {
      this.element.removeAttribute(attr);
    });

    this.observeElement();
  }
}
