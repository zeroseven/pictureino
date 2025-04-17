import { ImageResponse } from './types';

export class Loader {
  private cache: Map<string, ImageResponse>;

  constructor() {
    this.cache = new Map();
  }

  public preloadImage(url: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
      const img = new Image();

      img.onload = () => resolve(img);
      img.onerror = () => reject(new Error('Fehler beim Laden des Bildes'));
      img.src = url;
    });
  }

  public requestImage(url: string): Promise<ImageResponse> {
    if (this.cache.has(url)) {
      return Promise.resolve(this.cache.get(url)!);
    }

    return fetch(url)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((config: ImageResponse) => {
        this.cache.set(url, config);
        return config;
      })
      .catch(error => {
        throw new Error(`Fehler beim Laden der Konfiguration: ${error}`);
      });
  }

  public clearCache(): void {
    this.cache.clear();
  }

  public removeFromCache(url: string): void {
    this.cache.delete(url);
  }
}
