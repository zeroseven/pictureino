import {Image} from './image'

declare global {
  interface Window {
    Pictureiño: {
      handle: (element: HTMLImageElement) => void;
    };
  }
}

class Pictureiño {
  public static getConfig(element: HTMLImageElement): string {
    return element.getAttribute('data-config') as string
  }

  public static handle(element: HTMLImageElement): void {
    const config = Pictureiño.getConfig(element)

    new Image(element, config)
  }
}

window.Pictureiño = {
  handle: (element: HTMLImageElement): void => Pictureiño.handle(element),
}
