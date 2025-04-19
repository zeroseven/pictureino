import { ElementSize, ImageResponse, SourceMap } from './types';
import { Observer } from './observer';
import { Loader } from './loader';

export class Image {
  private element: HTMLImageElement;
  private config: string;
  private observer: Observer;
  private loader: Loader;
  private sources: SourceMap;
  private size: ElementSize;

  constructor(element: HTMLImageElement, config: string) {
    this.element = element;
    this.config = config;
    this.observer = new Observer(this.element);
    this.loader = new Loader();
    this.sources = {};
    this.size = {
      width: this.element.offsetWidth,
      height: this.element.offsetHeight
    };

    this.observeElement = this.observeElement.bind(this);
    this.init();
  }

  private getRequestUri(): string {
    return `/-/img/${this.size.width}x${this.size.height}/${Math.round(window.innerWidth)}/${this.config}/`;
  }

  private updateImage(imageResponse: ImageResponse): void {
    this.element.width = imageResponse.processed.width;
    this.element.height = imageResponse.processed.height;
    this.element.src = imageResponse.processed.img;

    if (imageResponse.processed.img2x) {
      this.element.srcset = imageResponse.processed.img2x + ' 2x';
    }
  }

  private updateSourceTag(view: number, imageResponse: ImageResponse): void {
    const source = this.sources[view];

    if (source) {
      source.width = imageResponse.processed.width;
      source.height = imageResponse.processed.height;

      if (imageResponse.processed.img2x) {
        source.srcset = imageResponse.processed.img + ',' + imageResponse.processed.img2x + ' 2x';
      } else {
        source.srcset = imageResponse.processed.img;
      }
    }
  }

  private getSourceKey(view: number): number {
    const views = Object.keys(this.sources).map(Number);

    if(views.length) {
      const lowerViews = views.filter(value => value <= view);

      return lowerViews.length ? Math.max(...lowerViews) : 0;
    }

    return 0
  }

  private updateSource(): void {
    this.loader.requestImage(this.getRequestUri())
      .then((result: ImageResponse) => {
        const sourceKey = this.getSourceKey(result.view);
        sourceKey ? this.updateSourceTag(sourceKey, result) : this.updateImage(result);

        this.element.addEventListener('load', this.observeElement, { once: true });
      }).catch(error => {
        if (error.code === 1745092982) {
          this.observeElement();
        } else {
          console.warn(error)
        }
      });
  }

  private observeElement(): void {
      this.observer.onResize(size => {
        this.size = size;
        this.updateSource();
      }, this.size)
  }

  private init(): void {
    ['data-config', 'onload', 'srcset'].forEach(attr => {
      this.element.removeAttribute(attr);
    });

    const picture = this.element.closest('picture') as HTMLPictureElement;
    if (picture) {
      Array.prototype.slice.call(picture.getElementsByTagName('source')).forEach((source: HTMLSourceElement) => {
        const view = parseInt(source.getAttribute('media')!.match(/\d+/)![0], 10);

        view && (this.sources[view] = source);
      })
    }

    this.observer.inView(() => this.updateSource())
  }
}
