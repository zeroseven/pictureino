import { Observer } from './observer';

export class Image {
  private element: HTMLImageElement;
  private config: string;
  private picture: HTMLPictureElement | null;
  private loaded: boolean;
  private observer: Observer;
  private width: number;
  private height: number;

  constructor(element: HTMLImageElement, config: string) {
    this.element = element;
    this.config = config;
    this.picture = this.element.closest('picture') as HTMLPictureElement;
    this.loaded = false;
    this.observer = new Observer(this.element);
    this.width = 0;
    this.height = 0;

    this,this.loadImage = this.loadImage.bind(this);

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
    this.observer.inView().then(this.loadImage);

    this.observer.resize().then((width: number, height: number) => {
      this.loaded = false;
      this.width = width;
      this.height = height;
    });
  }
}
