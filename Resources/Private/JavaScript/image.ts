import { ElementSize } from './types';
import { Observer } from './observer';
import { Loader } from './loader';

export class Image {
  private element: HTMLImageElement;
  private config: string;
  private picture: HTMLPictureElement | null;
  private loaded: boolean;
  private observer: Observer;
  private loader: Loader;
  private size: ElementSize;

  constructor(element: HTMLImageElement, config: string) {
    this.element = element;
    this.config = config;
    this.picture = this.element.closest('picture') as HTMLPictureElement;
    this.loaded = false;
    this.observer = new Observer(this.element);
    this.loader = new Loader();
    this.size = {
      width: this.element.offsetWidth,
      height: this.element.offsetHeight
    };

    this.loadImage = this.loadImage.bind(this);

    this.init();
  }

  public getRequestUri(): string {
    const browserWidth = Math.round(window.innerWidth);

    return `/-/img/${this.size.width}x${this.size.height}/${browserWidth}/${this.config}/`
  }

  public removePictureTag(): void {
    this.picture
      && this.picture.parentNode
      && this.picture.parentNode.insertBefore(this.element, this.picture)
      && this.picture.remove()
  }

  private loadImage(): void {
    if (this.loaded) return;

    this.loader.requestImage(this.getRequestUri())
      .then(config => {
        Object.keys(config.attributes).forEach(key => {
          key === 'src' || this.element.setAttribute(key, config.attributes[key]);
        });

        this.loader.preloadImage(config.attributes.src).then(() => {
          this.element.src = config.attributes.src;
          this.loaded = true;

          this.removePictureTag();
        })
      })
      .catch(error => {
        console.error('Fehler beim Laden des Bildes:', error);
      });
  }

  private async init(): Promise<void> {
    this.element.removeAttribute('data-config');
    this.element.removeAttribute('onload');
    this.element.removeAttribute('srcset');

    this.observer.inView().then(this.loadImage);
    this.observer.resize().then((size: ElementSize) => {
      this.loaded = false;
      this.size = size;
    });
  }
}
