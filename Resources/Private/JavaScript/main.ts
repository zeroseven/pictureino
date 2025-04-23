import { Image } from './image';

declare global {
  interface Window {
    Picturerino: {
      handle: (element: HTMLImageElement) => void;
    };
  }
}

class Picturerino {
  public static getConfig(element: HTMLImageElement): string {
    return element.getAttribute('data-config') as string;
  }

  public static handle(element: HTMLImageElement): void {
    const config = Picturerino.getConfig(element);

    new Image(element, config);
  }
}

window.Picturerino = {
  handle: (element: HTMLImageElement): void => Picturerino.handle(element)
};
