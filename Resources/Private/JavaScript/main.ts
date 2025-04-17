import { Image } from './image';

class Picturerino {
  public static getConfig(element: HTMLImageElement): string {
    return element.getAttribute('data-config') as string;
  }

  public static handle(element: HTMLImageElement): void {
    const config = Picturerino.getConfig(element);

    new Image(element, config);
  }
}

(window as any).Picturerino = {
  handle: (element: HTMLImageElement) => Picturerino.handle(element)
};
