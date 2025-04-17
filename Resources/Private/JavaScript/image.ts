import { ElementSize } from './types';
import { Observer } from './observer';

export class Image {
  private element: HTMLImageElement;
  private config: string;
  private picture: HTMLPictureElement | null;
  private loaded: boolean;
  private observer: Observer;
  private size: ElementSize;

  constructor(element: HTMLImageElement, config: string) {
    this.element = element;
    this.config = config;
    this.picture = this.element.closest('picture') as HTMLPictureElement;
    this.loaded = false;
    this.observer = new Observer(this.element);
    this.size = {
      width: this.element.offsetWidth,
      height: this.element.offsetHeight
    };

    this.loadImage = this.loadImage.bind(this);

    this.init();
  }

  public removePictureTag(): void {
    this.picture
      && this.picture.parentNode
      && this.picture.parentNode.insertBefore(this.element, this.picture)
      && this.picture.remove()
  }

  private loadImage(): void {
    if(this.loaded) return;


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
