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

    if (config) {
      new Image(element, config)
    } else {
      console.warn('No config found for the provided element.')
    }
  }
}

window.Pictureiño = {
  handle: (element: HTMLImageElement): void => Pictureiño.handle(element),
}
